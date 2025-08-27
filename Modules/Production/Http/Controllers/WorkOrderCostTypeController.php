<?php

// Modules/Production/Http/Controllers/WorkOrderCostTypeController.php
namespace Modules\Production\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use Modules\Production\Models\WorkOrderCostType;
use Modules\Inventory\Models\Product\Unit;

class WorkOrderCostTypeController
{
    /** Central list of categories used in validation/UI */
    private array $cats = ['labour','machine','logistics','fuel','service','overhead','other'];


    public function index()
    {
        // preload a few units for the dropdown (or load via select2 if you prefer)
        $units = Unit::orderBy('name')->limit(100)->get(['id','name','symbol']);
        return view('production.work_orders.costs.types.index', [
            'categories' => $this->cats,
            'units'      => $units,
        ]);
    }

    public function datatable(Request $r)
    {
        $q = WorkOrderCostType::query()->with('unit');

        return DataTables::of($q)
            ->addIndexColumn()
            ->addColumn('unit', fn($t) => $t->unit?->name ?? '—')
            ->addColumn('is_active_badge', fn($t) =>
                $t->is_active
                  ? '<span class="badge bg-success">Active</span>'
                  : '<span class="badge bg-secondary">Disabled</span>'
            )
            ->addColumn('actions', function ($t) {
                $data = e(json_encode([
                    'id' => $t->id, 'code'=>$t->code, 'name'=>$t->name, 'category'=>$t->category,
                    'default_unit_id'=>$t->default_unit_id, 'is_active'=>$t->is_active
                ]));
                return <<<HTML
                    <button class="btn btn-sm btn-info edit-type" data-record="{$data}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger del-type" data-id="{$t->id}">
                        <i class="fas fa-trash"></i>
                    </button>
                HTML;
            })
            ->rawColumns(['is_active_badge','actions'])
            ->make(true);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'code'            => ['required','max:50','alpha_dash', 'unique:work_order_cost_types,code'],
            'name'            => ['required','max:120'],
            'category'        => ['required', Rule::in($this->cats)],
            'default_unit_id' => ['nullable','exists:units,id'],
            'is_active'       => ['sometimes','boolean'],
        ]);

        $data['is_active'] = (bool)($data['is_active'] ?? true);

        $type = WorkOrderCostType::create($data);

        return response()->json(['message'=>'Type created','id'=>$type->id]);
    }

    public function update(Request $r, WorkOrderCostType $type)
    {
        $data = $r->validate([
            'code'            => ['required','max:50','alpha_dash', Rule::unique('work_order_cost_types','code')->ignore($type->id)],
            'name'            => ['required','max:120'],
            'category'        => ['required', Rule::in($this->cats)],
            'default_unit_id' => ['nullable','exists:units,id'],
            'is_active'       => ['sometimes','boolean'],
        ]);
        $type->update($data);

        return response()->json(['message'=>'Type updated']);
    }

    public function destroy(WorkOrderCostType $type)
    {
        // Optionally guard if referenced by lines
        $type->delete();
        return response()->json(['message'=>'Deleted']);
    }

    /** Select2 endpoint: returns [{id,text}] */
    public function select2(Request $r)
    {
        $q = trim($r->q ?? '');
        $rows = WorkOrderCostType::query()
            ->when($q, fn($qq)=>$qq->where('name','like',"%$q%")->orWhere('code','like',"%$q%"))
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(20)
            ->get();

        return $rows->map(fn($t)=>[
            'id'   => $t->id,
            'text' => "{$t->name} ({$t->code})"
        ]);
    }
}

