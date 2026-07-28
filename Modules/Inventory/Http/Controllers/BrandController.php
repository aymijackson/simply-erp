<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Inventory\Models\Product\Brand;
use Modules\Inventory\Models\Product\BrandManufacturer;
use Yajra\DataTables\Facades\DataTables;

class BrandController extends Controller
{
    /* =========================================================
     | Audit helper (consistent with your Inventory pattern)
     ========================================================= */
    private function audit(string $action, ?string $description = null, $subject = null, array $meta = []): void
    {
        $module = 'inventory.products.brands';

        auth()->user()?->audit(
            module: $module,
            action: $action,
            description: $description,
            subject: $subject,
            meta: $meta
        );
    }

    public function index()
    {
        $manufacturers = BrandManufacturer::orderBy('name')->get();

        $this->audit(
            action: 'view.index',
            description: 'Viewed brands index',
            subject: null,
            meta: ['manufacturers_count' => $manufacturers->count()]
        );

        return view('inventory.products.manufacturers.brands.index', compact('manufacturers'));
    }

    public function datatable(Request $request)
    {
        if ($request->ajax()) {
            $brands = Brand::with('manufacturer')->latest();

            return DataTables::of($brands)
                ->addIndexColumn()
                ->addColumn('checkbox', fn($row) =>
                    '<input type="checkbox" class="brand-checkbox" value="'.$row->id.'">'
                )
                ->addColumn('manufacturer_name', fn($row) =>
                    $row->manufacturer->name ?? '-' // ✅ fixed: use name, not manufacturer_name
                )
                ->addColumn('action', function ($row) {
                    return '
                        <button class="btn btn-sm btn-warning edit-btn"
                            data-id="'.$row->id.'"
                            data-brand_code="'.e($row->brand_code).'"
                            data-brand_name="'.e($row->brand_name).'"
                            data-manufacturer_id="'.$row->manufacturer_id.'">
                            Edit
                        </button>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="'.$row->id.'">Delete</button>
                    ';
                })
                ->rawColumns(['checkbox', 'action'])
                ->make(true);
        }
    }

    public function metrics()
    {
        $total = Brand::count();
        
        return response()->json(['total' => $total]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'brand_code'      => 'required|string|max:255|unique:brands,brand_code',
            'brand_name'      => 'required|string|max:255',
            'manufacturer_id' => 'nullable|integer|exists:brand_manufacturers,id',
        ]);

        $brand = Brand::create($validated);

        $manufacturerName = optional(BrandManufacturer::find($brand->manufacturer_id))->name;

        $this->audit(
            action: 'created',
            description: 'Created brand '.$brand->brand_name.' ('.$brand->brand_code.')',
            subject: $brand,
            meta: [
                'id'              => $brand->id,
                'brand_code'      => $brand->brand_code,
                'brand_name'      => $brand->brand_name,
                'manufacturer_id' => $brand->manufacturer_id,
                'manufacturer'    => $manufacturerName,
            ]
        );

        return response()->json(['success' => true, 'message' => 'Brand created.']);
    }

    public function update(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'brand_code'      => 'required|string|max:255|unique:brands,brand_code,' . $brand->id,
            'brand_name'      => 'required|string|max:255',
            'manufacturer_id' => 'nullable|integer|exists:brand_manufacturers,id',
        ]);

        // BEFORE snapshot
        $before = $brand->only(['brand_code', 'brand_name', 'manufacturer_id']);
        $before['manufacturer'] = optional($brand->manufacturer)->name;

        $brand->update($validated);

        // AFTER snapshot
        $brandFresh = $brand->fresh(['manufacturer']);
        $after = $brandFresh->only(['brand_code', 'brand_name', 'manufacturer_id']);
        $after['manufacturer'] = optional($brandFresh->manufacturer)->name;

        $this->audit(
            action: 'updated',
            description: 'Updated brand '.$brandFresh->brand_name.' ('.$brandFresh->brand_code.')',
            subject: $brandFresh,
            meta: [
                'before' => $before,
                'after'  => $after,
            ]
        );

        return response()->json(['success' => true, 'message' => 'Brand updated.']);
    }

    public function destroy(Brand $brand)
    {
        $meta = $brand->load('manufacturer')->only(['id', 'brand_code', 'brand_name', 'manufacturer_id']);
        $meta['manufacturer'] = optional($brand->manufacturer)->name;

        $brand->delete();

        $this->audit(
            action: 'deleted',
            description: 'Deleted brand '.$meta['brand_name'].' ('.$meta['brand_code'].')',
            subject: null,
            meta: $meta
        );

        return response()->json(['success' => true, 'message' => 'Brand deleted.']);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'exists:brands,id',
        ]);

        $brands = Brand::with('manufacturer')->whereIn('id', $request->ids)->get();

        Brand::whereIn('id', $request->ids)->delete();

        $this->audit(
            action: 'bulk.deleted',
            description: 'Bulk deleted brands',
            subject: null,
            meta: [
                'count' => $brands->count(),
                'ids'   => $brands->pluck('id'),
                'items' => $brands->map(fn($b) => [
                    'id'           => $b->id,
                    'brand_code'   => $b->brand_code,
                    'brand_name'   => $b->brand_name,
                    'manufacturer' => optional($b->manufacturer)->name,
                ])->values()->toArray(),
            ]
        );

        return response()->json(['success' => true, 'message' => 'Selected brands deleted.']);
    }
}
