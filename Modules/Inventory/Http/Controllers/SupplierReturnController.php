<?php

// Modules/Inventory/Http/Controllers/SupplierReturnController.php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

use App\Models\LocationStore;
use Modules\Inventory\Models\StockReturn;
use Modules\Inventory\Models\StockIssue;
use Modules\Inventory\Models\StockReturnLine;
use Modules\Inventory\Services\SupplierReturnService as ReturnService;

class SupplierReturnController extends BaseController
{
    public function __construct()
    {
        // ✅ Permissions (rename to match your Spatie permissions)
        $this->middleware('permission:inventory.supplier_returns.view')->only(['index', 'datatable']);
        $this->middleware('permission:inventory.supplier_returns.create')->only(['store']);
        $this->middleware('permission:inventory.supplier_returns.approve')->only(['approve']);
        $this->middleware('permission:inventory.supplier_returns.post')->only(['post']);
    }

    public function index()
    {
        $data['stores']       = LocationStore::query()->orderBy('name')->get();
        $data['request_uuid'] = (string) Str::uuid();

        $this->audit(
            'inventory.supplier_returns',
            'viewed',
            'Viewed supplier returns list',
            null,
            []
        );

        return view('inventory.returns.suppliers.index', $data);
    }

    public function edit(StockReturn $return)
    {
        $data['issue'] = $return;
        return view('inventory.returns.suppliers.show', $data);
    }
    
    public function datatable()
    {
        $q = StockReturn::query()
            ->supplier()
            ->with([
                'store:id,name',
                'supplier:id,name',
                'postedBy:id,name',
            ])
            ->orderByDesc('stock_returns.created_at');
    
        return DataTables::eloquent($q)
            ->addColumn('location_store', fn($r) => e($r->store?->name ?? '—'))
            ->addColumn('supplier', fn($r) => e($r->supplier?->name ?? '—'))
            ->addColumn('status_badge', function ($r) {
                $status = $r->status ?? 'draft';
                $map = ['posted'=>'success','approved'=>'primary','draft'=>'secondary','void'=>'danger'];
                $cls = $map[$status] ?? 'secondary';
                return '<span class="badge bg-'.$cls.' text-white">'.e(ucfirst($status)).'</span>';
            })
            ->addColumn('posted_info', function ($r) {
                if (!$r->posted_at) return 'Not Posted/ Info. not available';
                $who = $r->postedBy?->name ? ' by '.e($r->postedBy->name) : '';
                return date('d-m-Y h:i a', strtotime($r->posted_at)).$who;
            })
            ->addColumn('actions', fn($r) => view('inventory.returns.suppliers.partials.actions', compact('r')))
            ->rawColumns(['status_badge','actions'])
            ->make(true);
    }

    public function store(Request $r, ReturnService $svc)
    {
        $hdr = $r->validate([
            'store_id'      => 'required|exists:location_stores,id',
            'supplier_id'   => 'required|exists:suppliers,id',
            'reason'        => 'nullable|string|max:255',
            'reference'     => 'nullable|string|max:255',
            'issue_date'    => 'nullable|date',
            'request_uuid'  => 'required|uuid', // ✅ prevents double submit
        ]) + ['return_type' => 'supplier'];

        $lines = collect($r->input('lines', []))
            ->filter(fn($l) => (int)($l['product_variant_id'] ?? 0) > 0 && (float)($l['qty'] ?? 0) > 0)
            ->values()
            ->toArray();

        // Service should enforce idempotency using request_uuid (unique / firstOrCreate)
        $issue = $svc->create($hdr, $lines);

        $this->audit(
            'inventory.supplier_returns',
            'created',
            'Created supplier return draft',
            $issue,
            [
                'request_uuid' => $hdr['request_uuid'],
                'store_id'     => $hdr['store_id'],
                'supplier_id'  => $hdr['supplier_id'],
                'reference'    => $hdr['reference'] ?? null,
                'issue_date'   => $hdr['issue_date'] ?? null,
                'reason'       => $hdr['reason'] ?? null,
                'lines_count'  => count($lines),
            ]
        );

        return response()->json(['ok' => true, 'message' => 'Saved']);
    }

    public function approve(StockReturn $return, ReturnService $svc)
    {
        abort_if($return->return_type !== 'supplier', 400, 'Invalid return type');

        // StockReturn links to StockIssue via reference fields (as in your service)
        $issue = StockIssue::findOrFail($return->reference_id);

        // if your service has approve(StockIssue $issue), use it:
        if (method_exists($svc, 'approve')) {
            $svc->approve($issue);
        } else {
            abort_if($issue->status !== 'draft', 400, 'Already approved');
            $issue->update(['status' => 'approved', 'approved_by' => auth()->id()]);
        }

        // keep StockReturn in sync
        $return->update(['status' => 'approved']);

        $this->audit(
            'inventory.supplier_returns',
            'approved',
            'Approved supplier return',
            $issue,
            [
                'stock_return_id' => $return->id,
                'return_no'       => $return->return_no ?? null,
                'issue_id'        => $issue->id,
            ]
        );

        return response()->json(['ok' => true, 'message' => 'Approved']);
    }

    public function update(Request $r, StockReturn $return)
    {
        abort_if($return->return_type !== 'supplier', 400, 'Invalid return type');
    
        // Only editable in draft
        abort_if(($return->status ?? 'draft') !== 'draft', 422, 'Only draft returns can be edited.');
    
        // Linked StockIssue (your StockReturn.reference_id points to StockIssue.id)
        $issue = StockIssue::with(['lines'])->findOrFail($return->reference_id);
    
        abort_if(($issue->status ?? 'draft') !== 'draft', 422, 'Only draft issues can be edited.');
    
        $hdr = $r->validate([
            'store_id'     => 'required|exists:location_stores,id',
            'supplier_id'  => 'required|exists:suppliers,id',
            'reason'       => 'nullable|string|max:255',
            'reference'    => 'nullable|string|max:255',
            'issue_date'   => 'nullable|date',
            // request_uuid can stay as-is; do not force regeneration on edit
            'request_uuid' => 'nullable|uuid',
        ]);
    
        $lines = collect($r->input('lines', []))
            ->map(function ($l) {
                return [
                    'product_variant_id' => (int)($l['product_variant_id'] ?? 0),
                    'qty'                => (float)($l['qty'] ?? 0),
                    'unit_cost'          => (float)($l['unit_cost'] ?? 0),
                ];
            })
            ->filter(fn ($l) => $l['product_variant_id'] > 0 && $l['qty'] > 0)
            ->values();
    
        if ($lines->count() < 1) {
            return response()->json([
                'ok' => false,
                'message' => 'Add at least one valid line item.',
            ], 422);
        }
    
        DB::transaction(function () use ($hdr, $lines, $return, $issue) {
    
            // ---------- Update StockIssue header ----------
            $issue->update([
                'from_store_id' => (int)$hdr['store_id'],               // issue uses from_store_id
                'store_id'      => (int)$hdr['store_id'],               // if your StockIssue has store_id too
                'supplier_id'   => (int)$hdr['supplier_id'],
                'issue_date'    => $hdr['issue_date'] ?? $issue->issue_date,
                'reason'        => $hdr['reason'] ?? null,
                'reference'     => $hdr['reference'] ?? null,
            ]);
    
            // Rebuild StockIssue lines
            $issue->lines()->delete();
    
            $issueLineRows = $lines->map(function ($l) {
                $qty  = (float)$l['qty'];
                $cost = (float)$l['unit_cost'];
    
                return [
                    'product_variant_id' => (int)$l['product_variant_id'],
                    'qty'                => $qty,
                    'unit_cost'          => $cost,
                    'value'              => $qty * $cost,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ];
            })->all();
    
            $issue->lines()->createMany($issueLineRows);
    
            // ---------- Update StockReturn header ----------
            $return->update([
                'store_id'     => (int)$hdr['store_id'],
                'supplier_id'  => (int)$hdr['supplier_id'],
                'reason'       => $hdr['reason'] ?? null,
                // keep request_uuid stable (optional)
                'request_uuid' => $hdr['request_uuid'] ?? $return->request_uuid,
            ]);
    
            // Rebuild StockReturn lines (aggregated by variant)
            StockReturnLine::where('stock_return_id', $return->id)->delete();
    
            $grouped = [];
            foreach ($lines as $ln) {
                $vid  = (int)$ln['product_variant_id'];
                $qty  = (float)$ln['qty'];
                $cost = (float)$ln['unit_cost'];
    
                if (!$vid || $qty <= 0) continue;
    
                $grouped[$vid]['qty'] = ($grouped[$vid]['qty'] ?? 0) + $qty;
                $grouped[$vid]['cost_value'] = ($grouped[$vid]['cost_value'] ?? 0) + ($qty * $cost);
            }
    
            $returnRows = [];
            foreach ($grouped as $vid => $agg) {
                $qty = (float)$agg['qty'];
                if ($qty <= 0) continue;
    
                $returnRows[] = [
                    'stock_return_id'    => $return->id,
                    'product_variant_id' => $vid,
                    'qty'                => $qty,
                    'unit_cost'          => ($agg['cost_value'] / $qty),
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ];
            }
    
            if ($returnRows) {
                StockReturnLine::insert($returnRows);
            }
        });
    
        // Audit
        $totalQty   = $lines->sum('qty');
        $totalValue = $lines->sum(fn($l) => $l['qty'] * $l['unit_cost']);
        $variantIds = $lines->pluck('product_variant_id')->unique()->values()->all();
    
        $this->audit(
            'inventory.supplier_returns',
            'updated',
            'Updated supplier return draft',
            $issue,
            [
                'stock_return_id' => $return->id,
                'issue_id'        => $issue->id,
                'store_id'        => (int)$hdr['store_id'],
                'supplier_id'     => (int)$hdr['supplier_id'],
                'reference'       => $hdr['reference'] ?? null,
                'issue_date'      => $hdr['issue_date'] ?? null,
                'reason'          => $hdr['reason'] ?? null,
                'lines_count'     => $lines->count(),
                'variant_ids'     => $variantIds,
                'total_qty'       => (float)$totalQty,
                'total_value'     => (float)$totalValue,
            ]
        );
    
        return response()->json(['ok' => true, 'message' => 'Updated']);
    }

    public function post(StockReturn $return, ReturnService $svc)
    {
        abort_if($return->return_type !== 'supplier', 400, 'Invalid return type');

        $issue = StockIssue::with('lines.variant')->findOrFail($return->reference_id);

        $svc->post($issue);

        $this->audit(
            'inventory.supplier_returns',
            'posted',
            'Posted supplier return',
            $issue,
            [
                'stock_return_id' => $return->id,
                'return_no'       => $return->return_no ?? null,
                'issue_id'        => $issue->id,
                'posted_at'       => now()->toDateTimeString(),
            ]
        );

        return response()->json(['ok' => true, 'message' => 'Posted']);
    }
    
    public function destroy(StockReturn $return)
    {
        abort_if($return->return_type !== 'supplier', 400, 'Invalid return type');
        abort_if($return->status !== 'draft', 400, 'Only draft returns can be deleted');
    
        $issueId = $return->reference_id;
    
        DB::transaction(function () use ($return, $issueId) {
    
            // delete return lines first
            $return->lines()->delete();
    
            // delete linked stock issue + its lines (draft only)
            if ($issueId) {
                $issue = StockIssue::with('lines')->find($issueId);
    
                if ($issue && $issue->status === 'draft') {
                    $issue->lines()->delete();
                    $issue->delete();
                }
            }
    
            // delete stock return itself
            $return->delete();
        });
    
        $this->audit(
            'inventory.supplier_returns',
            'deleted',
            'Deleted supplier return draft',
            null,
            [
                'stock_return_id' => $return->id,
                'return_no'       => $return->return_no ?? null,
                'stock_issue_id'  => $issueId,
            ]
        );
    
        return response()->json([
            'ok' => true,
            'message' => 'Draft supplier return deleted'
        ]);
    }

}
