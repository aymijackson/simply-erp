<?php

namespace Modules\Inventory\Http\Controllers;   

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Inventory\Models\Product\Brand;
use Modules\Inventory\Models\Product\BrandManufacturer;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\RawMaterial;
use Modules\Inventory\Models\ProductInstance;
use Modules\Inventory\Models\StockMovement;
use Modules\Inventory\Models\Supplier;
use Modules\Inventory\Models\Invoice;
use Modules\Inventory\Models\ItemCategory;
use Modules\Inventory\Models\ManufacturingProcess;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;

class ManufacturerController extends Controller
{
    public function index()
    {
        return view('inventory.products.manufacturers.index');
    }
     
    public function datatable(Request $request)
    {
        $manufacturers = BrandManufacturer::select(['id', 'manufacturer_name'])
            ->whereNotNull('manufacturer_name')
            ->where('manufacturer_name', '!=', '');

        return DataTables::of($manufacturers)
            ->addIndexColumn()
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
            })
            ->addColumn('manufacturer_name', function ($row) {
                return $row->manufacturer_name;
            })
            ->addColumn('action', function ($row) {
                $editBtn = '<button class="btn btn-sm btn-primary edit" data-id="' . $row->id . '" data-name="'. $row->manufacturer_name .'">Edit</button>';
                $deleteBtn = '<button class="btn btn-sm btn-danger delete" data-id="' . $row->id . '">Delete</button>';
                return $editBtn . ' ' . $deleteBtn;
            })
            ->rawColumns(['checkbox', 'action'])
            ->make(true);
    }

    public function metrics()
    {
        $total = BrandManufacturer::count();
        return response()->json(['total' => $total]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'manufacturer_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $manufacturer = BrandManufacturer::create([
            'manufacturer_name' => $request->manufacturer_name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Manufacturer created successfully.',
            'manufacturer' => $manufacturer
        ]);
    }

    public function update(Request $request, BrandManufacturer $manufacturer)
    {
        $validator = Validator::make($request->all(), [
            'manufacturer_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $manufacturer->update([
            'manufacturer_name' => $request->manufacturer_name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Manufacturer updated successfully.',
            'manufacturer' => $manufacturer
        ]);
    }

    public function destroy($id)
    {
        try {
            $manufacturer = BrandManufacturer::findOrFail($id);
            $manufacturer->delete();

            return response()->json(['success' => true, 'message' => 'Manufacturer deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error deleting manufacturer.'], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        BrandManufacturer::whereIn('id', $request->ids)->delete();
        return response()->json(['message' => 'Selected manufacturers deleted.']);
    }

}