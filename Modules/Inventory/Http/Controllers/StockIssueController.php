<?php

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Inventory\Models\StockIssue;
use App\Models\AuditLog;
use App\Models\LocationStore;
use Modules\Inventory\Models\Product\ProductVariant;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Services\StockIssueService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StockIssueController extends Controller
{
    /* ---------------------------------------------------------
     * AJAX: variant lookup (NO audit)
     * --------------------------------------------------------- */
    public function fetch_variants(Request $req)
    {
        $q = $req->get('q', '');

        $variants = ProductVariant::query()
            ->with('product:id,product_name')
            ->when($q, function ($qry) use ($q) {
                $qry->where('sku', 'like', "%$q%")
                    ->orWhereHas('product', fn($p) =>
                        $p->where('product_name', 'like', "%$q%")
                    );
            })
            ->orderBy('sku')
            ->limit(30)
            ->get(['id','product_id','sku']);

        return response()->json(
            $variants->map(fn($v) => [
                'id'   => $v->id,
                'text' => ($v->product->product_name ?? 'Unknown') . ' - ' . $v->sku,
            ])->values()
        );
    }

    /* ---------------------------------------------------------
     * Index
     * --------------------------------------------------------- */
    public function index()
    {
        $stores = LocationStore::orderBy('name')->get(['id','name']);
        return view('inventory.stock.issues.index', compact('stores'));
    }

    /* ---------------------------------------------------------
     * Datatable (NO audit)
     * --------------------------------------------------------- */
    public function datatable()
    {
        $q = StockIssue::with('fromStore')
            ->orderBy('id', 'DESC')
            ->select(['id','issue_no','from_store_id','status','created_at']);

        return datatables()->eloquent($q)
            ->addColumn('posted_at', fn($r) => optional($r->posted_at)->format('d-m-Y h:i a') ?? '-')
            ->addColumn('store', fn($r) => $r->fromStore->name)
            ->addColumn('actions', fn($r) =>
                view('inventory.stock.issues.partials.actions', compact('r'))
            )
            ->make(true);
    }

    /* ---------------------------------------------------------
     * Store (CREATE draft) — AUDIT
     * --------------------------------------------------------- */
    public function store(Request $r)
    {
        $issue = DB::transaction(function () use ($r) {

            $hdr = StockIssue::create([
                'issue_no' => $this->nextNumber(strtoupper($r->input('issue_type','ISS'))),

                'issue_type'        => $r->input('issue_type', 'normal'),
                'from_store_id'     => $r->input('from_store_id'),
                'reference'         => $r->input('reference'),
                'reason'            => $r->input('reason'),
                'requested_by'      => auth()->id(),
                'issue_date'        => $r->input('issue_date', now()),
                'bom_header_id'     => $r->input('bom_header_id'),
                'sales_delivery_id' => $r->input('sales_delivery_id'),
                'status'            => 'draft',
            ]);

            $lines = collect($r->input('lines', []))
                ->filter(fn ($ln) => !empty($ln['product_variant_id']))
                ->map(fn ($ln) => [
                    'product_variant_id' => (int) $ln['product_variant_id'],
                    'qty'        => (float) $ln['qty'],
                    'unit_cost'  => (float) ($ln['unit_cost'] ?? 0),
                    'value'      => ((float)$ln['qty']) * ((float)($ln['unit_cost'] ?? 0)),
                ]);

            if ($lines->isEmpty()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'lines' => 'At least one valid line is required.',
                ]);
            }

            $hdr->lines()->createMany($lines->all());
            return $hdr;
        });

        // ✅ AUDIT: draft created
        $this->audit(
            action: 'create',
            description: 'Created stock issue draft',
            subject: $issue,
            meta: [
                'issue_no'  => $issue->issue_no,
                'store_id'  => $issue->from_store_id,
                'lines'     => $issue->lines()->count(),
            ]
        );

        return response()->json([
            'id' => $issue->id,
            'message' => 'Issue saved (draft).'
        ]);
    }

    /* ---------------------------------------------------------
     * Approve — AUDIT
     * --------------------------------------------------------- */
    public function approve(StockIssue $issue)
    {
        if ($issue->status !== 'draft') {
            return response()->json([
                'message' => 'Only draft issues can be approved'
            ], 422);
        }

        $issue->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // ✅ AUDIT
        $this->audit(
            action: 'approve',
            description: 'Approved stock issue',
            subject: $issue,
            meta: [
                'issue_no' => $issue->issue_no,
            ]
        );

        return response()->json(['message' => 'Issue approved']);
    }

    /* ---------------------------------------------------------
     * Post — AUDIT (CRITICAL)
     * --------------------------------------------------------- */
    public function post(StockIssue $issue, StockIssueService $service)
    {
        try {
            $service->post($issue->loadMissing('lines.variant'));
            $fresh = $issue->fresh();

            // ✅ AUDIT
            $this->audit(
                action: 'post',
                description: 'Posted stock issue',
                subject: $fresh,
                meta: [
                    'issue_no' => $fresh->issue_no,
                    'store_id' => $fresh->from_store_id,
                    'lines'    => $fresh->lines()->count(),
                ]
            );

            return response()->json([
                'id'        => $fresh->id,
                'status'    => $fresh->status,
                'posted_at' => optional($fresh->posted_at)->toDateTimeString(),
                'message'   => 'Issue posted successfully.',
            ]);

        } catch (HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'Post failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /* ---------------------------------------------------------
     * Show (NO audit – intentional)
     * --------------------------------------------------------- */
    public function show(StockIssue $issue)
    {
        $issue->load([
            'fromStore',
            'lines.variant.product'
        ]);

        return view('inventory.stock.issues.show', compact('issue'));
    }

    /* ---------------------------------------------------------
     * Lines datatable (NO audit)
     * --------------------------------------------------------- */
    public function linesDatatable(StockIssue $issue)
    {
        $q = $issue->lines()->with('variant.product');

        return datatables()->eloquent($q)
            ->addColumn('sku', fn($l) => $l->variant->sku)
            ->addColumn('name', fn($l) => $l->variant->product->product_name)
            ->addColumn('qty', fn($l) => number_format($l->qty, 4))
            ->addColumn('u_cost', fn($l) => number_format($l->unit_cost, 2))
            ->addColumn('value', fn($l) => number_format($l->value, 2))
            ->make(true);
    }

    /* ---------------------------------------------------------
     * Helper
     * --------------------------------------------------------- */
    protected function nextNumber(string $type = 'ISS'): string
    {
        // Switch for future settings
        switch ($type) {
            case 'SALES': $prefix = 'SIS'; break; // sales issue
            case 'BOM':   $prefix = 'BIS'; break;
            default:      $prefix = 'ISS'; break;
        }
    
        $latest = StockIssue::where('issue_no', 'like', $prefix.'-%')
            ->orderByDesc('id')
            ->value('issue_no');
    
        $lastSeq = 0;
        if ($latest) {
            $parts = explode('-', $latest);
            $lastSeq = (int) end($parts);
        }
    
        $seq = $lastSeq + 1;
    
        return $prefix . '-' . str_pad((string)$seq, 5, '0', STR_PAD_LEFT);
    }

    
    protected function audit(
        string $action,
        ?string $description = null,
        $subject = null,
        array $meta = []
    ): void {
        $user = auth()->user();
    
        AuditLog::create([
            'user_id'      => $user?->id,
            'module'       => 'inventory.stock.issues',
            'action'       => $action,
            'description'  => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->id,
            'route'        => request()->route()?->getName(),
            'url'          => request()->fullUrl(),
            'method'       => request()->method(),
            'ip'           => request()->ip(),
            'user_agent'   => request()->userAgent(),
            'meta'         => $meta,
        ]);
    }
    
}
