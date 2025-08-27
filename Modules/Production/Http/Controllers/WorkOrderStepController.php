<?php

namespace Modules\Production\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class WorkOrderStepController extends Controller
{
    public function datatable(int $wo)
    {
        $q = DB::table('work_order_steps')
            ->where('work_order_id', $wo)
            ->orderBy('sequence');

        return DataTables::of($q)
            ->addIndexColumn()
            ->addColumn('status_badge', function($x){
                $map = ['pending'=>'secondary','in_progress'=>'warning','done'=>'success','blocked'=>'danger'];
                $c = $map[$x->status] ?? 'secondary';
                return '<span class="badge bg-'.$c.'">'.ucfirst($x->status).'</span>';
            })
            ->addColumn('actions', function($x){
                $btns = [];
                if ($x->status === 'pending') {
                    $btns[] = '<button class="btn btn-sm btn-outline-primary step-start" data-id="'.$x->id.'">Start</button>';
                } elseif ($x->status === 'in_progress') {
                    $btns[] = '<button class="btn btn-sm btn-outline-success step-finish" data-id="'.$x->id.'">Finish</button>';
                }
                return implode(' ', $btns);
            })
            ->rawColumns(['status_badge','actions'])
            ->make(true);
    }

    public function start(int $step)
    {
        $row = DB::table('work_order_steps')->where('id',$step)->firstOrFail();
        if ($row->status !== 'pending') abort(422,'Step must be pending.');
        DB::table('work_order_steps')->where('id',$step)->update([
            'status'    => 'in_progress',
            'started_at'=> now(),
            'updated_at'=> now(),
        ]);
        return response()->json(['message'=>'Step started.']);
    }

    public function finish(int $step)
    {
        $row = DB::table('work_order_steps')->where('id',$step)->firstOrFail();
        if ($row->status !== 'in_progress') abort(422,'Step must be in progress.');
        DB::table('work_order_steps')->where('id',$step)->update([
            'status'       => 'done',
            'ended_at'     => now(),
            'actual_minutes'=> DB::raw("IFNULL(actual_minutes, TIMESTAMPDIFF(MINUTE, started_at, NOW()))"),
            'updated_at'   => now(),
        ]);
        return response()->json(['message'=>'Step finished.']);
    }
}
