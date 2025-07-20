<?php

namespace Modules\Inventory\Http\Controllers;   

use App\Http\Controllers\Controller;
use Modules\Inventory\Services\StockService;
use Modules\Inventory\Models\Product\Product;
use App\Models\LocationStore;
use Modules\Inventory\Models\Product\Brand;
use Modules\Inventory\Models\Product\BrandManufacturer as Manufacturer;
use Modules\Inventory\Models\Product\Category;
use Modules\Inventory\Models\Product\ProductAttribute;
use Modules\Inventory\Models\Product\ProductAttributeType;
use Modules\Inventory\Models\Product\ProductAttributeValue;
use Modules\Inventory\Models\StockEntry;
use Modules\Inventory\Models\StockEntryLine;
use Modules\Inventory\Models\StockTransaction;
use Modules\Inventory\Models\Product\ProductVariant;
use Modules\Inventory\Models\Product\Unit;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index()
    {
        return view('inventory.stock.entries.index', [
            'stores'   => LocationStore::orderBy('name')->get(),
            'variants' => ProductVariant::with('product:id,product_name')
                            ->orderBy('sku')->get(),
        ]);
    }

    public function getShelvesByStore($storeId)
    {
        $shelves = StoreShelf::where('store_id', $storeId)->get();
        return response()->json($shelves);
    }

    public function datatable()
    {
        $q = StockEntry::with('store:id,name')
        ->select('stock_entries.*');

        return datatables()->eloquent($q)
            ->addColumn('checkbox', fn($row) =>
                '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->addColumn('store_name', fn($row)=>$row->store->name)
            ->addColumn('entry_date', fn($row)=>date('d-m-Y', strtotime($row->entry_date)))
            ->addColumn('actions', fn($row)=>'
                <button class="btn btn-sm btn-primary edit-entry" data-id="'.$row->id.'">Edit</button>
                <button class="btn btn-sm btn-danger delete-entry" data-id="'.$row->id.'">Delete</button>')
            ->rawColumns(['checkbox','actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {

            $entry = StockEntry::create($data['header']);

            foreach ($data['lines'] as $line) {
                $entry->lines()->create($line);
            }

            // auto‑approve & post, or keep as draft
            if ($data['header']['status'] === 'approved') {
                StockService::postEntry($entry);
            }
        });

        return response()->json(['message'=>'Stock entry saved']);
    }

    /** GET /admin/inventory/stock/entries/{id}  */
    public function show($id)
    {
        $entry = StockEntry::with(['lines'])   // lines: id, product_variant_id, qty, unit_cost
                ->findOrFail($id);

        return response()->json([
            'id'         => $entry->id,
            'store_id'   => $entry->store_id,
            'entry_date' => $entry->entry_date,
            'reference'  => $entry->reference,
            'status'     => $entry->status,      // draft | approved
            'lines'      => $entry->lines->map(fn ($l) => [
                'product_variant_id' => $l->product_variant_id,
                'qty'                => $l->qty,
                'unit_cost'          => $l->unit_cost,
            ]),
        ]);
    }

    public function update(Request $request, $id)
    {
        $data  = $this->validated($request);
        DB::transaction(function () use ($data, $id) {

            $entry = StockEntry::findOrFail($id);
            $entry->update($data['header']);

            // remove & re‑create lines (simplest)
            $entry->lines()->delete();
            foreach ($data['lines'] as $line) {
                $entry->lines()->create($line);
            }

            StockService::postEntry($entry);                 // re‑post to ledger
        });

        return response()->json(['message'=>'Stock entry updated']);
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $entry = StockEntry::findOrFail($id);

            // remove ledger postings first
            StockTransaction::whereMorphedTo('txable', $entry)->delete();

            $entry->delete();                                // cascades lines
        });

        return response()->json(['message'=>'Stock entry deleted']);
    }

    /* ---------- tiny helper ---------- */
    protected function validated(Request $request): array
    {
        $request->validate([
            'store_id'        => 'required|exists:location_stores,id',
            'entry_date'      => 'required|date',
            'reference'       => 'nullable|string|max:50',
            'status'          => 'required|in:draft,approved',
            'lines.variant_id'=> 'required|array|min:1',
            'lines.variant_id.*' => 'exists:product_variants,id',
            'lines.qty'       => 'required|array',
            'lines.qty.*'     => 'integer|min:1',
            'lines.unit_cost' => 'array',
            'lines.unit_cost.*'=> 'nullable|numeric|min:0',
        ]);

        /* reshape arrays -> list of associative arrays */
        $lines=[];
        foreach ($request->input('lines.variant_id') as $idx=>$variantId) {
            $lines[] = [
                'product_variant_id' => $variantId,
                'qty'                => $request->input('lines.qty')[$idx],
                'unit_cost'          => $request->input('lines.unit_cost')[$idx] ?? null,
            ];
        }

        return [
            'header' => $request->only('store_id','entry_date','reference','status'),
            'lines'  => $lines,
        ];
    }

    /* fetch one entry for edit */
    public function show2($id)
    {
        return StockEntry::with(['lines:id,stock_entry_id,product_variant_id,qty,unit_cost'])
            ->findOrFail($id);
    }


    public function bulkDelete(Request $request)
    {
        StockEntry::whereIn('id', $request->ids)->delete();
        return response()->json(['success' => true, 'message' => 'Selected stock entries deleted.']);
    }

    public function stockEntryLinesDatatable()
    {
        $q = StockEntryLine::with([
                'entry.store:id,name',
                'product_variant.product:id,product_name'
            ])->select('stock_entry_lines.*');
    
        return datatables()->eloquent($q)
            ->addColumn('checkbox', fn($r)=>
                '<input type="checkbox" class="row-checkbox" value="'.$r->id.'">')
            ->addColumn('entry_id',   fn($r)=> $r->stock_entry_id)
            ->addColumn('store',   fn($r)=> $r->entry->store?->name)
            ->addColumn('variant', fn($r)=> $r->product_variant->sku.' – '.$r->product_variant->product->product_name)
            ->addColumn('actions', fn($r)=>'
                <button class="btn btn-sm btn-primary edit-line" data-id="'.$r->id.'">Edit</button>
                <button class="btn btn-sm btn-danger delete-line" data-id="'.$r->id.'">Del</button>')
            ->rawColumns(['checkbox','actions'])
            ->make(true);
    }

    public function stockEntryLineDatatable($entryId)
    {
        $q = StockEntryLine::query()
        ->where('stock_entry_id', $entryId)
        ->leftJoin('product_variants','product_variants.id','=','stock_entry_lines.product_variant_id')
        ->leftJoin('products','products.id','=','product_variants.product_id')
        ->select('stock_entry_lines.*',
                 'product_variants.sku as variant_sku',
                 'products.product_name');

    return datatables()->eloquent($q)
        ->addColumn('variant', fn($row) =>
            $row->variant_sku.' – '.$row->product_name)

        // tell DataTables how to sort the computed column
        ->orderColumn('product_variant', 'variant_sku $1')

        ->addColumn('actions', fn($row)=>
            '<button class="btn btn-sm btn-danger delete-line" data-id="'.$row->id.'">Del</button>')
        ->make(true);
    }

    public function storeStockEntryLine(Request $r, $entryId)
    {
        $r->validate([
            'variant_id' => 'required|exists:product_variants,id',
            'qty'        => 'required|integer|min:1',
            'unit_cost'  => 'nullable|numeric|min:0',
        ]);

        StockEntryLine::create([
            'stock_entry_id'    => $entryId,
            'product_variant_id'=> $r->variant_id,
            'qty'               => $r->qty,
            'unit_cost'         => $r->unit_cost,
        ]);

        // OPTIONAL: re‑post ledger here if entry already approved
        return response()->json(['message'=>'Line added']);
    }

    public function destroyStockEntryLine($id)
    {
        $line  = StockEntryLine::findOrFail($id);
        $entry = $line->entry;
        $line->delete();

        // re‑post ledger if approved
        if ($entry->status === 'approved') {
            StockService::postEntry($entry->fresh('lines'));
        }
        return response()->json(['message'=>'Line deleted']);
    }

    public function stockEntryLinesIndex()
    {
        $entries = StockEntry::all();
        $variants = ProductVariant::all();
        return view('inventory.stock.entries.lines.index', ['entries' => $entries,  
                                                            'variants' => $variants
            ]
        );
    }

    public function stockEntryLineIndex()
    {
        $entries = StockEntry::all();
        $variants = ProductVariant::all();
        return view('inventory.stock.entries.lines.index', ['entries' => $entries,  
                                                            'variants' => $variants
            ]
        );
    }

    public function stockTransactionsDatatable()
    {
        $q = StockTransaction::with(['product_variant.product:id,product_name','store:id,store_name'])
            ->select('stock_transactions.*');

        return datatables()->eloquent($q)
            ->addColumn('variant', fn($r)=>$r->product_variant->sku.' – '.$r->product_variant->product->product_name)
            ->addColumn('store',   fn($r)=>$r->store->store_name)
            ->addColumn('source',  fn($r)=> class_basename($r->txable_type).' #'.$r->txable_id)
            ->make(true);
    }

}