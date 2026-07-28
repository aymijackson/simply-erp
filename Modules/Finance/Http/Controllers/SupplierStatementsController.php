<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierStatementsController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('finance.reports.supplier_statements.view'), 403);
        return view('finance.supplier_statements.index');
    }

    /**
     * Select2: suppliers lookup (NAME ONLY)
     */
    public function suppliers(Request $request)
    {
        abort_unless(auth()->user()->can('finance.reports.supplier_statements.view'), 403);

        $q = trim((string)$request->q);

        $rows = DB::table('suppliers')
            ->when($q !== '', fn($x) => $x->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(30)
            ->get([
                'id',
                DB::raw("name as text"),
            ]);

        return response()->json(['results' => $rows]);
    }

    /**
     * JSON statement lines + totals
     * Uses ONLY:
     *  - finance_supplier_bills (posted/part_paid/paid)
     *  - finance_supplier_bill_payments (posted)
     *  - finance_supplier_credits (posted)
     *
     * Running balance logic:
     *  Bills   => +amount
     *  Payments=> -amount
     *  Credits => -amount
     */
    public function data(Request $request)
    {
        abort_unless(auth()->user()->can('finance.reports.supplier_statements.view'), 403);

        $companyId  = auth()->user()->company_id ?? 1;
        $supplierId = (int)($request->supplier_id ?? 0);

        $from = $request->date_from ?: null;
        $to   = $request->date_to ?: null;

        if ($supplierId <= 0) {
            return response()->json([
                'ok' => false,
                'message' => 'Supplier is required.',
            ], 422);
        }

        // Supplier name (name only)
        $supplier = DB::table('suppliers')->where('id', $supplierId)->first(['id', 'name']);
        if (!$supplier) {
            return response()->json(['ok' => false, 'message' => 'Supplier not found.'], 404);
        }

        // ---- Opening balance (everything before from date) ----
        $opening = $this->computeOpeningBalance($companyId, $supplierId, $from);

        // ---- Lines within range ----
        $lines = $this->fetchLines($companyId, $supplierId, $from, $to);

        // Running balance
        $run = (float)$opening;
        $out = [];
        foreach ($lines as $ln) {
            $amt = (float)$ln->amount;
            $run = round($run + $amt, 2);

            $out[] = [
                'date' => $ln->txn_date,
                'type' => $ln->txn_type,
                'ref'  => $ln->ref,
                'memo' => $ln->memo,
                'debit'  => $amt > 0 ? number_format($amt, 2) : '',
                'credit' => $amt < 0 ? number_format(abs($amt), 2) : '',
                'balance' => number_format($run, 2),
            ];
        }

        $summary = [
            'supplier_name' => $supplier->name,
            'opening_balance' => number_format((float)$opening, 2),
            'closing_balance' => number_format((float)$run, 2),
            'from' => $from,
            'to' => $to,
        ];

        return response()->json([
            'ok' => true,
            'summary' => $summary,
            'lines' => $out,
        ]);
    }

    /**
     * Optional printable page (simple HTML print)
     */
    public function print(Request $request)
    {
        abort_unless(auth()->user()->can('finance.reports.supplier_statements.view'), 403);

        // Reuse JSON logic and render
        $resp = $this->data($request);
        $payload = $resp->getData(true);

        if (!($payload['ok'] ?? false)) {
            abort(422, $payload['message'] ?? 'Invalid request.');
        }

        return view('finance.supplier_statements.print', [
            'summary' => $payload['summary'],
            'lines' => $payload['lines'],
        ]);
    }

    // ---------------- Helpers ----------------

    private function computeOpeningBalance(int $companyId, int $supplierId, ?string $from): float
    {
        if (!$from) return 0.0;

        $bills = DB::table('finance_supplier_bills as b')
            ->where('b.company_id', $companyId)
            ->where('b.supplier_id', $supplierId)
            ->whereNull('b.deleted_at')
            ->whereIn('b.status', ['posted','part_paid','paid'])
            ->where('b.bill_date', '<', $from)
            ->sum('b.total_amount');

        $payments = DB::table('finance_supplier_bill_payments as p')
            ->where('p.company_id', $companyId)
            ->where('p.supplier_id', $supplierId)
            ->whereNull('p.deleted_at')
            ->where('p.status', 'posted')
            ->where('p.payment_date', '<', $from)
            ->sum('p.amount_total');

        $credits = DB::table('finance_supplier_credits as c')
            ->where('c.company_id', $companyId)
            ->where('c.supplier_id', $supplierId)
            ->whereNull('c.deleted_at')
            ->where('c.status', 'posted')
            ->where('c.credit_date', '<', $from)
            ->sum('c.total_amount');

        // Bills increase AP; payments/credits reduce
        return round(((float)$bills) - ((float)$payments) - ((float)$credits), 2);
    }

    private function fetchLines(int $companyId, int $supplierId, ?string $from, ?string $to)
    {
        // bills: +amount
        $bills = DB::table('finance_supplier_bills as b')
            ->where('b.company_id', $companyId)
            ->where('b.supplier_id', $supplierId)
            ->whereNull('b.deleted_at')
            ->whereIn('b.status', ['posted','part_paid','paid'])
            ->when($from, fn($x) => $x->where('b.bill_date', '>=', $from))
            ->when($to, fn($x) => $x->where('b.bill_date', '<=', $to))
            ->selectRaw("
                b.bill_date as txn_date,
                'Bill' as txn_type,
                COALESCE(b.bill_no, CONCAT('BILL-', b.id)) as ref,
                b.memo as memo,
                CAST(b.total_amount as DECIMAL(18,2)) as amount
            ");

        // payments: -amount
        $payments = DB::table('finance_supplier_bill_payments as p')
            ->where('p.company_id', $companyId)
            ->where('p.supplier_id', $supplierId)
            ->whereNull('p.deleted_at')
            ->where('p.status', 'posted')
            ->when($from, fn($x) => $x->where('p.payment_date', '>=', $from))
            ->when($to, fn($x) => $x->where('p.payment_date', '<=', $to))
            ->selectRaw("
                p.payment_date as txn_date,
                'Payment' as txn_type,
                COALESCE(p.payment_no, CONCAT('PAY-', p.id)) as ref,
                p.memo as memo,
                CAST((0 - p.amount_total) as DECIMAL(18,2)) as amount
            ");

        // credits: -amount
        $credits = DB::table('finance_supplier_credits as c')
            ->where('c.company_id', $companyId)
            ->where('c.supplier_id', $supplierId)
            ->whereNull('c.deleted_at')
            ->where('c.status', 'posted')
            ->when($from, fn($x) => $x->where('c.credit_date', '>=', $from))
            ->when($to, fn($x) => $x->where('c.credit_date', '<=', $to))
            ->selectRaw("
                c.credit_date as txn_date,
                'Credit' as txn_type,
                COALESCE(c.credit_no, CONCAT('CR-', c.id)) as ref,
                c.memo as memo,
                CAST((0 - c.total_amount) as DECIMAL(18,2)) as amount
            ");

        // Union + sort
        return $bills
            ->unionAll($payments)
            ->unionAll($credits)
            ->orderBy('txn_date')
            ->orderBy('txn_type')
            ->get();
    }
}