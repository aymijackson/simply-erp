<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BankAccountsController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id ?? 1;

        return view('finance.bank_accounts.index', compact('companyId'));
    }

    public function datatable(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
    
        $q = DB::table('finance_bank_accounts as b')
            ->leftJoin('companies as co', 'co.id', '=', 'b.company_id')
            ->leftJoin('finance_accounts as a', 'a.id', '=', 'b.gl_account_id')
            ->leftJoin('banks as bk', 'bk.id', '=', 'b.bank_id')
            ->leftJoin('currencies as c', 'c.code', '=', 'b.currency_code')
            ->where('b.company_id', $companyId)
            ->whereNull('b.deleted_at')
            ->select([
                'b.id',
                'b.company_id',
                'b.name',
                'b.type',
                'b.currency_code',
                'b.bank_id',
                'b.bank_name',
                'b.account_number',
                'b.sort_code',
                'b.iban',
                'b.swift',
                'b.opening_balance',
                'b.opening_balance_date',
                'b.gl_account_id',
                'b.is_active',
                'b.notes',
    
                'co.name as company_name',
    
                'a.code as gl_code',
                'a.name as gl_name',
    
                'bk.name as bank_lookup_name',
    
                'c.name as currency_name',
            ]);
    
        // Filters
        if ($request->filled('type')) {
            $q->where('b.type', $request->type);
        }
    
        if ($request->filled('active')) {
            $q->where('b.is_active', (int) $request->active);
        }
    
        if ($request->filled('q')) {
            $term = trim((string) $request->q);
    
            $q->where(function ($x) use ($term) {
                $x->where('b.name', 'like', "%{$term}%")
                  ->orWhere('co.name', 'like', "%{$term}%")
                  ->orWhere('b.bank_name', 'like', "%{$term}%")
                  ->orWhere('bk.name', 'like', "%{$term}%")
                  ->orWhere('b.account_number', 'like', "%{$term}%")
                  ->orWhere('b.currency_code', 'like', "%{$term}%")
                  ->orWhere('a.code', 'like', "%{$term}%")
                  ->orWhere('a.name', 'like', "%{$term}%");
            });
        }
    
        $start  = (int) ($request->start ?? 0);
        $length = (int) ($request->length ?? 10);
        $draw   = (int) ($request->draw ?? 1);
    
        $recordsTotal = (clone $q)->count();
    
        // Blade columns:
        // 0 checkbox, 1 ID, 2 Name, 3 Type, 4 Currency, 5 GL, 6 Opening, 7 Status, 8 Actions
        $orderColIndex = $request->input('order.0.column');
        $orderDir      = $request->input('order.0.dir', 'asc');
    
        $columns = [
            1 => 'b.id',
            2 => 'b.name',
            3 => 'b.company_name',
            4 => 'b.type',
            5 => 'b.currency_code',
            6 => 'a.code',
            7 => 'b.opening_balance',
            8 => 'b.is_active',
        ];
    
        if ($orderColIndex !== null && isset($columns[(int) $orderColIndex])) {
            $q->orderBy($columns[(int) $orderColIndex], $orderDir === 'desc' ? 'desc' : 'asc');
        } else {
            $q->orderBy('b.id', 'desc');
        }
    
        $rows = $q->offset($start)->limit($length)->get();
    
        $data = $rows->map(function ($r) {
            $bankLabel = $r->bank_lookup_name ?: ($r->bank_name ?: null);
    
            $currencyLabel = $r->currency_code
                ? ($r->currency_code . ($r->currency_name ? ' - ' . $r->currency_name : ''))
                : null;
    
            $glLabel = $r->gl_account_id
                ? trim(($r->gl_code ?? '') . ' - ' . ($r->gl_name ?? ''), ' -')
                : null;
    
            $json = [
                'id' => $r->id,
                'company_id' => $r->company_id,
                'company_name' => $r->company_name,
    
                'name' => $r->name,
                'type' => $r->type,
    
                'currency_code' => $r->currency_code,
                'currency_code_label' => $currencyLabel,
    
                'bank_id' => $r->bank_id,
                'bank_label' => $bankLabel,
                'bank_name' => $r->bank_name,
    
                'account_number' => $r->account_number,
                'sort_code' => $r->sort_code,
                'iban' => $r->iban,
                'swift' => $r->swift,
    
                'opening_balance' => (string) ($r->opening_balance ?? '0.00'),
                'opening_balance_date' => $r->opening_balance_date,
    
                'gl_account_id' => $r->gl_account_id,
                'gl_label' => $glLabel,
    
                'is_active' => (int) $r->is_active,
                'notes' => $r->notes,
            ];
    
            return [
                'id' => $r->id,
                'name' => e($r->name),
                'company_name' => e($r->company_name ?? 'Not Available'),
                'type' => e($r->type),
                'currency' => e($r->currency_code ?? ''),
                'bank' => e($bankLabel ?? '—'),
                'gl' => $glLabel ? e($glLabel) : '—',
                'opening' => number_format((float) ($r->opening_balance ?? 0), 2),
                'active' => (int) $r->is_active
                    ? '<span class="badge bg-success">ACTIVE</span>'
                    : '<span class="badge bg-secondary">DISABLED</span>',
                'actions' => view('finance.bank_accounts.partials.actions', ['json' => $json])->render(),
            ];
        })->values();
    
        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => $data,
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

        $rows = $q->limit(30)->get();

        return response()->json([
            'results' => $rows->map(fn($r)=>[
                'id' => $r->id,
                'text' => $r->code.' - '.$r->name,
            ])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $data = $this->validateBankAccount($request);
    
        // If bank_id is set, sync bank_name from banks table (keeps legacy display consistent)
        if (!empty($data['bank_id'])) {
            $bkName = DB::table('banks')->where('id', (int)$data['bank_id'])->value('name');
            if ($bkName) $data['bank_name'] = $bkName;
        }
    
        $id = DB::table('finance_bank_accounts')->insertGetId(array_merge($data, [
            'company_id' => $companyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    
        return response()->json(['message'=>'Bank/Cash account created.', 'id'=>$id]);
    }

    public function update(Request $request, $bankAccount)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $data = $this->validateBankAccount($request);
    
        $row = DB::table('finance_bank_accounts')
            ->where('company_id', $companyId)
            ->where('id', (int)$bankAccount)
            ->whereNull('deleted_at')
            ->first();
    
        if (!$row) return response()->json(['message'=>'Bank/Cash account not found.'], 404);
    
        // Sync bank_name from lookup when bank_id present
        if (!empty($data['bank_id'])) {
            $bkName = DB::table('banks')->where('id', (int)$data['bank_id'])->value('name');
            if ($bkName) $data['bank_name'] = $bkName;
        }
    
        DB::table('finance_bank_accounts')
            ->where('id', (int)$bankAccount)
            ->update(array_merge($data, ['updated_at'=>now()]));
    
        return response()->json(['message'=>'Bank/Cash account updated.']);
    }

    public function destroy($bankAccount)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $row = DB::table('finance_bank_accounts')
            ->where('company_id', $companyId)
            ->where('id', (int)$bankAccount)
            ->whereNull('deleted_at')
            ->first();

        if (!$row) return response()->json(['message'=>'Not found.'], 404);

        // if you later add reconciliation/transactions table, block delete when used
        DB::table('finance_bank_accounts')
            ->where('id', (int)$bankAccount)
            ->update(['deleted_at'=>now()]);

        return response()->json(['message'=>'Deleted.']);
    }

    public function setDefault(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $id = (int) $request->input('id');

        $row = DB::table('finance_bank_accounts')
            ->where('company_id', $companyId)
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Bank/Cash account not found.'], 404);
        }

        DB::transaction(function () use ($companyId, $id) {
            DB::table('finance_bank_accounts')
                ->where('company_id', $companyId)
                ->update(['is_default' => false]);

            DB::table('finance_bank_accounts')
                ->where('id', $id)
                ->update(['is_default' => true, 'updated_at' => now()]);
        });

        return response()->json(['message' => 'Default bank/cash account updated.']);
    }

    public function bulkDelete(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $ids = $request->input('ids', []);
        if (!is_array($ids) || !count($ids)) return response()->json(['message'=>'No rows selected.'], 422);

        $ids = array_map('intval', $ids);

        DB::table('finance_bank_accounts')
            ->where('company_id', $companyId)
            ->whereIn('id', $ids)
            ->update(['deleted_at'=>now()]);

        return response()->json(['message'=>'Selected accounts deleted.']);
    }

    private function validateBankAccount(Request $request): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:bank,cash,wallet,mobile_money'],
    
            'currency_code' => ['nullable', 'string', 'size:3', 'exists:currencies,code'],
    
            'bank_id' => ['nullable', 'integer', 'exists:banks,id'],
            'bank_name' => ['nullable', 'string', 'max:255'],
    
            'account_number' => ['nullable', 'string', 'max:255'],
            'sort_code' => ['nullable', 'string', 'max:255'],
            'iban' => ['nullable', 'string', 'max:255'],
            'swift' => ['nullable', 'string', 'max:255'],
    
            'opening_balance' => ['nullable', 'numeric'],
            'opening_balance_date' => ['nullable', 'date'],
    
            'gl_account_id' => ['nullable', 'integer', 'exists:finance_accounts,id'],
    
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    
        $data = Validator::make($request->all(), $rules)->validate();
    
        $data['name'] = trim($data['name']);
        $data['currency_code'] = !empty($data['currency_code'])
            ? strtoupper(trim($data['currency_code']))
            : null;
    
        $data['bank_id'] = !empty($data['bank_id']) ? (int) $data['bank_id'] : null;
        $data['bank_name'] = !empty($data['bank_name']) ? trim((string) $data['bank_name']) : null;
    
        $data['account_number'] = !empty($data['account_number']) ? trim((string) $data['account_number']) : null;
        $data['sort_code'] = !empty($data['sort_code']) ? trim((string) $data['sort_code']) : null;
        $data['iban'] = !empty($data['iban']) ? trim((string) $data['iban']) : null;
        $data['swift'] = !empty($data['swift']) ? trim((string) $data['swift']) : null;
    
        $data['opening_balance'] = isset($data['opening_balance']) ? (float) $data['opening_balance'] : 0.00;
        $data['gl_account_id'] = !empty($data['gl_account_id']) ? (int) $data['gl_account_id'] : null;
    
        $data['is_active'] = $request->boolean('is_active');
        $data['notes'] = !empty($data['notes']) ? trim((string) $data['notes']) : null;
    
        return $data;
    }
}