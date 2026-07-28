<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;

class SupplierPaymentsController extends Controller
{
  public function index(){
    return view('finance.supplier_payments.index');
  }

  public function datatable(Request $request){
    $companyId = auth()->user()->company_id ?? 1;

    $q = DB::table('finance_supplier_payments as p')
      ->leftJoin('suppliers as s','s.id','=','p.supplier_id')
      ->leftJoin('finance_bank_accounts as b','b.id','=','p.bank_account_id')
      ->where('p.company_id',$companyId)
      ->whereNull('p.deleted_at')
      ->select([
        'p.id','p.payment_no','p.payment_date','p.amount','p.currency_code','p.status',
        'p.reference','p.memo','p.supplier_id','p.bank_account_id','p.ap_control_account_id',
        's.name as supplier_name',
        'b.name as bank_name',
      ]);

    if($request->filled('status')) $q->where('p.status',$request->status);
    if($request->filled('date_from')) $q->where('p.payment_date','>=',$request->date_from);
    if($request->filled('date_to')) $q->where('p.payment_date','<=',$request->date_to);

    if($request->filled('q')){
      $term = trim((string)$request->q);
      $q->where(function($x) use ($term){
        $x->where('p.payment_no','like',"%{$term}%")
          ->orWhere('p.reference','like',"%{$term}%")
          ->orWhere('p.memo','like',"%{$term}%")
          ->orWhere('s.name','like',"%{$term}%");
      });
    }

    $start  = (int)($request->start ?? 0);
    $length = (int)($request->length ?? 10);
    $draw   = (int)($request->draw ?? 1);

    $recordsTotal = (clone $q)->count();
    $rows = $q->orderByDesc('p.id')->offset($start)->limit($length)->get();

    $data = $rows->map(function($r){
      $badge = match($r->status){
        'posted' => '<span class="badge bg-success">POSTED</span>',
        'voided' => '<span class="badge bg-dark">VOIDED</span>',
        default  => '<span class="badge bg-secondary">DRAFT</span>',
      };

      $apLabel = null;
      if(!empty($r->ap_control_account_id)){
        $a = DB::table('finance_accounts')->where('id',$r->ap_control_account_id)->first();
        $apLabel = $a ? trim(($a->code ?? '').' - '.($a->name ?? '')) : ('GL #'.$r->ap_control_account_id);
      }

      $json = [
        'id'=>$r->id,
        'payment_no'=>$r->payment_no,
        'payment_date'=>$r->payment_date,
        'supplier_id'=>$r->supplier_id,
        'supplier_label'=>$r->supplier_name,
        'bank_account_id'=>$r->bank_account_id,
        'bank_account_label'=>$r->bank_name,
        'ap_control_account_id'=>$r->ap_control_account_id,
        'ap_control_account_label'=>$apLabel,
        'currency_code'=>$r->currency_code,
        'fx_rate'=>null,
        'reference'=>$r->reference,
        'memo'=>$r->memo,
        'amount'=>$r->amount,
        'status'=>$r->status,
      ];

      $actions = view('finance.supplier_payments.partials.actions', [
        'p' => (object)['id'=>$r->id,'status'=>$r->status],
        'json' => $json
      ])->render();

      return [
        'id'=>$r->id,
        'payment_no'=>e($r->payment_no ?? ('PAY-'.$r->id)),
        'payment_date'=>e($r->payment_date),
        'supplier'=>e($r->supplier_name ?? '—'),
        'bank'=>e($r->bank_name ?? '—'),
        'currency'=>e($r->currency_code ?? ''),
        'amount'=>number_format((float)$r->amount,2),
        'status'=>$badge,
        'actions'=>$actions,
      ];
    })->values();

    return response()->json([
      'draw'=>$draw,
      'recordsTotal'=>$recordsTotal,
      'recordsFiltered'=>$recordsTotal,
      'data'=>$data
    ]);
  }

  public function allocations($paymentId){
    $companyId = auth()->user()->company_id ?? 1;

    $p = DB::table('finance_supplier_payments')
      ->where('company_id',$companyId)->where('id',(int)$paymentId)->whereNull('deleted_at')->first();
    if(!$p) return response()->json(['message'=>'Not found'],404);

    $rows = DB::table('finance_supplier_payment_allocations as a')
      ->join('finance_supplier_bills as b','b.id','=','a.supplier_bill_id')
      ->where('a.supplier_payment_id',(int)$paymentId)
      ->orderBy('a.id')
      ->get(['a.id','a.supplier_bill_id','a.allocated_amount','b.bill_no','b.bill_date','b.total_amount']);

    return response()->json([
      'allocations' => $rows->map(fn($r)=>[
        'id'=>$r->id,
        'supplier_bill_id'=>$r->supplier_bill_id,
        'bill_label'=> "{$r->bill_no} | {$r->bill_date} | Total: ".number_format((float)$r->total_amount,2),
        'allocated_amount'=>(float)$r->allocated_amount,
      ])->values()
    ]);
  }

  public function store(Request $request){
    $companyId = auth()->user()->company_id ?? 1;
    $data = $this->validatePayment($request);

    return DB::transaction(function() use ($companyId,$data){
      $amount = $this->sumAllocations($data['allocations']);

      $id = DB::table('finance_supplier_payments')->insertGetId([
        'company_id'=>$companyId,
        'payment_no'=>$data['payment_no'] ?? null,
        'payment_date'=>$data['payment_date'],
        'supplier_id'=>(int)$data['supplier_id'],
        'currency_code'=>$data['currency_code'] ?? null,
        'fx_rate'=>$data['fx_rate'] ?? null,
        'bank_account_id'=>(int)$data['bank_account_id'],
        'ap_control_account_id'=>$data['ap_control_account_id'] ?? null,
        'reference'=>$data['reference'] ?? null,
        'memo'=>$data['memo'] ?? null,
        'amount'=>$amount,
        'status'=>'draft',
        'created_at'=>now(),
        'updated_at'=>now(),
      ]);

      $this->syncAllocations($id, $data['allocations']);

      return response()->json(['message'=>'Payment created.','id'=>$id]);
    });
  }

  public function update(Request $request, $id){
    $companyId = auth()->user()->company_id ?? 1;

    $p = DB::table('finance_supplier_payments')
      ->where('company_id',$companyId)->where('id',(int)$id)->whereNull('deleted_at')->first();
    if(!$p) return response()->json(['message'=>'Not found'],404);
    if(($p->status ?? 'draft') !== 'draft') return response()->json(['message'=>'Only draft payments can be edited.'],422);

    $data = $this->validatePayment($request);
    $amount = $this->sumAllocations($data['allocations']);

    return DB::transaction(function() use ($id,$data,$amount){
      DB::table('finance_supplier_payments')->where('id',(int)$id)->update([
        'payment_no'=>$data['payment_no'] ?? null,
        'payment_date'=>$data['payment_date'],
        'supplier_id'=>(int)$data['supplier_id'],
        'currency_code'=>$data['currency_code'] ?? null,
        'fx_rate'=>$data['fx_rate'] ?? null,
        'bank_account_id'=>(int)$data['bank_account_id'],
        'ap_control_account_id'=>$data['ap_control_account_id'] ?? null,
        'reference'=>$data['reference'] ?? null,
        'memo'=>$data['memo'] ?? null,
        'amount'=>$amount,
        'updated_at'=>now(),
      ]);

      $this->syncAllocations((int)$id, $data['allocations']);
      return response()->json(['message'=>'Payment updated.']);
    });
  }

  public function destroy($id){
    $companyId = auth()->user()->company_id ?? 1;

    $p = DB::table('finance_supplier_payments')
      ->where('company_id',$companyId)->where('id',(int)$id)->whereNull('deleted_at')->first();
    if(!$p) return response()->json(['message'=>'Not found'],404);
    if(($p->status ?? 'draft') !== 'draft') return response()->json(['message'=>'Only draft payments can be deleted.'],422);

    DB::transaction(function() use ($id){
      DB::table('finance_supplier_payments')->where('id',(int)$id)->update(['deleted_at'=>now()]);
      DB::table('finance_supplier_payment_allocations')->where('supplier_payment_id',(int)$id)->delete();
    });

    return response()->json(['message'=>'Deleted.']);
  }

  public function post($id){
    $companyId = auth()->user()->company_id ?? 1;

    $p = DB::table('finance_supplier_payments')
      ->where('company_id',$companyId)->where('id',(int)$id)->whereNull('deleted_at')->first();
    if(!$p) return response()->json(['message'=>'Not found'],404);
    if(($p->status ?? 'draft') !== 'draft') return response()->json(['message'=>'Only draft payments can be posted.'],422);
    if((float)$p->amount <= 0) return response()->json(['message'=>'Payment amount must be > 0.'],422);

    return DB::transaction(function() use ($companyId,$id){
      if(!class_exists(\Modules\Finance\Services\Posting\SupplierPaymentPostingService::class)){
        throw new \RuntimeException('SupplierPaymentPostingService not found. Please create posting service.');
      }
      $jeId = \Modules\Finance\Services\Posting\SupplierPaymentPostingService::post($companyId,(int)$id);

      DB::table('finance_supplier_payments')->where('id',(int)$id)->update([
        'status'=>'posted',
        'posted_at'=>now(),
        'posted_by'=>auth()->id(),
        'journal_entry_id'=>$jeId,
        'updated_at'=>now(),
      ]);

      return response()->json(['message'=>'Payment posted.']);
    });
  }

  public function void($id){
    $companyId = auth()->user()->company_id ?? 1;

    $p = DB::table('finance_supplier_payments')
      ->where('company_id',$companyId)->where('id',(int)$id)->whereNull('deleted_at')->first();
    if(!$p) return response()->json(['message'=>'Not found'],404);
    if(($p->status ?? '') !== 'posted') return response()->json(['message'=>'Only posted payments can be voided.'],422);

    DB::table('finance_supplier_payments')->where('id',(int)$id)->update([
      'status'=>'voided',
      'voided_at'=>now(),
      'voided_by'=>auth()->id(),
      'updated_at'=>now(),
    ]);

    return response()->json(['message'=>'Payment voided.']);
  }

  private function validatePayment(Request $request): array{
    $data = Validator::make($request->all(), [
      'payment_no' => ['nullable','string','max:50'],
      'payment_date' => ['required','date'],
      'supplier_id' => ['required','integer','exists:suppliers,id'],

      'currency_code' => ['nullable','string','size:3'],
      'fx_rate' => ['nullable','numeric','min:0.000001'],

      'bank_account_id' => ['required','integer','exists:finance_bank_accounts,id'],
      'ap_control_account_id' => ['nullable','integer','exists:finance_accounts,id'],

      'reference' => ['nullable','string','max:100'],
      'memo' => ['nullable','string','max:255'],

      'allocations' => ['required','array','min:1'],
      'allocations.*.supplier_bill_id' => ['required','integer'], // validate against your bill table in service if table name differs
      'allocations.*.allocated_amount' => ['required','numeric','min:0.01'],
    ])->validate();

    if(!empty($data['currency_code'])) $data['currency_code'] = strtoupper(trim($data['currency_code']));
    return $data;
  }

  private function sumAllocations(array $allocs): float{
    $sum = 0.0;
    foreach($allocs as $a){
      $sum += (float)($a['allocated_amount'] ?? 0);
    }
    return round($sum,2);
  }

  private function syncAllocations(int $paymentId, array $allocs): void{
    DB::table('finance_supplier_payment_allocations')->where('supplier_payment_id',$paymentId)->delete();
    $rows = [];
    foreach($allocs as $a){
      $rows[] = [
        'supplier_payment_id'=>$paymentId,
        'supplier_bill_id'=>(int)$a['supplier_bill_id'],
        'allocated_amount'=>round((float)$a['allocated_amount'],2),
        'created_at'=>now(),
        'updated_at'=>now(),
      ];
    }
    if($rows) DB::table('finance_supplier_payment_allocations')->insert($rows);
  }
}