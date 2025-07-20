<?php

namespace Modules\Production\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Production\Models\{WorkOrder, BillOfMaterial, Routing};
use Yajra\DataTables\Facades\DataTables;

class WorkOrderController extends Controller
{
    public function index()
    {
        $boms     = BillOfMaterial::pluck('id','id');   // or name
        $routings = Routing::pluck('name','id');
        return view('production::work_orders.index', compact('boms','routings'));
    }

    public function datatable()
    {
        return DataTables::of(
            WorkOrder::with('product')->latest()
        )
        ->addColumn('checkbox', fn($wo)=>'<input type="checkbox" class="row-checkbox" value="'.$wo->id.'">')
        ->addColumn('product', fn($wo)=> $wo->product->name ?? '-')
        ->addColumn('status_badge', fn($wo)=> '<span class="badge bg-info">'.ucfirst($wo->status).'</span>')
        ->addColumn('actions', fn($wo)=> view('production::work_orders.partials.actions',compact('wo'))->render())
        ->rawColumns(['checkbox','status_badge','actions'])->make(true);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id'  => 'required|exists:products,id',
            'bill_of_material_id' => 'required|exists:bill_of_materials,id',
            'routing_id'  => 'required|exists:routings,id',
            'quantity'    => 'required|numeric|min:0.0001',
            'notes'       => 'nullable|string',
        ]);

        $data['work_order_number'] = 'WO-'.now()->timestamp;
        WorkOrder::create($data);

        return response()->json(['message' => 'Work order created']);
    }

    public function update(Request $request, WorkOrder $workOrder)
    {
        $data = $request->validate([
            'status'   => 'required|in:pending,in_progress,completed,cancelled',
            'notes'    => 'nullable|string',
            'quantity' => 'numeric|min:0.0001',
        ]);

        $workOrder->update($data);
        return response()->json(['message' => 'Work order updated']);
    }

    public function destroy(WorkOrder $workOrder)
    {
        $workOrder->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function bulkDelete(Request $request)
    {
        WorkOrder::whereIn('id', $request->ids ?? [])->delete();
        return response()->json(['message' => 'Bulk delete done']);
    }
}
