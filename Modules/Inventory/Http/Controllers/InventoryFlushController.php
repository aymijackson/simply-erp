<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class InventoryFlushController extends Controller
{
    private array $tables = [
        'stock_transactions',
        'stock_entry_lines',
        'stock_entries',
        'stock_issue_lines',
        'stock_issues',
        'stock_transfer_lines',
        'stock_transfers',
    ];

    public function index()
    {
        if (app()->environment('production') && !config('inventory.allow_flush_in_production', false)) {
            abort(403, 'Inventory flush is disabled in production.');
        }

        return view('inventory.flush.index', [
            'tables' => $this->tables,
        ]);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'include_audit' => 'nullable|boolean',
        ]);

        $counts = [];
        foreach ($this->tables as $t) {
            try {
                $counts[$t] = DB::table($t)->count();
            } catch (\Throwable $e) {
                $counts[$t] = 'n/a';
            }
        }

        return response()->json([
            'tables' => $this->tables,
            'counts' => $counts,
        ]);
    }

    public function flush(Request $request)
    {
        if (app()->environment('production') && !config('inventory.allow_flush_in_production', false)) {
            abort(403, 'Inventory flush is disabled in production.');
        }

        $request->validate([
            'confirm_word' => 'required|string',
            'password'     => 'required|string',
            'dry_run'      => 'nullable|boolean',
        ]);

        if (strtoupper(trim($request->confirm_word)) !== 'FLUSH') {
            return back()->with('error', 'Confirmation word must be FLUSH.');
        }

        $user = Auth::user();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->with('error', 'Password confirmation failed.');
        }

        $dryRun = (bool) $request->boolean('dry_run');

        // capture counts BEFORE
        $before = [];
        foreach ($this->tables as $t) {
            try { $before[$t] = DB::table($t)->count(); }
            catch (\Throwable $e) { $before[$t] = null; }
        }

        if ($dryRun) {
            $this->logAudit($user->id, 'inventory', 'flush.preview', 'Dry-run inventory flush preview', [
                'tables' => $this->tables,
                'counts_before' => $before,
            ]);

            return back()->with('ok', 'Dry-run complete. No data was deleted.');
        }

        try {
            // IMPORTANT: do NOT wrap TRUNCATE in a transaction (MySQL implicit commit)
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            foreach ($this->tables as $t) {
                DB::statement("TRUNCATE TABLE `{$t}`");
            }
        } catch (\Throwable $e) {
            // Ensure FK checks are restored even on failure
            try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $ignored) {}

            return back()->with('error', 'Flush failed: ' . $e->getMessage());
        } finally {
            // Always restore FK checks
            try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $ignored) {}
        }

        // Log AFTER flush (outside transaction)
        try {
            $this->logAudit($user->id, 'inventory', 'flush', 'Inventory tables flushed (test-data reset)', [
                'tables' => $this->tables,
                'counts_before' => $before,
            ]);
        } catch (\Throwable $e) {
            // Don’t fail the request if audit logging fails
        }

        return back()->with('ok', 'Inventory flushed successfully.');
    }

    private function logAudit(int $userId, string $module, string $action, string $description, array $meta = []): void
    {
        AuditLog::create([
            'user_id'     => $userId,
            'module'      => $module,
            'action'      => $action,
            'description' => $description,
            'route'       => request()->route()?->getName(),
            'url'         => request()->fullUrl(),
            'method'      => request()->method(),
            'ip'          => request()->ip(),
            'user_agent'  => request()->userAgent(),
            'meta'        => $meta,
        ]);
    }
}
