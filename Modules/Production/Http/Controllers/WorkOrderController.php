<?php

namespace Modules\Production\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;
use Modules\Production\Services\WorkOrderService;
use Modules\Production\Models\WorkOrder;

class WorkOrderController extends Controller
{
    public function __construct(private WorkOrderService $svc) {}

    private function companyId(Request $r): int
    {
        return (int) ($r->user()->company_id ?? 1);
    }

    public function routingStepsDatatable(Request $request, WorkOrder $work_order)
    {
        abort_unless($work_order->company_id == $this->companyId($request), 404);

        // No routing → no steps
        if (empty($work_order->routing_id)) {
            return DataTables::of(collect())->make(true);
        }

        // Detect the join column on work_order_steps
        $joinCol = null;
        if (Schema::hasTable('work_order_steps')) {
            if (Schema::hasColumn('work_order_steps', 'routing_step_id')) {
                $joinCol = 'routing_step_id';
            } elseif (Schema::hasColumn('work_order_steps', 'step_id')) {
                $joinCol = 'step_id'; // legacy name
            }
        }

        $q = DB::table('routing_steps as rs')
            ->where('rs.routing_id', $work_order->routing_id);

        if ($joinCol) {
            $q->leftJoin('work_order_steps as ws', function ($j) use ($work_order, $joinCol) {
                    $j->on("ws.$joinCol", '=', 'rs.id')
                    ->where('ws.work_order_id', '=', $work_order->id);
                })
            ->select([
                'rs.id as routing_step_id',
                'rs.routing_id',
                'rs.sequence',
                'rs.step_name',
                'rs.instructions',
                'ws.id as wo_step_id',
                'ws.status',
                'ws.started_at',
                'ws.completed_at',
            ]);
        } else {
            // Table/column missing: still show routing steps
            $q->select([
                'rs.id as routing_step_id',
                'rs.routing_id',
                'rs.sequence',
                'rs.step_name',
                'rs.instructions',
                DB::raw('NULL as wo_step_id'),
                DB::raw('NULL as status'),
                DB::raw('NULL as started_at'),
                DB::raw('NULL as completed_at'),
            ]);
        }

        $q->orderBy('rs.sequence')->orderBy('rs.id');

        return DataTables::of($q)
            ->addIndexColumn()
            ->editColumn('status', fn ($r) => $r->status ?? 'pending')
            ->addColumn('started', fn ($r) => $r->started_at ? date('Y-m-d H:i', strtotime($r->started_at)) : '—')
            ->addColumn('completed', fn ($r) => $r->completed_at ? date('Y-m-d H:i', strtotime($r->completed_at)) : '—')
            ->addColumn('actions', function ($r) {
                $data = e(json_encode($r));
                $start   = '<button class="btn btn-sm btn-primary start-step" data-record="'.$data.'"><i class="fas fa-play"></i></button>';
                $complete= '<button class="btn btn-sm btn-success complete-step" data-record="'.$data.'" '.($r->wo_step_id ? '' : 'disabled').'><i class="fas fa-check"></i></button>';
                $delete  = '<button class="btn btn-sm btn-danger delete-wo-step" data-id="'.($r->wo_step_id ?? 0).'" '.($r->wo_step_id ? '' : 'disabled').'><i class="fas fa-trash"></i></button>';
                return $start.' '.$complete.' '.$delete;
            })
            ->rawColumns(['actions'])
            ->make(true);
    }


    public function index()
    {
        return view('production.work_orders.index');
    }

    public function datatable(Request $r)
    {
        $q = DB::table('work_orders as w')
            ->leftJoin('product_variants as v', 'v.id', '=', 'w.product_variant_id')
            ->leftJoin('products as p', 'p.id', '=', 'v.product_id')
            ->leftJoin('bom_headers as b', 'b.id', '=', 'w.bom_header_id')
            ->leftJoin('routings as rt', 'rt.id', '=', 'w.routing_id')
            ->where('w.company_id', $this->companyId($r))
            ->selectRaw("
                w.id, w.status, w.quantity_to_produce, w.start_date, w.end_date,
                v.sku as variant_sku, COALESCE(p.product_name,'') as product_name,
                b.bom_code, rt.name as routing_name, w.created_at
            ");

        return DataTables::of($q)
            ->addIndexColumn()
            ->addColumn('variant', fn($x) => $x->variant_sku ?: '—')
            ->addColumn('product', fn($x) => $x->product_name ?: '—')
            ->addColumn('bom',     fn($x) => $x->bom_code ? '#'.$x->bom_code : '—')
            ->addColumn('routing', fn($x) => $x->routing_name ?: '—')
            ->addColumn('qty',     fn($x) => number_format($x->quantity_to_produce, 4))
            ->addColumn('status_badge', function($x){
                $map = [
                    'draft'=>'secondary','released'=>'info','in_progress'=>'warning',
                    'completed'=>'success','closed'=>'dark'
                ];
                $c = $map[$x->status] ?? 'secondary';
                return '<span class="badge bg-'.$c.' text-white">'.ucfirst($x->status).'</span>';
            })
            ->addColumn('actions', function($x){
                $show = route('admin.production.work-orders.show', $x->id);
                return '<a href="'.$show.'" class="btn btn-sm btn-outline-primary">Open</a>';
            })
            ->rawColumns(['status_badge','actions'])
            ->make(true);
    }

    public function create()
    {
        // Work orders are created via the "New Work Order" modal on the index page, not a
        // dedicated form - there is no production.work_orders.create view.
        return redirect()->route('admin.production.work-orders.index');
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'work_order_number'   => ['required','string','max:50'],
            'product_variant_id' => ['required','integer','exists:product_variants,id'],
            'bom_header_id'      => ['required','integer','exists:bom_headers,id'],
            'routing_id'         => ['required','integer','exists:routings,id'],
            'quantity_to_produce'=> ['required','numeric','min:0.0001'],
            'start_date' => ['nullable','date'],
            'end_date' => ['nullable','date'],
        ]);

        $id = DB::table('work_orders')->insertGetId([
            'company_id'          => $this->companyId($r),
            'work_order_number'  => $data['work_order_number'],
            'product_variant_id'  => $data['product_variant_id'],
            'bom_header_id'       => $data['bom_header_id'],
            'routing_id'          => $data['routing_id'],
            'quantity_to_produce' => $data['quantity_to_produce'],
            'status'              => 'draft',
            'start_date'              => isset($data['start_date']) ? $data['start_date'] : NULL,
            'end_date'              => isset($data['end_date']) ? $data['end_date'] : NULL,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        return redirect()->route('admin.production.work-orders.show', $id)
            ->with('success','Work Order created.');
    }

    public function update(Request $r, int $wo)
    {
        $row = DB::table('work_orders')->where('id', $wo)->where('company_id', $this->companyId($r))->firstOrFail();

        $data = $r->validate([
            'work_order_number'   => ['required','string','max:50'],
            'product_variant_id' => ['required','integer','exists:product_variants,id'],
            'bom_header_id'      => ['required','integer','exists:bom_headers,id'],
            'routing_id'         => ['required','integer','exists:routings,id'],
            'quantity_to_produce'=> ['required','numeric','min:0.0001'],
            'start_date' => ['nullable','date'],
            'end_date' => ['nullable','date'],
        ]);

        if ($row->status !== 'draft') {
            return response()->json(['message' => 'Only draft Work Orders can be edited.'], 422);
        }

        DB::table('work_orders')->where('id', $wo)->update([
            'work_order_number'  => $data['work_order_number'],
            'product_variant_id'  => $data['product_variant_id'],
            'bom_header_id'       => $data['bom_header_id'],
            'routing_id'          => $data['routing_id'],
            'quantity_to_produce' => $data['quantity_to_produce'],
            'start_date'          => $data['start_date'] ?? null,
            'end_date'            => $data['end_date'] ?? null,
            'updated_at'          => now(),
        ]);

        return response()->json(['message' => 'Work Order updated.']);
    }

    public function destroy(Request $r, int $wo)
    {
        $row = DB::table('work_orders')->where('id', $wo)->where('company_id', $this->companyId($r))->firstOrFail();

        if ($row->status !== 'draft') {
            return response()->json(['message' => 'Only draft Work Orders can be deleted.'], 422);
        }

        DB::table('work_orders')->where('id', $wo)->delete();

        return response()->json(['message' => 'Work Order deleted.']);
    }

    public function show(Request $r, int $wo)
    {
        $woRow = DB::table('work_orders as w')
            ->leftJoin('product_variants as v', 'v.id', '=', 'w.product_variant_id')
            ->leftJoin('products as p', 'p.id', '=', 'v.product_id')
            ->leftJoin('bom_headers as b', 'b.id', '=', 'w.bom_header_id')
            ->leftJoin('routings as rt', 'rt.id', '=', 'w.routing_id')
            ->where('w.id', $wo)
            ->where('w.company_id', $this->companyId($r))
            ->selectRaw("
               w.*, v.sku as variant_sku, COALESCE(p.product_name,'') as product_name,
               b.bom_code, rt.name as routing_name
            ")
            ->firstOrFail();

        return view('production.work_orders.show', ['wo' => $woRow]);
    }

    public function release(Request $r, int $wo)
    {
        $row = DB::table('work_orders')->where('id', $wo)->where('company_id', $this->companyId($r))->firstOrFail();
        $this->svc->release($row);
        return response()->json(['message'=>'Work Order released.']);
    }

    public function start(Request $r, int $wo)
    {
        $row = DB::table('work_orders')->where('id', $wo)->where('company_id', $this->companyId($r))->firstOrFail();
        $this->svc->start($row);
        return response()->json(['message'=>'Work Order started.']);
    }

    public function complete(Request $r, int $wo)
    {
        $row = DB::table('work_orders')->where('id', $wo)->where('company_id', $this->companyId($r))->firstOrFail();
        $res = $this->svc->complete($row);
        return response()->json(['message'=>'Work Order completed.', 'summary'=>$res]);
    }

    public function close(Request $r, int $wo)
    {
        $row = DB::table('work_orders')->where('id', $wo)->where('company_id', $this->companyId($r))->firstOrFail();
        $this->svc->close($row);
        return response()->json(['message'=>'Work Order closed.']);
    }
}
