<?php

namespace Modules\Production\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Production\Models\Routing;
use Modules\Production\Models\RoutingStep;
use Yajra\DataTables\Facades\DataTables;

class RoutingStepController extends Controller
{
    public function index()
    {
        return view('production.routings.steps.index');
    }

    public function datatable(Request $r)
    {
        $q = RoutingStep::query()
            ->orderBy('sequence')
            ->select(['id','routing_id','step_name','instructions','sequence','created_at']);

        return DataTables::of($q)
            ->addIndexColumn()
            ->addColumn('checkbox', fn($row) =>
                '<input type="checkbox" class="row-check" value="'.$row->id.'">'
            )
            ->addColumn('routing', fn($row) =>
                $row->routing->name
            )
            ->addColumn('variant', fn($row) =>
                $row->routing->product_variant->product->product_name.' - '.
                $row->routing->product_variant->sku
            )
            ->addColumn('created_at', fn($row) =>
                date('d-m-Y h:i a', strtotime($row->created_at))
            )
            ->addColumn('actions', function($row){
                $data = e(json_encode($row));
                return <<<HTML
                    <button class="btn btn-sm btn-info edit-step" data-record="{$data}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger del-step" data-id="{$row->id}">
                        <i class="fas fa-trash"></i>
                    </button>
                HTML;
            })
            ->rawColumns(['checkbox','actions'])
            ->make(true);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'step_name'    => ['required','string','max:255'],
            'instructions' => ['nullable','string'],
            'sequence'     => ['nullable','integer','min:0'],
            'routing_id'     => ['nullable','integer','min:0'],
        ]);

        // default sequence to next
        if (!isset($data['sequence'])) {
            $data['sequence'] = (int) RoutingStep::where('routing_id',$data['routing_id'])->max('sequence') + 10;
        }

        //$data['routing_id'] = $routing->id;
        $step = RoutingStep::create($data);

        return response()->json(['message'=>'Step created','id'=>$step->id]);
    }

    public function update(Request $r, Routing $routing, RoutingStep $step)
    {
        abort_if($step->routing_id !== $routing->id, 404);

        $data = $r->validate([
            'step_name'    => ['required','string','max:255'],
            'instructions' => ['nullable','string'],
            'sequence'     => ['required','integer','min:0'],
        ]);

        $step->update($data);

        return response()->json(['message'=>'Step updated']);
    }

    public function destroy(RoutingStep $step)
    {
        abort_if($step->id !== $step->id, 404);
        $step->delete();

        return response()->json(['message'=>'Step deleted']);
    }

    /** Optional bulk reorder: lines: [{id,sequence},...] */
    public function reorder(Request $r, Routing $routing)
    {
        $data = $r->validate([
            'lines'   => ['required','array','min:1'],
            'lines.*.id'       => ['required','integer','exists:routing_steps,id'],
            'lines.*.sequence' => ['required','integer','min:0'],
        ]);

        DB::transaction(function() use ($data, $routing){
            foreach ($data['lines'] as $ln) {
                RoutingStep::where('id',$ln['id'])
                    ->where('routing_id',$routing->id)
                    ->update(['sequence'=>$ln['sequence']]);
            }
        });

        return response()->json(['message'=>'Order updated']);
    }
}
