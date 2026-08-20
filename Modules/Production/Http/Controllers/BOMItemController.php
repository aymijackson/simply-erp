<?php

namespace Modules\Production\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Production\Models\BomItem;
use Modules\Production\Models\BomHeader;
use Modules\Inventory\Models\Product\ProductVariant;
use Yajra\DataTables\Facades\DataTables;

class BOMItemController extends Controller
{
    private function companyId(Request $r): int
    {
        return (int) ($r->user()->company_id ?? 1);
    }

    /**
     * Show the BOM Items page.
     */
    public function index(Request $request)
    {
        $boms = BomHeader::where('company_id', $this->companyId($request))->get();          // for dropdown
        $variants = ProductVariant::with('product')->get();     // for dropdown
        return view('production.boms.items.index', compact('boms', 'variants'));
    }

    /**
     * Return JSON for DataTables.
     */
    public function datatable(Request $request)
    {
        $query = BomItem::with(['bom', 'product_variant'])
            ->whereHas('bom', fn ($q) => $q->where('company_id', $this->companyId($request)))
            ->select('bom_items.*');

        return DataTables::of($query)
            ->addIndexColumn()               // <<< add this
            ->addColumn('checkbox', fn($item) =>
                '<input type="checkbox" class="row‑checkbox" value="'.$item->id.'">'
            )
            ->addColumn('bom_code', fn($item) =>
                $item->bom->bom_code ?? '-'
            )
            ->addColumn('product_variant', fn($item) =>
                $item->product_variant->product->product_name ?? '-'
            )
            ->addColumn('variant_sku', fn($item) =>
                $item->product_variant->sku ?? '-'
            )
            ->addColumn('product_name', fn($item) =>
                $item->product_variant->product->product_name ?? '-'
            )
            ->addColumn('qty_per_parent', fn($item) =>
                number_format($item->qty_per_parent, 2) ?? '-'
            )
            ->addColumn('actions', function ($row) {
                $data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                return '
                    <button class="btn btn-sm btn-info edit-bom-item" data-record="'.$data.'"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-danger delete-bom-item" data-id="'.$row->id.'"><i class="fas fa-trash-alt"></i></button>
                ';
            })
            ->rawColumns(['checkbox','actions'])
            ->make(true);

    }

    /**
     * Store a new BOM item.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'bom_header_id'      => 'required|exists:bom_headers,id',
            'product_variant_id' => 'required|exists:product_variants,id',
            'qty_per_parent'     => 'required|numeric|min:0.0001',
        ]);

        $bom = BomHeader::findOrFail($data['bom_header_id']);
        abort_unless($bom->company_id == $this->companyId($request), 404);

        BomItem::create($data);

        return response()->json(['message' => 'BOM item added successfully']);
    }

    /**
     * Update an existing BOM item.
     */
    public function update(Request $request, BomItem $bom_item)
    {
        abort_unless($bom_item->bom->company_id == $this->companyId($request), 404);

        $data = $request->validate([
            'bom_header_id'      => 'required|exists:bom_headers,id',
            'product_variant_id' => 'required|exists:product_variants,id',
            'qty_per_parent'     => 'required|numeric|min:0.0001',
        ]);

        $bom = BomHeader::findOrFail($data['bom_header_id']);
        abort_unless($bom->company_id == $this->companyId($request), 404);

        $bom_item->update($data);

        return response()->json(['message' => 'BOM item updated successfully']);
    }

    /**
     * Delete a single BOM item.
     */
    public function destroy(Request $request, BomItem $bom_item)
    {
        abort_unless($bom_item->bom->company_id == $this->companyId($request), 404);

        $bom_item->delete();
        return response()->json(['message' => 'BOM item deleted']);
    }

    /**
     * Bulk delete selected BOM items.
     */
    public function bulkDelete(Request $request)
    {
        BomItem::whereIn('id', $request->ids ?? [])
            ->whereHas('bom', fn ($q) => $q->where('company_id', $this->companyId($request)))
            ->delete();
        return response()->json(['message' => 'Selected BOM items deleted']);
    }
}
