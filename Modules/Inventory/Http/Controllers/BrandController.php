<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Inventory\Models\Product\Brand;
use Modules\Inventory\Models\Product\BrandManufacturer;
use Yajra\DataTables\Facades\DataTables;

class BrandController extends Controller
{
    public function index()
    {
        $manufacturers = BrandManufacturer::all();
        return view('inventory.products.manufacturers.brands.index', compact('manufacturers'));
    }

    public function datatable(Request $request)
    {
        if ($request->ajax()) {
            $brands = Brand::with('manufacturer')->latest();

            return DataTables::of($brands)
                ->addIndexColumn()
                ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="brand-checkbox" value="'.$row->id.'">')
                ->addColumn('manufacturer_name', function ($row) {
                    return $row->manufacturer->manufacturer_name ?? '-';
                })
                ->addColumn('action', function ($row) {
                    return '
                        <button class="btn btn-sm btn-warning edit-btn"
                            data-id="'.$row->id.'"
                            data-brand_code="'.$row->brand_code.'"
                            data-brand_name="'.$row->brand_name.'"
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
        $request->validate([
            'brand_code' => 'required|string|unique:brands',
            'brand_name' => 'required|string',
            'manufacturer_id' => 'nullable|integer'
        ]);

        Brand::create($request->only(['brand_code', 'brand_name', 'manufacturer_id']));

        return response()->json(['success' => true, 'message' => 'Brand created.']);
    }

    public function update(Request $request, Brand $brand)
    {
        $request->validate([
            'brand_code' => 'required|string|unique:brands,brand_code,' . $brand->id,
            'brand_name' => 'required|string',
            'manufacturer_id' => 'nullable|integer'
        ]);

        $brand->update($request->only(['brand_code', 'brand_name', 'manufacturer_id']));

        return response()->json(['success' => true, 'message' => 'Brand updated.']);
    }

    public function destroy(Brand $brand)
    {
        $brand->delete();
        return response()->json(['success' => true, 'message' => 'Brand deleted.']);
    }

    public function bulkDelete(Request $request)
    {
        Brand::whereIn('id', $request->ids)->delete();
        return response()->json(['success' => true, 'message' => 'Selected brands deleted.']);
    }
}
