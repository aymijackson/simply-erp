<?php

namespace Modules\Inventory\Http\Controllers;   

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Services\TransferService;
use App\Http\Controllers\Controller;
use Modules\Inventory\Models\StockLevel;
use Modules\Inventory\Models\Product\ProductVariant;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use Modules\Inventory\Models\StockTransfer;
use App\Models\LocationStore;
use App\Models\AuditLog;

class StockTransferController extends Controller
{
    public function __construct(private TransferService $svc) { }

     /**
     * GET /admin/api/variants?q=abc
     * Returns lightweight JSON array for Select2
     */
    public function fetch_variants(Request $req)
    {
        $q = $req->get('q', '');

        $variants = ProductVariant::query()
            ->with('product:id,product_name')   // eager ‑ tiny payload
            ->when($q, function ($qry) use ($q) {
                $qry->where('sku',   'like', "%$q%")
                     ->orWhereHas('product', fn($p) => $p->where('product_name','like',"%$q%"));
            })
            ->orderBy('sku')
            ->limit(30)
            ->get(['id','product_id','sku']);   // select only needed cols

        /* transform to slim JSON that Select2 expects:
           [ { id:123, sku:'ABC‑001', product_name:'Widget A' }, … ]
        */
        return $variants->map(fn($v)=>[
            'id'            => $v->id,
            'sku'           => $v->sku,
            'product_name'  => $v->product->product_name,
        ]);
    }

    public function index()
    {
        return view('inventory.stock.transfers.index');
    }

    public function datatable()
    {
        $q = StockTransfer::with('fromStore','toStore')
                ->select('*');
        return datatables()->eloquent($q)
            ->addColumn('stores', fn($r)=>$r->fromStore->name.' → '.$r->toStore->name)
            ->addColumn('lines',  fn($r)=>$r->lines()->count())
            ->addColumn('actions', fn($r)=>'
               <a class="btn btn-sm btn-primary" href="'.route('admin.inventory.stock.transfers.edit',$r).'">Open</a>')
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function create()
    {
        return view('inventory.stock.transfers.edit', [
            'transfer' => null,
            'stores'   => LocationStore::all()
        ]);
    }

    public function store(Request $r)
    {
        $transfer = $this->svc->create(
            $r->only('from_store_id','to_store_id','reason','requested_by'),
            $r->lines ?? []
        );
    
        $this->audit(
            action: 'create',
            description: 'Created stock transfer draft',
            subject: $transfer,
            meta: [
                'from_store_id' => $transfer->from_store_id,
                'to_store_id'   => $transfer->to_store_id,
                'lines_count'   => $transfer->lines()->count(),
            ]
        );
    
        return redirect()
            ->route('admin.inventory.stock.transfers.edit', $transfer)
            ->with('ok', 'Draft created');
    }
    
    public function update(Request $r, StockTransfer $transfer)
    {
        abort_if($transfer->status !== 'draft', 403, 'Only draft transfers can be edited');
    
        $r->validate([
            'reason' => 'nullable|string|max:255',
            'lines' => 'required|array|min:1',
            'lines.*.product_variant_id' => 'required|integer',
            'lines.*.qty' => 'required|numeric|min:0.0001',
        ]);
    
        $this->svc->updateDraft(
            $transfer,
            $r->only('reason'),
            $r->lines
        );
        return back()->with('ok', 'Draft updated');
    }

    public function edit(StockTransfer $transfer)
    {
        $this->audit(
            action: 'view',
            description: 'Viewed stock transfer',
            subject: $transfer
        );
    
        return view('inventory.stock.transfers.edit', [
            'transfer' => $transfer->load('lines.variant'),
            'stores'   => LocationStore::all()
        ]);
    }


    public function post(Request $r, StockTransfer $transfer)
    {
        abort_if($transfer->status !== 'draft', 403, 'Only draft transfers can be posted.');
    
        // 1) Persist any changes user made on the form (qty, variant)
        $lines = $r->input('lines', []);
    
        DB::transaction(function () use ($transfer, $lines) {
            // Simple approach: wipe + recreate for drafts
            $transfer->lines()->delete();
            $transfer->lines()->createMany($lines);
        });
    
        // 2) Reload fresh lines from DB so service uses current saved values
        $transfer->load('lines.variant', 'fromStore', 'toStore');
    
        // 3) Post (creates stock transactions etc.)
        $this->svc->post($transfer);
    
        return redirect()
            ->route('admin.inventory.stock.transfers.edit', $transfer)
            ->with('ok', 'Transfer posted successfully. It is now locked.');
    }


    public function destroy(StockTransfer $transfer)
    {
        abort_if($transfer->status === 'posted', 403, 'Posted transfers cannot be deleted');
    
        $this->audit(
            action: 'delete',
            description: 'Deleted stock transfer draft',
            subject: $transfer,
            meta: [
                'from_store_id' => $transfer->from_store_id,
                'to_store_id'   => $transfer->to_store_id,
                'status'        => $transfer->status,
            ]
        );
    
        $transfer->delete();
    
        return response()->json(['ok' => 'Stock transfer deleted successfully']);
    }

    public function fetch_stores(Request $req): JsonResponse
    {
        $exclude = $req->integer('exclude');

        $stores = LocationStore::query()
            ->when($exclude, fn($q) => $q->where('id', '!=', $exclude))
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(
            $stores->map(fn($s) => ['id' => $s->id, 'text' => $s->name])
        );
    }

    /**
     * GET /admin/api/store-variants?store_id=1&q=abc
     * Returns only variants with stock > 0 in the selected store (Select2 friendly)
     */
    public function fetch_store_variants(Request $req): JsonResponse
    {
        $storeId = $req->integer('store_id');
        $q       = $req->get('q', '');

        if (!$storeId) {
            return response()->json([]); // no store selected yet
        }

        // CHANGE THIS COLUMN NAME if yours differs:
        $qtyCol = 'qty_on_hand';

        // Get variant IDs that are in stock in this store
        $inStockVariantIds = StockLevel::query()
            ->where('location_store_id', $storeId)
            ->where($qtyCol, '>', 0)
            ->pluck('product_variant_id');

        $variants = ProductVariant::query()
            ->with('product:id,product_name')
            ->whereIn('id', $inStockVariantIds)
            ->when($q, function ($qry) use ($q) {
                $qry->where('sku', 'like', "%$q%")
                    ->orWhereHas('product', fn($p) => $p->where('product_name','like',"%$q%"));
            })
            ->orderBy('sku')
            ->limit(30)
            ->get(['id','product_id','sku']);

        // Also return available qty so you can display it in dropdown and set max
        $qtyByVariant = StockLevel::query()
            ->where('location_store_id', $storeId)
            ->whereIn('product_variant_id', $variants->pluck('id'))
            ->pluck($qtyCol, 'product_variant_id');

        return response()->json(
            $variants->map(function ($v) use ($qtyByVariant) {
                $avail = (float) ($qtyByVariant[$v->id] ?? 0);
                return [
                    'id'           => $v->id,
                    'sku'          => $v->sku,
                    'product_name' => $v->product->product_name,
                    'available'    => $avail,
                    // Select2 can use 'text' directly
                    'text'         => "{$v->sku} — {$v->product->product_name} (Avail: {$avail})",
                ];
            })
        );
    }

    /**
     * GET /admin/api/store-variant-qty?store_id=1&variant_id=10
     * Returns available quantity for selected variant in the selected store
     */
    public function fetch_store_variant_qty(Request $req): JsonResponse
    {
        $storeId   = $req->integer('store_id');
        $variantId = $req->integer('variant_id');

        if (!$storeId || !$variantId) {
            return response()->json(['available' => 0]);
        }

        // CHANGE THIS COLUMN NAME if yours differs:
        $qtyCol = 'quantity';

        $available = (float) StockLevel::query()
            ->where('store_id', $storeId)
            ->where('variant_id', $variantId)
            ->value($qtyCol);

        return response()->json(['available' => max(0, $available)]);
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
            'module'       => 'inventory.stock.transfers',
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
