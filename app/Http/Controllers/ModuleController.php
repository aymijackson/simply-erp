<?php

namespace App\Http\Controllers;

use App\Models\Module;
use Illuminate\Http\Request;
use DataTables;

class ModuleController extends Controller
{
    // Display page or return JSON for DataTables
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $modules = Module::select('id','name','slug');
            return Datatables::of($modules)
                ->addColumn('checkbox', fn($m) => 
                    '<input type="checkbox" name="ids[]" value="'.$m->id.'">')
                ->addColumn('actions', fn($m) => '
                    <button class="btn btn-warning btn-sm edit-module" data-id="'.$m->id.'">Edit</button>
                    <button class="btn btn-danger btn-sm delete-module" data-id="'.$m->id.'">Delete</button>
                ')
                ->rawColumns(['checkbox','actions'])
                ->make(true);
        }

        return view('modules.index');
    }

    // Store new module
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:modules,name',
            'slug' => 'required|unique:modules,slug',
        ]);

        Module::create($request->only('name','slug'));

        return response()->json(['message'=>'Module created successfully!']);
    }

    // Fetch single module for edit
    public function edit($id)
    {
        $module = Module::findOrFail($id);
        return response()->json(['module'=>$module]);
    }

    // Update module
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:modules,name,'.$id,
            'slug' => 'required|unique:modules,slug,'.$id,
        ]);

        $mod = Module::findOrFail($id);
        $mod->update($request->only('name','slug'));

        return response()->json(['message'=>'Module updated successfully!']);
    }

    // Delete single module
    public function destroy($id)
    {
        Module::findOrFail($id)->delete();
        return response()->json(['message'=>'Module deleted successfully!']);
    }

    // Bulk delete
    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;
        if (empty($ids) || ! is_array($ids)) {
            return response()->json(['message'=>'No modules selected'],422);
        }

        Module::whereIn('id',$ids)->delete();
        return response()->json(['message'=>'Selected modules deleted successfully!']);
    }
}
