<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ChartOfAccountsController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $types = DB::table('finance_account_types')
            ->orderBy('name')
            ->get(['id','code','name','category','normal_balance']);

        return view('finance.chart_of_accounts.index', compact('companyId','types'));
    }

    public function datatable(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $q = DB::table('finance_accounts as a')
            ->join('finance_account_types as t', 't.id', '=', 'a.account_type_id')
            ->leftJoin('finance_accounts as p', 'p.id', '=', 'a.parent_id')
            ->where('a.company_id', $companyId)
            ->select([
                'a.id',
                'a.code',
                'a.name',
                'a.description',
                'a.parent_id',
                'p.code as parent_code',
                'p.name as parent_name',
                'a.is_control',
                'a.allow_manual_posting',
                'a.is_active',
                'a.account_type_id',
                't.code as type_code',
                't.name as type_name',
                't.category as type_category',
                't.normal_balance as normal_balance',
            ]);

        // filters
        if ($request->filled('type_id')) {
            $q->where('a.account_type_id', (int)$request->type_id);
        }
        if ($request->filled('category')) {
            $q->where('t.category', $request->category);
        }
        if ($request->filled('active')) {
            $q->where('a.is_active', (int)$request->active);
        }
        if ($request->filled('q')) {
            $term = trim((string)$request->q);
            $q->where(function ($x) use ($term) {
                $x->where('a.code', 'like', "%{$term}%")
                  ->orWhere('a.name', 'like', "%{$term}%");
            });
        }

        // DataTables server-side style
        $start  = (int)($request->start ?? 0);
        $length = (int)($request->length ?? 10);
        $draw   = (int)($request->draw ?? 1);

        $recordsTotal = (clone $q)->count();

        // ordering
        $orderColIndex = $request->input('order.0.column');
        $orderDir      = $request->input('order.0.dir', 'asc');

        $columns = [
            0 => 'a.id',
            1 => 'a.code',
            2 => 'a.name',
            3 => 't.category',
            4 => 't.name',
            5 => 'p.code',
            6 => 'a.is_control',
            7 => 'a.allow_manual_posting',
            8 => 'a.is_active',
        ];

        if ($orderColIndex !== null && isset($columns[(int)$orderColIndex])) {
            $q->orderBy($columns[(int)$orderColIndex], $orderDir === 'desc' ? 'desc' : 'asc');
        } else {
            $q->orderBy('a.code', 'asc');
        }

        $rows = $q->offset($start)->limit($length)->get();

        $data = $rows->map(function ($r) {
            $json = [
                'id' => $r->id,
                'account_type_id' => $r->account_type_id,
                'code' => $r->code,
                'name' => $r->name,
                'parent_id' => $r->parent_id,
                'parent_label' => $r->parent_id ? ($r->parent_code.' - '.$r->parent_name) : null,
                'is_control' => (int)$r->is_control,
                'allow_manual_posting' => (int)$r->allow_manual_posting,
                'is_active' => (int)$r->is_active,
                'description' => $r->description,
            ];

            return [
                'id' => $r->id,
                'code' => e($r->code),
                'name' => e($r->name),
                'category' => e($r->type_category),
                'type' => e($r->type_name),
                'parent' => $r->parent_id ? e($r->parent_code.' - '.$r->parent_name) : '—',
                'control' => $r->is_control ? '<span class="badge bg-info">CONTROL</span>' : '<span class="text-muted">No</span>',
                'manual' => $r->allow_manual_posting ? '<span class="badge bg-success">YES</span>' : '<span class="badge bg-secondary">NO</span>',
                'active' => $r->is_active ? '<span class="badge bg-success">ACTIVE</span>' : '<span class="badge bg-secondary">DISABLED</span>',
                'actions' => view('finance.chart_of_accounts.partials.actions', ['json' => $json])->render(),
            ];
        })->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => $data,
        ]);
    }

    public function parentOptions(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $term = trim((string)$request->get('q', ''));
        $accountTypeId = $request->get('account_type_id');
    
        $q = DB::table('finance_accounts as a')
            ->join('finance_account_types as t', 't.id', '=', 'a.account_type_id')
            ->where('a.company_id', $companyId)
            ->where('a.is_active', 1)
            ->select([
                'a.id','a.code','a.name','a.is_control','a.account_type_id',
                't.category'
            ]);
    
        if ($accountTypeId) {
            $childType = DB::table('finance_account_types')->where('id', (int)$accountTypeId)->first(['category']);
            if ($childType) {
                $q->where('t.category', $childType->category);
            }
        }
    
        if ($term !== '') {
            $q->where(function($x) use ($term){
                $x->where('a.code', 'like', "%{$term}%")
                  ->orWhere('a.name', 'like', "%{$term}%");
            });
        }
    
        $rows = $q
            ->orderByDesc('a.is_control')
            ->orderBy('a.code')
            ->limit(30)
            ->get();
    
        return response()->json([
            'results' => $rows->map(fn($r)=>[
                'id' => $r->id,
                'text' => $r->code.' - '.$r->name.($r->is_control ? ' [CONTROL]' : ''),
            ])->values()
        ]);
    }
    
    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $data = $this->validateAccount($request, null);

        $exists = DB::table('finance_accounts')
            ->where('company_id', $companyId)
            ->where('code', $data['code'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Account code already exists.'], 422);
        }

        $id = DB::table('finance_accounts')->insertGetId([
            'company_id' => $companyId,
            'account_type_id' => $data['account_type_id'],
            'code' => $data['code'],
            'name' => $data['name'],
            'parent_id' => $data['parent_id'],
            'is_control' => $data['is_control'],
            'allow_manual_posting' => $data['allow_manual_posting'],
            'is_active' => $data['is_active'],
            'description' => $data['description'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Account created.', 'id' => $id]);
    }

    public function update(Request $request, $account)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $data = $this->validateAccount($request, (int)$account);

        $row = DB::table('finance_accounts')
            ->where('company_id', $companyId)
            ->where('id', (int)$account)
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        $exists = DB::table('finance_accounts')
            ->where('company_id', $companyId)
            ->where('code', $data['code'])
            ->where('id', '!=', (int)$account)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Account code already exists.'], 422);
        }

        // prevent self-parenting
        if (!empty($data['parent_id']) && (int)$data['parent_id'] === (int)$account) {
            return response()->json(['message' => 'An account cannot be its own parent.'], 422);
        }

        DB::table('finance_accounts')
            ->where('id', (int)$account)
            ->update([
                'account_type_id' => $data['account_type_id'],
                'code' => $data['code'],
                'name' => $data['name'],
                'parent_id' => $data['parent_id'],
                'is_control' => $data['is_control'],
                'allow_manual_posting' => $data['allow_manual_posting'],
                'is_active' => $data['is_active'],
                'description' => $data['description'],
                'updated_at' => now(),
            ]);

        return response()->json(['message' => 'Account updated.']);
    }

    public function destroy($account)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $row = DB::table('finance_accounts')
            ->where('company_id', $companyId)
            ->where('id', (int)$account)
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        // safety: block delete if used in journal lines
        $used = DB::table('finance_journal_entry_lines')
            ->where('account_id', (int)$account)
            ->exists();

        if ($used) {
            return response()->json(['message' => 'Cannot delete: account has journal activity. Disable it instead.'], 422);
        }

        // safety: block delete if has children
        $hasChildren = DB::table('finance_accounts')
            ->where('company_id', $companyId)
            ->where('parent_id', (int)$account)
            ->exists();

        if ($hasChildren) {
            return response()->json(['message' => 'Cannot delete: account has child accounts.'], 422);
        }

        DB::table('finance_accounts')->where('id', (int)$account)->delete();

        return response()->json(['message' => 'Account deleted.']);
    }

    public function bulkDelete(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $ids = $request->input('ids', []);

        if (!is_array($ids) || !count($ids)) {
            return response()->json(['message' => 'No rows selected.'], 422);
        }

        $ids = array_map('intval', $ids);

        // only delete those with no usage and no children
        $safeIds = [];
        foreach ($ids as $id) {
            $used = DB::table('finance_journal_entry_lines')->where('account_id', $id)->exists();
            $hasChildren = DB::table('finance_accounts')->where('company_id', $companyId)->where('parent_id', $id)->exists();
            if (!$used && !$hasChildren) $safeIds[] = $id;
        }

        if (!count($safeIds)) {
            return response()->json(['message' => 'None of the selected accounts can be deleted (usage/children).'], 422);
        }

        DB::table('finance_accounts')
            ->where('company_id', $companyId)
            ->whereIn('id', $safeIds)
            ->delete();

        return response()->json(['message' => 'Selected accounts deleted (safe items only).']);
    }

    private function validateAccount(Request $request, ?int $ignoreId): array
    {
        $companyId = auth()->user()->company_id ?? 1;
    
        $rules = [
            'account_type_id' => ['required','integer','exists:finance_account_types,id'],
            'code' => ['required','string','max:50'],
            'name' => ['required','string','max:255'],
            'parent_id' => ['nullable','integer','min:1'],
            'is_control' => ['nullable','boolean'],
            'allow_manual_posting' => ['nullable','boolean'],
            'is_active' => ['nullable','boolean'],
            'description' => ['nullable','string'],
        ];
    
        $data = validator($request->all(), $rules)->validate();
    
        $data['code'] = trim($data['code']);
        $data['name'] = trim($data['name']);
    
        $data['parent_id'] = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
        $data['is_control'] = !empty($data['is_control']) ? 1 : 0;
        $data['allow_manual_posting'] = array_key_exists('allow_manual_posting', $data) ? (int)!!$data['allow_manual_posting'] : 1;
        $data['is_active'] = array_key_exists('is_active', $data) ? (int)!!$data['is_active'] : 1;
    
        if ($data['parent_id']) {
            if ($ignoreId && $data['parent_id'] === $ignoreId) {
                abort(response()->json(['message' => 'An account cannot be its own parent.'], 422));
            }
    
            $parent = DB::table('finance_accounts as a')
                ->join('finance_account_types as t', 't.id', '=', 'a.account_type_id')
                ->where('a.id', $data['parent_id'])
                ->where('a.company_id', $companyId)
                ->select('a.id', 'a.company_id', 'a.account_type_id', 't.category')
                ->first();
    
            if (!$parent) {
                abort(response()->json(['message' => 'Selected parent account was not found for this company.'], 422));
            }
    
            $childType = DB::table('finance_account_types')
                ->where('id', $data['account_type_id'])
                ->first(['id', 'category']);
    
            if (!$childType) {
                abort(response()->json(['message' => 'Invalid account type selected.'], 422));
            }
    
            if ($parent->category !== $childType->category) {
                abort(response()->json(['message' => 'Parent account must belong to the same account category.'], 422));
            }
        }
    
        return $data;
    }
}