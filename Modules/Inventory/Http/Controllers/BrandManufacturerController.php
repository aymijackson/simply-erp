<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Inventory\Models\BrandManufacturer;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class BrandManufacturerController extends Controller
{
    public function index()
    {
        return view('inventory::brand-manufacturers.index');
    }

    public function list(Request $request)
    {
        $query = BrandManufacturer::select('id','name','website','created_at');
        return DataTables::of($query)
            ->addColumn('checkbox', fn($m) => "<input type=\"checkbox\" name=\"ids[]\" value=\"{$m->id}\">")
            ->addColumn('actions', fn($m) =>
                "<button class=\"btn btn-warning btn-sm edit-btn\" data-id=\"{$m->id}\">Edit</button>
                 <button class=\"btn btn-danger btn-sm delete-btn\" data-id=\"{$m->id}\">Delete</button>"
            )
            ->rawColumns(['checkbox','actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|unique:brand_manufacturers,name',
            'website' => 'nullable|url',
        ]);

        BrandManufacturer::create($data);

        return response()->json(['message'=>'Brand Manufacturer created']);
    }

    public function edit($id)
    {
        $bm = BrandManufacturer::findOrFail($id);
        return response()->json(['brandManufacturer' => $bm]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name'    => "required|string|unique:brand_manufacturers,name,{$id}",
            'website' => 'nullable|url',
        ]);

        BrandManufacturer::findOrFail($id)->update($data);

        return response()->json(['message'=>'Brand Manufacturer updated']);
    }

    public function destroy($id)
    {
        BrandManufacturer::findOrFail($id)->delete();
        return response()->json(['message'=>'Brand Manufacturer deleted']);
    }

    public function bulkDelete(Request $request)
    {
        BrandManufacturer::whereIn('id', $request->input('ids', []))->delete();
        return response()->json(['message'=>'Selected Brand Manufacturers deleted']);
    }
}
