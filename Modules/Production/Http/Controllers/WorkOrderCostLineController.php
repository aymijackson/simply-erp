<?php

// app/Http/Controllers/Production/WorkOrderCostLineController.php
namespace Modules\Production\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Modules\Production\Models\{WorkOrder, WorkOrderCostLine, WorkOrderCostType};
use Modules\Inventory\Models\Product\Unit;

class WorkOrderCostLineController
{
    public function datatable(int $wo)
    {
        $q = DB::table('work_order_cost_lines as c')
           ->join('work_order_cost_types as t','t.id','=','c.work_order_cost_type_id')
           ->leftJoin('units as u','u.id','=','c.unit_id')
           ->where('c.work_order_id', $wo)
           ->selectRaw("
              c.id, c.qty, c.rate, (c.qty*c.rate) AS amount, c.note,
              t.name as type_name, t.category, u.name as unit_name, c.created_at
           ");

        return DataTables::of($q)
            ->addIndexColumn()
            ->addColumn('qty_fmt',   fn($x)=> number_format($x->qty, 6))
            ->addColumn('rate_fmt',  fn($x)=> number_format($x->rate, 4))
            ->addColumn('amount_fmt',fn($x)=> number_format($x->amount, 2))
            ->addColumn('actions', fn($x)=> '
                <button class="btn btn-sm btn-info edit-cost" data-record="'.e(json_encode($x)).'"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-danger del-cost" data-id="'.$x->id.'"><i class="fas fa-trash"></i></button>
            ')
            ->with([
                'totals'=>[
                    'amount'=> (float) DB::table('work_order_cost_lines')->where('work_order_id',$wo)->sum(DB::raw('qty*rate')),
                ]
            ])
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function store(int $wo, Request $r)
    {
        $data = $r->validate([
            'work_order_cost_type_id' => ['required','integer','exists:work_order_cost_types,id'],
            'unit_id'                 => ['nullable','integer','exists:units,id'],
            'qty'                     => ['required','numeric','min:0.000001'],
            'rate'                    => ['required','numeric','min:0'],
            'note'                    => ['nullable','string','max:5000'],
        ]);

        DB::table('work_order_cost_lines')->insert([
            'work_order_id'          => $wo,
            'work_order_cost_type_id'=> $data['work_order_cost_type_id'],
            'unit_id'                => $data['unit_id'] ?? null,
            'qty'                    => $data['qty'],
            'rate'                   => $data['rate'],
            'note'                   => $data['note'] ?? null,
            'created_by'             => auth()->id(),
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        return response()->json(['message'=>'Cost added.']);
    }

    public function update(int $line, Request $r)
    {
        $data = $r->validate([
            'work_order_cost_type_id' => ['required','integer','exists:work_order_cost_types,id'],
            'unit_id'                 => ['nullable','integer','exists:units,id'],
            'qty'                     => ['required','numeric','min:0.000001'],
            'rate'                    => ['required','numeric','min:0'],
            'note'                    => ['nullable','string','max:5000'],
        ]);

        DB::table('work_order_cost_lines')->where('id', $line)->update([
            'work_order_cost_type_id'=> $data['work_order_cost_type_id'],
            'unit_id'                => $data['unit_id'] ?? null,
            'qty'                    => $data['qty'],
            'rate'                   => $data['rate'],
            'note'                   => $data['note'] ?? null,
            'updated_at'             => now(),
        ]);

        return response()->json(['message'=>'Cost updated.']);
    }

    public function destroy(int $line)
    {
        DB::table('work_order_cost_lines')->where('id',$line)->delete();
        return response()->json(['message'=>'Cost removed.']);
    }
}
