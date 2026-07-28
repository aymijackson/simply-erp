<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class APAgingController extends Controller
{
    public function index()
    {
        return view('finance.ap_aging.index');
    }

    public function datatable(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $asAt = $request->input('as_at') ?: date('Y-m-d');
        $supplierId = (int)($request->supplier_id ?? 0);

        // days overdue = DATEDIFF(asAt, due_date)
        // negative => not due yet (Current)
        $q = DB::table('finance_supplier_bills as b')
            ->join('suppliers as s','s.id','=','b.supplier_id')
            ->where('b.company_id',$companyId)
            ->whereNull('b.deleted_at')
            ->where('b.status','posted')
            ->where('b.balance_due','>',0)
            ->when($supplierId > 0, fn($x)=>$x->where('b.supplier_id',$supplierId))
            ->select([
                'b.id','b.bill_no','b.bill_date','b.due_date','b.currency_code','b.balance_due',
                's.id as supplier_id','s.name as supplier_name',
                DB::raw("DATEDIFF('{$asAt}', b.due_date) as days_overdue"),
            ]);

        $rows = $q->orderBy('s.name')->orderBy('b.due_date')->get();

        // Group for summary
        $summary = [
            'current'=>0,'d1_30'=>0,'d31_60'=>0,'d61_90'=>0,'d91_120'=>0,'d120_plus'=>0,'total'=>0
        ];

        $data = $rows->map(function($r) use (&$summary){
            $days = (int)$r->days_overdue;
            $bal = (float)$r->balance_due;

            $bucket = 'Current';
            if($days <= 0) { $summary['current'] += $bal; $bucket='Current'; }
            elseif($days <= 30){ $summary['d1_30'] += $bal; $bucket='1-30'; }
            elseif($days <= 60){ $summary['d31_60'] += $bal; $bucket='31-60'; }
            elseif($days <= 90){ $summary['d61_90'] += $bal; $bucket='61-90'; }
            elseif($days <= 120){ $summary['d91_120'] += $bal; $bucket='91-120'; }
            else { $summary['d120_plus'] += $bal; $bucket='120+'; }

            $summary['total'] += $bal;

            return [
                'supplier' => e($r->supplier_name),
                'bill_no' => e($r->bill_no ?? ('BILL-'.$r->id)),
                'bill_date' => e($r->bill_date ?? ''),
                'due_date' => e($r->due_date ?? ''),
                'currency' => e($r->currency_code ?? ''),
                'balance_due' => number_format($bal, 2),
                'days_overdue' => $days,
                'bucket' => $bucket,
            ];
        });

        return response()->json([
            'ok'=>true,
            'as_at'=>$asAt,
            'summary'=>array_map(fn($v)=>round($v,2), $summary),
            'data'=>$data->values(),
        ]);
    }
}