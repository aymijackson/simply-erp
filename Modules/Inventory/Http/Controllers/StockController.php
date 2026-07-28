<?php

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Models\LocationStore;
use App\Models\StoreShelf; // Adjust namespace if your StoreShelf model lives elsewhere
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Inventory\Exports\StockEntryExport;
use Modules\Inventory\Models\Product\Product;
use Modules\Inventory\Models\Product\Brand;
use Modules\Inventory\Models\Product\BrandManufacturer as Manufacturer;
use Modules\Inventory\Models\Product\Category;
use Modules\Inventory\Models\Product\ProductAttribute;
use Modules\Inventory\Models\Product\ProductAttributeType;
use Modules\Inventory\Models\Product\ProductAttributeValue;
use Modules\Inventory\Models\Product\ProductVariant;
use Modules\Inventory\Models\Product\Unit;
use Modules\Inventory\Models\StockEntry;
use Modules\Inventory\Models\StockEntryLine;
use Modules\Inventory\Models\StockTransaction;
use Modules\Inventory\Services\StockService;

class StockController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission:inventory.stock.entries.view')->only([
            'index',
            'datatable',
            'show',
            'show2',
            'stockEntryLinesIndex',
            'stockEntryLineIndex',
            'stockTransactionsIndex',
            'stockTransactionsDatatable',
            'workflow',
            'getShelvesByStore',
        ]);

        $this->middleware('permission:inventory.stock.entries.create')->only([
            'store',
            'storeStockEntryLine',
        ]);

        $this->middleware('permission:inventory.stock.entries.edit')->only([
            'update',
            'updateStockEntryLine',
        ]);

        $this->middleware('permission:inventory.stock.entries.delete')->only([
            'destroy',
            'destroyStockEntryLine',
            'bulkDelete',
        ]);

        $this->middleware('permission:inventory.stock.entries.approve')->only([
            'approve',
        ]);

        $this->middleware('permission:inventory.stock.entries.post')->only([
            'post',
        ]);

        $this->middleware('permission:inventory.stock.entries.unpost')->only([
            'unpost',
        ]);

        $this->middleware('permission:inventory.stock.entries.export')->only([
            'export',
        ]);

        $this->middleware('permission:inventory.stock.entries.analytics.view')->only([
            'analyticsIndex',
            'analyticsData',
        ]);

        $this->middleware('permission:inventory.stock.entries.analytics.export')->only([
            'analyticsExport',
        ]);

        $this->middleware('permission:inventory.stock.entry_lines.view')->only([
            'stockEntryLineDatatable',
            'showStockEntryLine',
        ]);
    }

    public function index()
    {
        return view('inventory.stock.entries.index', [
            'stores'   => LocationStore::orderBy('name')->get(),
            'variants' => ProductVariant::with('product:id,product_name')
                ->orderBy('sku')
                ->get(),
        ]);
    }

    public function analyticsIndex()
    {
        return view('inventory.stock.entries.analytics', [
            'stores' => LocationStore::orderBy('name')->get(),
        ]);
    }

    /**
     * AJAX endpoint: returns all dashboard metrics in one response.
     * Filters: from, to, store_id, status, entry_type
     */
    public function analyticsData(Request $request)
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->from)->startOfDay()
            : now()->subDays(30)->startOfDay();

        $to = $request->filled('to')
            ? Carbon::parse($request->to)->endOfDay()
            : now()->endOfDay();

        $base = StockEntry::query()
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->store_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('entry_type'), fn ($q) => $q->where('entry_type', $request->entry_type))
            ->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()]);

        $kpis = (clone $base)
            ->selectRaw("
                COUNT(*) as total_entries,
                SUM(CASE WHEN status='draft' THEN 1 ELSE 0 END) as draft_count,
                SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN status='posted' THEN 1 ELSE 0 END) as posted_count,
                SUM(CASE WHEN entry_type='cust_return' THEN 1 ELSE 0 END) as returns_count,
                SUM(CASE WHEN entry_type='normal' THEN 1 ELSE 0 END) as normal_count
            ")
            ->first();

        $trend = (clone $base)
            ->selectRaw("DATE(entry_date) as d, COUNT(*) as c")
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        $byStore = (clone $base)
            ->leftJoin('location_stores', 'location_stores.id', '=', 'stock_entries.store_id')
            ->selectRaw("location_stores.name as label, COUNT(*) as value")
            ->groupBy('label')
            ->orderByDesc('value')
            ->get();

        $byStatus = (clone $base)
            ->selectRaw("status as label, COUNT(*) as value")
            ->groupBy('label')
            ->orderByDesc('value')
            ->get();

        $topVariants = DB::table('stock_entry_lines as l')
            ->join('stock_entries as e', 'e.id', '=', 'l.stock_entry_id')
            ->join('product_variants as v', 'v.id', '=', 'l.product_variant_id')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->when($request->filled('store_id'), fn ($q) => $q->where('e.store_id', $request->store_id))
            ->when($request->filled('status'), fn ($q) => $q->where('e.status', $request->status))
            ->when($request->filled('entry_type'), fn ($q) => $q->where('e.entry_type', $request->entry_type))
            ->whereBetween('e.entry_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw("CONCAT(v.sku,' – ',p.product_name) as label, SUM(l.qty) as value")
            ->groupBy('label')
            ->orderByDesc('value')
            ->limit(10)
            ->get();

        $this->audit(
            action: 'analytics.view',
            description: 'Viewed stock entry analytics',
            subject: null,
            meta: [
                'filters' => [
                    'from'       => $from->toDateString(),
                    'to'         => $to->toDateString(),
                    'store_id'   => $request->store_id,
                    'status'     => $request->status,
                    'entry_type' => $request->entry_type,
                ],
            ]
        );

        return response()->json([
            'filters' => [
                'from' => $from->toDateString(),
                'to'   => $to->toDateString(),
            ],
            'kpis'         => $kpis,
            'trend'        => $trend,
            'by_store'     => $byStore,
            'by_status'    => $byStatus,
            'top_variants' => $topVariants,
        ]);
    }

    /**
     * Export dashboard data (filtered) in Excel or PDF.
     * query params: from,to,store_id,status,entry_type,type=excel|pdf
     */
    public function analyticsExport(Request $request)
    {
        $type = $request->get('type');

        $entries = StockEntry::with(['store', 'supplier', 'customer', 'lines.product_variant.product'])
            ->when($request->filled('from'), fn ($q) => $q->whereDate('entry_date', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('entry_date', '<=', $request->to))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->store_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('entry_type'), fn ($q) => $q->where('entry_type', $request->entry_type))
            ->orderBy('entry_date', 'desc')
            ->get();

        $filters = $request->only(['from', 'to', 'store_id', 'status', 'entry_type']);

        if ($type === 'excel') {
            $this->permit('inventory.stock.entries.analytics.export.excel');

            $this->audit(
                action: 'analytics.export.excel',
                description: 'Exported stock entry analytics to Excel',
                subject: null,
                meta: [
                    'filters' => $filters,
                    'count'   => $entries->count(),
                ]
            );

            $this->workflow('inventory', 'stock_entries_analytics_export_excel', 0);

            return Excel::download(
                new StockEntryExport($entries),
                'stock-entry-analytics.xlsx'
            );
        }

        if ($type === 'pdf') {
            $this->permit('inventory.stock.entries.analytics.export.pdf');

            $this->audit(
                action: 'analytics.export.pdf',
                description: 'Exported stock entry analytics to PDF',
                subject: null,
                meta: [
                    'filters' => $filters,
                    'count'   => $entries->count(),
                ]
            );

            $this->workflow('inventory', 'stock_entries_analytics_export_pdf', 0);

            $pdf = Pdf::loadView(
                'inventory.stock.entries.analytics_pdf',
                compact('entries', 'filters')
            );

            return $pdf->download('stock-entry-analytics.pdf');
        }

        abort(422, 'Unknown export type');
    }

    public function export(Request $request)
    {
        $entries = StockEntry::with(['store', 'supplier', 'customer', 'lines.product_variant'])
            ->when($request->filled('from'), fn ($q) => $q->whereDate('entry_date', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('entry_date', '<=', $request->to))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->store_id))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->filled('variant_id'), function ($q) use ($request) {
                $q->whereHas('lines', fn ($l) => $l->where('product_variant_id', $request->variant_id));
            })
            ->orderBy('entry_date', 'desc')
            ->get();

        $filters = $request->only([
            'from',
            'to',
            'store_id',
            'supplier_id',
            'customer_id',
            'variant_id',
            'type',
        ]);

        switch ($request->type) {
            case 'excel':
                $this->permit('inventory.stock.entries.export.excel');

                $this->audit(
                    action: 'export.excel',
                    description: 'Exported stock entries to Excel',
                    subject: null,
                    meta: [
                        'filters' => $filters,
                        'count'   => $entries->count(),
                    ]
                );

                $this->workflow('inventory', 'stock_entries_export_excel', 0);

                return Excel::download(new StockEntryExport($entries), 'stock-report.xlsx');

            case 'pdf':
                $this->permit('inventory.stock.entries.export.pdf');

                $this->audit(
                    action: 'export.pdf',
                    description: 'Exported stock entries to PDF',
                    subject: null,
                    meta: [
                        'filters' => $filters,
                        'count'   => $entries->count(),
                    ]
                );

                $this->workflow('inventory', 'stock_entries_export_pdf', 0);

                $pdf = Pdf::loadView('inventory.stock.entries.pdf', ['entries' => $entries]);
                return $pdf->download('stock-report.pdf');
        }

        return back()->with('error', 'Unknown export type');
    }

    public function getShelvesByStore($storeId)
    {
        $shelves = StoreShelf::where('store_id', $storeId)->get();
        return response()->json($shelves);
    }

    public function datatable()
    {
        $q = StockEntry::with(['store:id,name', 'supplier:id,name', 'customer:id,name'])
            ->select('stock_entries.*')
            ->orderBy('id', 'desc');

        return datatables()->eloquent($q)
            ->addColumn('checkbox', fn ($row) =>
                '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->addColumn('store_name', fn ($row) => $row->store->name ?? 'N/A')
            ->addColumn('party', function ($row) {
                return match ($row->entry_type) {
                    'cust_return' => 'Customer: ' . (($row->customer?->name) ?? 'N/A'),
                    'normal'      => 'Supplier: ' . (($row->supplier?->name) ?? 'N/A'),
                    default       => 'N/A',
                };
            })
            ->addColumn('entry_date', fn ($row) => date('d-m-Y', strtotime($row->entry_date)))
            ->addColumn('actions', function ($row) {
                $u = Auth::user();
                $btn = '';

                if ($u->can('inventory.stock.entry_lines.view')) {
                    $btn .= '<button class="btn btn-sm btn-secondary me-1 view-lines" data-id="'.$row->id.'">Lines</button>';
                }

                if ($row->status === 'draft' && $u->can('inventory.stock.entries.approve')) {
                    $btn .= '<button class="btn btn-sm btn-warning me-1 approve-entry" data-id="'.$row->id.'">Approve</button>';
                }

                if ($row->status === 'approved' && $u->can('inventory.stock.entries.post')) {
                    $btn .= '<button class="btn btn-sm btn-success me-1 post-entry" data-id="'.$row->id.'">Post</button>';
                }

                if ($row->status === 'posted') {
                    $btn .= '<span class="badge bg-success me-1">Posted</span>';

                    if ($u->can('inventory.stock.entries.unpost')) {
                        $btn .= '<button class="btn btn-sm btn-outline-danger me-1 unpost-entry" data-id="'.$row->id.'">Unpost</button>';
                    }
                }

                if ($u->can('inventory.stock.entries.edit')) {
                    $btn .= '<button class="btn btn-sm btn-primary me-1 edit-entry" data-id="'.$row->id.'">Edit</button>';
                }

                if ($row->status !== 'posted' && $u->can('inventory.stock.entries.delete')) {
                    $btn .= '<button class="btn btn-sm btn-danger delete-entry" data-id="'.$row->id.'">Delete</button>';
                }

                return $btn ?: '-';
            })
            ->rawColumns(['checkbox', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $entry = DB::transaction(function () use ($data) {
            $entry = StockEntry::create($data['header']);

            foreach ($data['lines'] as $line) {
                $entry->lines()->create($line);
            }

            if (($data['header']['status'] ?? null) === 'posted') {
                StockService::postEntry($entry->fresh('lines'));
                $entry->refresh();
            }

            return $entry->fresh(['lines']);
        });

        $linesMeta = $entry->lines->map(fn ($l) => [
            'product_variant_id' => $l->product_variant_id,
            'qty'                => (float) $l->qty,
            'unit_cost'          => $l->unit_cost !== null ? (float) $l->unit_cost : null,
        ])->toArray();

        $this->audit(
            action: 'created',
            description: 'Created stock entry ' . ($entry->reference ?: '#'.$entry->id),
            subject: $entry,
            meta: [
                'store_id'    => $entry->store_id,
                'entry_date'  => $entry->entry_date,
                'entry_type'  => $entry->entry_type ?? null,
                'status'      => $entry->status,
                'lines_count' => count($linesMeta),
                'lines'       => $linesMeta,
            ]
        );

        $this->workflow('inventory', 'stock_entry_created', $entry->id);

        if ($entry->status === 'posted') {
            $this->audit(
                action: 'posted',
                description: 'Auto-posted stock entry ' . ($entry->reference ?: '#'.$entry->id),
                subject: $entry,
                meta: [
                    'store_id' => $entry->store_id,
                    'status'   => 'posted',
                ]
            );

            $this->workflow('inventory', 'stock_entry_posted', $entry->id);
        }

        return response()->json(['message' => 'Stock entry saved']);
    }

    public function approve($id)
    {
        $entry = StockEntry::findOrFail($id);

        if ($entry->status !== 'draft') {
            abort(422, 'Only draft entries can be approved.');
        }

        $before = ['status' => $entry->status];

        $entry->update(['status' => 'approved']);

        $this->audit(
            action: 'approved',
            description: 'Approved stock entry ' . ($entry->reference ?? '#'.$entry->id),
            subject: $entry,
            meta: [
                'before' => $before,
                'after'  => ['status' => 'approved'],
            ]
        );

        $this->workflow('inventory', 'stock_entry_approved', $entry->id);

        return response()->json(['message' => 'Entry approved']);
    }

    public function post($id)
    {
        $entry = StockEntry::with('lines')->findOrFail($id);

        if ($entry->status !== 'approved') {
            abort(422, 'Only approved entries can be posted.');
        }

        $before = ['status' => $entry->status];

        DB::transaction(function () use ($entry) {
            $entry->update(['status' => 'posted']);
            StockService::postEntry($entry->fresh('lines'));
        });

        $this->audit(
            action: 'posted',
            description: 'Posted stock entry ' . ($entry->reference ?? '#'.$entry->id),
            subject: $entry,
            meta: [
                'before'      => $before,
                'after'       => ['status' => 'posted'],
                'lines_count' => $entry->lines()->count(),
            ]
        );

        $this->workflow('inventory', 'stock_entry_posted', $entry->id);

        return response()->json(['message' => 'Entry posted']);
    }

    public function unpost($id)
    {
        $entry = StockEntry::with('lines')->findOrFail($id);

        if ($entry->status !== 'posted') {
            abort(422, 'Only posted entries can be unposted.');
        }

        $before = ['status' => $entry->status];

        DB::transaction(function () use ($entry) {
            StockService::unpostEntry($entry);
            $entry->update(['status' => 'approved']);
        });

        $this->audit(
            action: 'unposted',
            description: 'Unposted stock entry ' . ($entry->reference ?? '#'.$entry->id),
            subject: $entry,
            meta: [
                'before' => $before,
                'after'  => ['status' => 'approved'],
            ]
        );

        $this->workflow('inventory', 'stock_entry_unposted', $entry->id);

        return response()->json(['message' => 'Entry unposted']);
    }

    public function show($id)
    {
        $entry = StockEntry::with([
            'lines',
            'supplier:id,name',
            'customer:id,name',
        ])->findOrFail($id);

        return response()->json([
            'id'         => $entry->id,
            'store_id'   => $entry->store_id,
            'entry_date' => $entry->entry_date,
            'reference'  => $entry->reference,
            'status'     => $entry->status,
            'entry_type' => $entry->entry_type ?? 'normal',

            'supplier' => $entry->supplier ? [
                'id'   => $entry->supplier->id,
                'text' => $entry->supplier->name,
            ] : null,

            'customer' => $entry->customer ? [
                'id'   => $entry->customer->id,
                'text' => $entry->customer->name,
            ] : null,

            'lines' => $entry->lines->map(fn ($l) => [
                'product_variant_id' => $l->product_variant_id,
                'qty'                => $l->qty,
                'unit_cost'          => $l->unit_cost,
                'invoice_line_id'    => $l->invoice_line_id ?? null,
                'delivery_line_id'   => $l->delivery_line_id ?? null,
            ])->values(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $this->validated($request);

        $entryBefore = StockEntry::with('lines')->findOrFail($id);
        $before = [
            'header' => $entryBefore->only([
                'store_id',
                'entry_date',
                'entry_type',
                'reference',
                'supplier_id',
                'customer_id',
                'status'
            ]),
            'lines' => $entryBefore->lines
                ->map(fn ($l) => $l->only(['product_variant_id', 'qty', 'unit_cost']))
                ->toArray(),
        ];

        DB::transaction(function () use ($data, $id, $request) {
            $entry = StockEntry::with('lines')->findOrFail($id);
            $wasPosted = $entry->status === 'posted';
            $newStatus = $data['header']['status'];
            $wantsPost = $newStatus === 'posted';
            $repost = $request->boolean('repost');

            if ($wasPosted && !$repost && $newStatus === 'posted') {
                abort(422, 'Entry is already posted. Set it back to draft/approved or pass repost=1.');
            }

            if ($wasPosted && $newStatus !== 'posted') {
                StockService::unpostEntry($entry);
                $entry->refresh();
            }

            $entry->update($data['header']);
            $entry->lines()->delete();

            foreach ($data['lines'] as $line) {
                $entry->lines()->create($line);
            }

            if ($wantsPost) {
                StockService::postEntry($entry->fresh('lines'), $wasPosted || $repost);
            } else {
                $entry->forceFill(['status' => $newStatus])->save();
            }
        });

        $entryAfter = StockEntry::with('lines')->findOrFail($id);
        $after = [
            'header' => $entryAfter->only([
                'store_id',
                'entry_date',
                'entry_type',
                'reference',
                'supplier_id',
                'customer_id',
                'status'
            ]),
            'lines' => $entryAfter->lines
                ->map(fn ($l) => $l->only(['product_variant_id', 'qty', 'unit_cost']))
                ->toArray(),
        ];

        $isRepost = $request->boolean('repost') && (($before['header']['status'] ?? null) === 'posted');

        $this->audit(
            action: $isRepost ? 'reposted' : 'updated',
            description: ($isRepost ? 'Reposted' : 'Updated') . ' stock entry ' . ($entryAfter->reference ?? '#'.$entryAfter->id),
            subject: $entryAfter,
            meta: [
                'repost' => $isRepost,
                'before' => $before,
                'after'  => $after,
            ]
        );

        $this->workflow('inventory', $isRepost ? 'stock_entry_reposted' : 'stock_entry_updated', $entryAfter->id);

        return response()->json(['message' => 'Stock entry updated']);
    }

    public function destroy($id)
    {
        $entry = StockEntry::with('lines')->findOrFail($id);

        $meta = [
            'id'          => $entry->id,
            'reference'   => $entry->reference,
            'status'      => $entry->status,
            'store_id'    => $entry->store_id,
            'entry_date'  => $entry->entry_date,
            'lines_count' => $entry->lines->count(),
            'lines'       => $entry->lines->map(fn ($l) => $l->only(['product_variant_id', 'qty', 'unit_cost']))->toArray(),
        ];

        DB::transaction(function () use ($entry) {
            if ($entry->status === 'posted') {
                StockService::unpostEntry($entry);
            }

            StockTransaction::whereMorphedTo('txable', $entry)->delete();
            $entry->delete();
        });

        $this->audit(
            action: 'deleted',
            description: 'Deleted stock entry ' . ($meta['reference'] ?? '#'.$meta['id']),
            subject: null,
            meta: $meta
        );

        $this->workflow('inventory', 'stock_entry_deleted', $id);

        return response()->json(['message' => 'Stock entry deleted']);
    }

    protected function validated(Request $request): array
    {
        $request->validate([
            'store_id'          => 'required|exists:location_stores,id',
            'entry_date'        => 'required|date',
            'reference'         => 'nullable|string|max:50',
            'entry_type'        => 'nullable|in:normal,cust_return',
            'supplier_id'       => 'nullable|exists:suppliers,id',
            'customer_id'       => 'nullable|exists:customers,id',
            'status'            => 'required|in:draft,approved,posted',
            'lines.variant_id'  => 'required|array|min:1',
            'lines.variant_id.*'=> 'exists:product_variants,id',
            'lines.qty'         => 'required|array',
            'lines.qty.*'       => 'integer|min:1',
            'lines.unit_cost'   => 'array',
            'lines.unit_cost.*' => 'nullable|numeric|min:0',
        ]);

        $lines = [];
        foreach ($request->input('lines.variant_id') as $idx => $variantId) {
            $lines[] = [
                'product_variant_id' => $variantId,
                'qty'                => $request->input('lines.qty')[$idx],
                'unit_cost'          => $request->input('lines.unit_cost')[$idx] ?? null,
            ];
        }

        return [
            'header' => $request->only(
                'store_id',
                'entry_date',
                'entry_type',
                'reference',
                'supplier_id',
                'customer_id',
                'status'
            ),
            'lines' => $lines,
        ];
    }

    public function show2($id)
    {
        return StockEntry::with(['lines:id,stock_entry_id,product_variant_id,qty,unit_cost'])
            ->findOrFail($id);
    }

    public function bulkDelete(Request $request)
    {
        $this->permit('inventory.stock.entries.delete');

        $ids = collect($request->input('ids', []))
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            abort(422, 'No stock entries selected.');
        }

        $entries = StockEntry::with('lines')
            ->whereIn('id', $ids)
            ->get();

        DB::transaction(function () use ($entries) {
            foreach ($entries as $entry) {
                if ($entry->status === 'posted') {
                    StockService::unpostEntry($entry);
                }

                StockTransaction::whereMorphedTo('txable', $entry)->delete();
                $entry->delete();

                $this->workflow('inventory', 'stock_entry_deleted', $entry->id);
            }
        });

        $this->audit(
            action: 'bulk.deleted',
            description: 'Bulk deleted stock entries',
            subject: null,
            meta: [
                'ids'   => $ids->toArray(),
                'count' => $ids->count(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Selected stock entries deleted.'
        ]);
    }

    public function showStockEntryLine(StockEntryLine $line)
    {
        return response()->json([
            'id'                 => $line->id,
            'stock_entry_id'     => $line->stock_entry_id,
            'product_variant_id' => $line->product_variant_id,
            'qty'                => $line->qty,
            'unit_cost'          => $line->unit_cost,
        ]);
    }

    public function stockEntryLinesDatatable()
    {
        $q = StockEntryLine::with([
            'entry.store:id,name',
            'entry.supplier:id,name',
            'product_variant.product:id,product_name'
        ])->select('stock_entry_lines.*')->orderBy('stock_entry_lines.id', 'DESC');

        return datatables()->eloquent($q)
            ->addColumn('checkbox', fn ($r) =>
                '<input type="checkbox" class="row-checkbox" value="'.$r->id.'">')
            ->addColumn('entry_id', fn ($r) => $r->stock_entry_id)
            ->addColumn('store', fn ($r) => $r->entry->store?->name)
            ->addColumn('supplier', fn ($r) => $r->entry->supplier?->name)
            ->addColumn('variant', fn ($r) => ($r->product_variant->sku ?? '').' – '.($r->product_variant->product->product_name ?? ''))
            ->addColumn('actions', function ($r) {
                $u = Auth::user();
                $btn = '';

                if ($u->can('inventory.stock.entries.edit')) {
                    $btn .= '<button class="btn btn-sm btn-primary edit-line" data-id="'.$r->id.'">Edit</button> ';
                }

                if ($u->can('inventory.stock.entries.delete')) {
                    $btn .= '<button class="btn btn-sm btn-danger delete-line" data-id="'.$r->id.'">Del</button>';
                }

                return $btn ?: '-';
            })
            ->rawColumns(['checkbox', 'actions'])
            ->make(true);
    }

    protected function validateLine($r)
    {
        return $r->validate([
            'stock_entry_id'     => 'required|exists:stock_entries,id',
            'product_variant_id' => 'required|exists:product_variants,id',
            'qty'                => 'required|integer|min:1',
            'unit_cost'          => 'nullable|numeric|min:0',
        ]);
    }

    public function stockEntryLineDatatable($entryId)
    {
        $q = StockEntryLine::query()
            ->where('stock_entry_id', $entryId)
            ->leftJoin('product_variants', 'product_variants.id', '=', 'stock_entry_lines.product_variant_id')
            ->leftJoin('products', 'products.id', '=', 'product_variants.product_id')
            ->select('stock_entry_lines.*', 'product_variants.sku as variant_sku', 'products.product_name');

        return datatables()->eloquent($q)
            ->addColumn('variant', fn ($row) => $row->variant_sku.' – '.$row->product_name)
            ->addColumn('actions', function ($row) {
                $u = Auth::user();
                if (!$u->can('inventory.stock.entries.delete')) {
                    return '-';
                }

                return '<button class="btn btn-sm btn-danger delete-line" data-id="'.$row->id.'">Del</button>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function storeStockEntryLine(Request $r)
    {
        $validated = $this->validateLine($r);

        $entry = StockEntry::with('lines')->findOrFail($validated['stock_entry_id']);
        $beforeCount = $entry->lines->count();

        if ($entry->status === 'posted' && !$r->boolean('repost')) {
            abort(422, 'Entry is posted. Set it to draft/approved or pass repost=1.');
        }

        $line = null;

        DB::transaction(function () use ($entry, $validated, &$line) {
            if ($entry->status === 'posted') {
                StockService::unpostEntry($entry);
            }

            $line = $entry->lines()->create($validated);
        });

        $afterCount = $entry->fresh('lines')->lines->count();

        $this->audit(
            action: 'line.created',
            description: 'Added line to stock entry ' . ($entry->reference ?? '#'.$entry->id),
            subject: $entry,
            meta: [
                'entry_id'            => $entry->id,
                'before_lines_count'  => $beforeCount,
                'after_lines_count'   => $afterCount,
                'line'                => $validated,
                'line_id'             => $line?->id,
            ]
        );

        $this->workflow('inventory', 'stock_entry_line_created', $entry->id);

        return response()->json(['message' => 'Line added']);
    }

    public function updateStockEntryLine(Request $r, $id)
    {
        $line = StockEntryLine::with('entry')->findOrFail($id);
        $entry = $line->entry;
        $before = $line->only(['product_variant_id', 'qty', 'unit_cost']);
        $validated = $this->validateLine($r);

        if ($entry->status === 'posted' && !$r->boolean('repost')) {
            abort(422, 'Entry is posted. Set it to draft/approved or pass repost=1.');
        }

        DB::transaction(function () use ($line, $entry, $validated) {
            if ($entry->status === 'posted') {
                StockService::unpostEntry($entry);
            }

            $line->update($validated);
        });

        $after = $line->fresh()->only(['product_variant_id', 'qty', 'unit_cost']);

        $this->audit(
            action: 'line.updated',
            description: 'Updated line #'.$line->id.' on stock entry ' . ($entry->reference ?? '#'.$entry->id),
            subject: $entry,
            meta: [
                'entry_id' => $entry->id,
                'line_id'  => $line->id,
                'before'   => $before,
                'after'    => $after,
            ]
        );

        $this->workflow('inventory', 'stock_entry_line_updated', $entry->id);

        return response()->json(['message' => 'Line updated']);
    }

    public function destroyStockEntryLine($id)
    {
        $line = StockEntryLine::with('entry')->findOrFail($id);
        $entry = $line->entry;

        $meta = [
            'entry_id' => $entry->id,
            'line_id'  => $line->id,
            'line'     => $line->only(['product_variant_id', 'qty', 'unit_cost']),
        ];

        DB::transaction(function () use ($line, $entry) {
            if ($entry->status === 'posted') {
                StockService::unpostEntry($entry);
            }

            $line->delete();
        });

        $this->audit(
            action: 'line.deleted',
            description: 'Deleted line #'.$meta['line_id'].' from stock entry ' . ($entry->reference ?? '#'.$entry->id),
            subject: $entry,
            meta: $meta
        );

        $this->workflow('inventory', 'stock_entry_line_deleted', $entry->id);

        return response()->json(['message' => 'Line deleted']);
    }

    public function stockEntryLinesIndex()
    {
        $entries = StockEntry::all();
        $variants = ProductVariant::all();

        return view('inventory.stock.entries.lines.index', [
            'entries'  => $entries,
            'variants' => $variants,
        ]);
    }

    public function stockEntryLineIndex()
    {
        $entries = StockEntry::all();
        $variants = ProductVariant::all();

        return view('inventory.stock.entries.lines.index', [
            'entries'  => $entries,
            'variants' => $variants,
        ]);
    }

    public function stockTransactionsIndex()
    {
        $stores = LocationStore::orderBy('name')->get();
        $product_variants = ProductVariant::with('product:id,product_name')
            ->orderBy('sku')
            ->get();

        return view('inventory.stock.transactions.index', compact('stores', 'product_variants'));
    }

    public function stockTransactionsDatatable()
    {
        $q = StockTransaction::with([
            'product_variant.product:id,product_name',
            'location_store:id,name'
        ])->select('stock_transactions.*');

        return datatables()->eloquent($q)
            ->addColumn('checkbox', fn ($r) =>
                '<input type="checkbox" class="row-checkbox" value="'.$r->id.'">')
            ->addColumn('variant', fn ($r) => ($r->product_variant->sku ?? '').' – '.($r->product_variant->product->product_name ?? ''))
            ->addColumn('store', fn ($r) => $r->location_store->name ?? 'N/A')
            ->addColumn('source', fn ($r) => class_basename($r->txable_type).' #'.$r->txable_id)
            ->addColumn('actions', fn ($r) =>
                '<button class="btn btn-sm btn-primary edit-transaction" data-id="'.$r->id.'">Edit</button>
                 <button class="btn btn-sm btn-danger delete-transaction" data-id="'.$r->id.'">Del</button>')
            ->rawColumns(['checkbox', 'actions'])
            ->make(true);
    }

    public function inventory_workflow()
    {
        $this->permit('inventory.stock.entries.workflow.view');

        return view('inventory.stock.workflow.index', []);
    }

    protected function permit(string $permission): void
    {
        abort_unless(auth()->check() && auth()->user()->can($permission), 403);
    }

    private function audit(string $action, ?string $description = null, $subject = null, array $meta = []): void
    {
        $module = 'inventory.stock_entries';

        auth()->user()?->audit(
            module: $module,
            action: $action,
            description: $description,
            subject: $subject,
            meta: $meta
        );
    }

    private function workflow(string $module, string $event, int $referenceId): void
    {
        try {
            if (class_exists(\App\Services\WorkflowEngine::class)) {
                \App\Services\WorkflowEngine::trigger($module, $event, $referenceId);
            }
        } catch (\Throwable $e) {
            $this->audit(
                action: 'workflow.failed',
                description: 'Workflow trigger failed for event: '.$event,
                subject: null,
                meta: [
                    'workflow_module' => $module,
                    'workflow_event'  => $event,
                    'reference_id'    => $referenceId,
                    'error'           => $e->getMessage(),
                ]
            );
        }
    }
}