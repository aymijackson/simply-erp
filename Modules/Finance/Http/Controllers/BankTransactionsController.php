<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Finance\Services\Posting\BankTransactionPostingService;

class BankTransactionsController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id ?? 1;

        return view('finance.bank_transactions.index', compact('companyId'));
    }

    public function datatable(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $q = DB::table('finance_bank_transactions as t')
            ->leftJoin('finance_bank_accounts as b', 'b.id', '=', 't.bank_account_id')
            ->leftJoin('finance_bank_accounts as tb', 'tb.id', '=', 't.to_bank_account_id')
            ->where('t.company_id', $companyId)
            ->whereNull('t.deleted_at')
            ->select([
                't.id','t.txn_no','t.txn_date','t.type','t.status','t.currency_code','t.total_amount',
                't.reference','t.description','t.bank_account_id','t.to_bank_account_id',
                't.journal_entry_id','t.posted_at',
                'b.name as bank_name',
                'tb.name as to_bank_name',
            ]);

        if ($request->filled('type')) $q->where('t.type', $request->type);
        if ($request->filled('status')) $q->where('t.status', $request->status);
        if ($request->filled('q')) {
            $term = trim((string)$request->q);
            $q->where(function($x) use ($term){
                $x->where('t.txn_no','like',"%{$term}%")
                  ->orWhere('t.reference','like',"%{$term}%")
                  ->orWhere('t.description','like',"%{$term}%")
                  ->orWhere('t.currency_code','like',"%{$term}%")
                  ->orWhere('t.total_amount','like',"%{$term}%");
            });
        }

        $start  = (int)($request->start ?? 0);
        $length = (int)($request->length ?? 10);
        $draw   = (int)($request->draw ?? 1);

        $recordsTotal = (clone $q)->count();

        $orderColIndex = $request->input('order.0.column');
        $orderDir      = $request->input('order.0.dir', 'desc');

        $cols = [
            0 => 't.id',
            1 => 't.txn_date',
            2 => 't.type',
            3 => 'b.name',
            4 => 't.currency_code',
            5 => 't.total_amount',
            6 => 't.status',
        ];

        if ($orderColIndex !== null && isset($cols[(int)$orderColIndex])) {
            $q->orderBy($cols[(int)$orderColIndex], $orderDir === 'asc' ? 'asc' : 'desc');
        } else {
            $q->orderBy('t.id','desc');
        }

        $rows = $q->offset($start)->limit($length)->get();

        $data = $rows->map(function($r){
            $json = [
                'id' => $r->id,
                'txn_no' => $r->txn_no,
                'txn_date' => $r->txn_date,
                'type' => $r->type,
                'status' => $r->status,
                'bank_account_id' => $r->bank_account_id,
                'bank_account_label' => $r->bank_name ?? null,
                'to_bank_account_id' => $r->to_bank_account_id,
                'to_bank_account_label' => $r->to_bank_name ?? null,
                'currency_code' => $r->currency_code,
                'total_amount' => (string)($r->total_amount ?? '0.00'),
                'reference' => $r->reference,
                'description' => $r->description,
            ];

            return [
                'id' => $r->id,
                'date' => e($r->txn_date),
                'type' => e(strtoupper($r->type)),
                'currency' => e($r->currency_code ?? ''),
                'bank' => e($r->bank_name ?? '—') . ($r->type === 'transfer' ? (' → ' . e($r->to_bank_name ?? '—')) : ''),
                'amount' => number_format((float)($r->total_amount ?? 0), 2),
                'status' => match ((string)$r->status) {
                    'posted' => '<span class="badge bg-success">POSTED</span>',
                    'void'   => '<span class="badge bg-danger">VOID</span>',
                    default  => '<span class="badge bg-secondary">DRAFT</span>',
                },
                'actions' => view('finance.bank_transactions.partials.actions', ['json'=>$json, 'row'=>$r])->render(),
            ];
        })->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $data = $this->validateTxn($request, $companyId);

        return DB::transaction(function() use ($companyId, $data){
            $id = DB::table('finance_bank_transactions')->insertGetId([
                'company_id' => $companyId,
                'txn_no' => $data['txn_no'] ?? null,
                'txn_date' => $data['txn_date'],
                'type' => $data['type'],
                'status' => 'draft',

                'bank_account_id' => $data['bank_account_id'],
                'to_bank_account_id' => $data['to_bank_account_id'] ?? null,

                'currency_code' => $data['currency_code'] ?? null,
                'exchange_rate' => $data['exchange_rate'] ?? 1.00000000,

                'reference' => $data['reference'] ?? null,
                'description' => $data['description'] ?? null,

                'total_amount' => 0.00, // computed from lines on save
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->syncLines($id, $data['type'], $data['lines'] ?? []);
            $this->recalcTotal($id);

            return response()->json(['message'=>'Bank transaction created.','id'=>$id]);
        });
    }

    public function update(Request $request, $txn)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $id = (int)$txn;

        $row = DB::table('finance_bank_transactions')
            ->where('company_id', $companyId)
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$row) return response()->json(['message'=>'Not found.'], 404);
        if ($row->status === 'posted') return response()->json(['message'=>'Cannot edit a posted transaction. Unpost first.'], 422);
        if ($row->status === 'void') return response()->json(['message'=>'Cannot edit a void transaction.'], 422);

        $data = $this->validateTxn($request, $companyId);

        return DB::transaction(function() use ($id, $data){
            DB::table('finance_bank_transactions')
                ->where('id', $id)
                ->update([
                    'txn_no' => $data['txn_no'] ?? null,
                    'txn_date' => $data['txn_date'],
                    'type' => $data['type'],
                    'bank_account_id' => $data['bank_account_id'],
                    'to_bank_account_id' => $data['to_bank_account_id'] ?? null,
                    'currency_code' => $data['currency_code'] ?? null,
                    'exchange_rate' => $data['exchange_rate'] ?? 1.00000000,
                    'reference' => $data['reference'] ?? null,
                    'description' => $data['description'] ?? null,
                    'updated_at' => now(),
                ]);

            $this->syncLines($id, $data['type'], $data['lines'] ?? []);
            $this->recalcTotal($id);

            return response()->json(['message'=>'Bank transaction updated.']);
        });
    }

    public function destroy($txn)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $id = (int)$txn;

        $row = DB::table('finance_bank_transactions')
            ->where('company_id', $companyId)
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$row) return response()->json(['message'=>'Not found.'], 404);
        if ($row->status === 'posted') return response()->json(['message'=>'Cannot delete a posted transaction. Unpost first.'], 422);

        DB::table('finance_bank_transactions')->where('id', $id)->update(['deleted_at'=>now()]);
        return response()->json(['message'=>'Deleted.']);
    }

    public function bulkDelete(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $ids = $request->input('ids', []);
        if (!is_array($ids) || !count($ids)) return response()->json(['message'=>'No rows selected.'], 422);

        $ids = array_map('intval', $ids);

        $postedCount = DB::table('finance_bank_transactions')
            ->where('company_id', $companyId)
            ->whereIn('id', $ids)
            ->where('status', 'posted')
            ->count();

        if ($postedCount > 0) {
            return response()->json(['message'=>'Some selected transactions are POSTED. Unpost them first.'], 422);
        }

        DB::table('finance_bank_transactions')
            ->where('company_id', $companyId)
            ->whereIn('id', $ids)
            ->update(['deleted_at'=>now()]);

        return response()->json(['message'=>'Selected transactions deleted.']);
    }

    public function post($txn)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $jeId = BankTransactionPostingService::post($companyId, (int)$txn);

        return response()->json(['message'=>"Posted successfully (JE ID: {$jeId}).", 'journal_entry_id'=>$jeId]);
    }

    public function unpost($txn)
    {
        $companyId = auth()->user()->company_id ?? 1;
        BankTransactionPostingService::unpost($companyId, (int)$txn);

        return response()->json(['message'=>"Unposted successfully."]);
    }

    // ===== Lookups for Select2 =====

    public function bankAccounts(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $term = trim((string)$request->get('q',''));

        $rows = DB::table('finance_bank_accounts')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->where('is_active', 1)
            ->when($term !== '', function($x) use ($term){
                $x->where(function($w) use ($term){
                    $w->where('name','like',"%{$term}%")
                      ->orWhere('bank_name','like',"%{$term}%")
                      ->orWhere('account_number','like',"%{$term}%");
                });
            })
            ->orderBy('name')
            ->limit(50)
            ->get(['id','name','type','currency_code','bank_name','account_number']);

        return response()->json([
            'results' => $rows->map(function($r){
                $meta = trim(($r->bank_name ?: '').' '.($r->account_number ?: ''));
                $meta = $meta ? (' - '.$meta) : '';
                return [
                    'id' => $r->id,
                    'text' => $r->name.' ['.$r->type.']'.($r->currency_code ? ' '.$r->currency_code : '').$meta,
                ];
            })->values()
        ]);
    }

    public function glAccounts(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $term = trim((string)$request->get('q',''));

        $q = DB::table('finance_accounts')
            ->where('company_id', $companyId)
            ->where('is_active', 1)
            ->orderBy('code')
            ->select(['id','code','name']);

        if ($term !== '') {
            $q->where(function($x) use ($term){
                $x->where('code','like',"%{$term}%")->orWhere('name','like',"%{$term}%");
            });
        }

        $rows = $q->limit(50)->get();

        return response()->json([
            'results' => $rows->map(fn($r)=>[
                'id' => $r->id,
                'text' => $r->code.' - '.$r->name,
            ])->values(),
        ]);
    }

    // ===== Internals =====

    private function validateTxn(Request $request, int $companyId): array
    {
        $rules = [
            'txn_no' => ['nullable','string','max:50'],
            'txn_date' => ['required','date'],
            'type' => ['required','in:deposit,withdrawal,transfer'],

            'bank_account_id' => ['required','integer','exists:finance_bank_accounts,id'],
            'to_bank_account_id' => ['nullable','integer'],

            'currency_code' => ['nullable','string','size:3','exists:currencies,code'],
            'exchange_rate' => ['nullable','numeric'],

            'reference' => ['nullable','string','max:120'],
            'description' => ['nullable','string','max:255'],

            // split lines
            'lines' => ['nullable','array'],
            'lines.*.account_id' => ['required_with:lines','integer','exists:finance_accounts,id'],
            'lines.*.memo' => ['nullable','string','max:255'],
            'lines.*.amount' => ['required_with:lines','numeric','min:0.01'],
        ];

        $data = Validator::make($request->all(), $rules)->validate();

        $data['txn_no'] = isset($data['txn_no']) ? trim((string)$data['txn_no']) : null;
        $data['reference'] = isset($data['reference']) ? trim((string)$data['reference']) : null;
        $data['description'] = isset($data['description']) ? trim((string)$data['description']) : null;

        $data['currency_code'] = !empty($data['currency_code']) ? strtoupper(trim((string)$data['currency_code'])) : null;
        $data['exchange_rate'] = isset($data['exchange_rate']) && $data['exchange_rate'] !== null ? (float)$data['exchange_rate'] : 1.0;

        $data['bank_account_id'] = (int)$data['bank_account_id'];
        $data['to_bank_account_id'] = !empty($data['to_bank_account_id']) ? (int)$data['to_bank_account_id'] : null;

        // transfer rules
        if ($data['type'] === 'transfer') {
            if (empty($data['to_bank_account_id'])) {
                abort(response()->json(['message'=>'Destination bank account is required for transfer.'], 422));
            }
            if ((int)$data['to_bank_account_id'] === (int)$data['bank_account_id']) {
                abort(response()->json(['message'=>'Transfer destination cannot be the same bank account.'], 422));
            }
        } else {
            // non-transfer MUST have lines
            $lines = $data['lines'] ?? [];
            if (!is_array($lines) || count($lines) < 1) {
                abort(response()->json(['message'=>'Split lines are required (add at least one line).'], 422));
            }
        }

        return $data;
    }

    private function syncLines(int $txnId, string $type, array $lines): void
    {
        // Transfers don’t use split lines
        DB::table('finance_bank_transaction_lines')->where('bank_transaction_id', $txnId)->delete();

        if ($type === 'transfer') return;

        $ins = [];
        $i = 1;

        foreach ($lines as $ln) {
            $acct = (int)$ln['account_id'];
            $amt  = round((float)$ln['amount'], 2);
            if ($amt <= 0) continue;

            // B-mode rule:
            // deposit => splits are CREDIT
            // withdrawal => splits are DEBIT
            $debit  = ($type === 'withdrawal') ? $amt : 0.00;
            $credit = ($type === 'deposit') ? $amt : 0.00;

            $ins[] = [
                'bank_transaction_id' => $txnId,
                'line_no' => $i++,
                'account_id' => $acct,
                'memo' => isset($ln['memo']) ? trim((string)$ln['memo']) : null,
                'debit' => $debit,
                'credit' => $credit,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!count($ins)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'lines' => ['No valid split lines found.']
            ]);
        }

        DB::table('finance_bank_transaction_lines')->insert($ins);
    }

    private function recalcTotal(int $txnId): void
    {
        $tx = DB::table('finance_bank_transactions')->where('id', $txnId)->first();
        if (!$tx) return;

        if ($tx->type === 'transfer') {
            // total stays as user set in UI (we’ll store it in update/store via hidden)
            // but if blank, keep 0
            return;
        }

        $sum = DB::table('finance_bank_transaction_lines')
            ->where('bank_transaction_id', $txnId)
            ->selectRaw('SUM(debit) as sD, SUM(credit) as sC')
            ->first();

        $amt = 0.00;
        if ($tx->type === 'deposit') $amt = (float)($sum->sC ?? 0);
        if ($tx->type === 'withdrawal') $amt = (float)($sum->sD ?? 0);

        DB::table('finance_bank_transactions')->where('id', $txnId)->update([
            'total_amount' => round($amt, 2),
            'updated_at' => now(),
        ]);
    }
}