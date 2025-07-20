<?php
namespace Modules\Production\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Production\Models\Routing;
use Yajra\DataTables\Facades\DataTables;

class RoutingController extends Controller
{
    public function index()
    {
        return view('production::routings.index');
    }

    public function datatable()
    {
        return DataTables::of(Routing::query())
            ->addColumn('checkbox', fn($r)=>'<input type="checkbox" class="row-checkbox" value="'.$r->id.'">')
            ->addColumn('steps', fn($r)=> $r->steps->count())
            ->addColumn('actions', fn($r)=> view(
                'production::routings.partials.actions', compact('r')
            )->render())
            ->rawColumns(['checkbox','actions'])->make(true);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        Routing::create($data);
        return response()->json(['message' => 'Routing added']);
    }

    public function update(Request $request, Routing $routing)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $routing->update($data);
        return response()->json(['message' => 'Routing updated']);
    }

    public function destroy(Routing $routing)
    {
        $routing->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function bulkDelete(Request $request)
    {
        Routing::whereIn('id', $request->ids ?? [])->delete();
        return response()->json(['message' => 'Bulk delete done']);
    }
}
