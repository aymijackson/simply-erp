<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ExpenseCategoriesController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id ?? 1;
        return view('finance.expense_categories.index', compact('companyId'));
    }

    public function datatable(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $q = DB::table('finance_expense_categories as c')
            ->leftJoin('finance_accounts as a', 'a.id', '=', 'c.gl_account_id')
            ->where('c.company_id', $companyId)
            ->select([
                'c.id','c.name','c.gl_account_id','c.is_active','c.created_at','c.updated_at',
                'a.code as gl_code','a.name as gl_name',
            ]);

        if ($request->filled('active')) {
            $q->where('c.is_active', (int)$request->active);
        }

        if ($request->filled('q')) {
            $term = trim((string)$request->q);
            $q->where(function($x) use ($term){
                $x->where('c.name','like',"%{$term}%")
                  ->orWhere('a.code','like',"%{$term}%")
                  ->orWhere('a.name','like',"%{$term}%");
            });
        }

        $start  = (int)($request->start ?? 0);
        $length = (int)($request->length ?? 10);
        $draw   = (int)($request->draw ?? 1);

        $recordsTotal = (clone $q)->count();

        // ordering (match datatable columns)
        $orderColIndex = $request->input('order.0.column');
        $orderDir      = $request->input('order.0.dir', 'asc');

        $columns = [
            0 => 'c.id',
            1 => 'c.name',
            2 => 'a.code',
            3 => 'c.is_active',
        ];

        if ($orderColIndex !== null && isset($columns[(int)$orderColIndex])) {
            $q->orderBy($columns[(int)$orderColIndex], $orderDir === 'desc' ? 'desc' : 'asc');
        } else {
            $q->orderBy('c.id', 'desc');
        }

        $rows = $q->offset($start)->limit($length)->get();

        $data = $rows->map(function($r){
            $json = [
                'id' => $r->id,
                'name' => $r->name,
                'gl_account_id' => $r->gl_account_id,
                'gl_label' => $r->gl_account_id ? trim(($r->gl_code ?? '').' - '.($r->gl_name ?? '')) : null,
                'is_active' => (int)$r->is_active,
            ];

            return [
                'id' => $r->id,
                'name' => e($r->name),
                'gl' => $r->gl_account_id ? e(trim(($r->gl_code ?? '').' - '.($r->gl_name ?? ''))) : '—',
                'active' => (int)$r->is_active
                    ? '<span class="badge bg-success">ACTIVE</span>'
                    : '<span class="badge bg-secondary">DISABLED</span>',
                'actions' => view('finance.expense_categories.partials.actions', ['json'=>$json])->render(),
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
        $data = $this->validateCategory($request);

        $id = DB::table('finance_expense_categories')->insertGetId([
            'company_id' => $companyId,
            'name' => $data['name'],
            'gl_account_id' => $data['gl_account_id'],
            'is_active' => $data['is_active'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message'=>'Expense category created.', 'id'=>$id]);
    }

    public function update(Request $request, $category)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $data = $this->validateCategory($request);

        $row = DB::table('finance_expense_categories')
            ->where('company_id', $companyId)
            ->where('id', (int)$category)
            ->first();

        if (!$row) return response()->json(['message'=>'Expense category not found.'], 404);

        DB::table('finance_expense_categories')
            ->where('company_id', $companyId)
            ->where('id', (int)$category)
            ->update([
                'name' => $data['name'],
                'gl_account_id' => $data['gl_account_id'],
                'is_active' => $data['is_active'],
                'updated_at' => now(),
            ]);

        return response()->json(['message'=>'Expense category updated.']);
    }

    public function destroy($category)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $row = DB::table('finance_expense_categories')
            ->where('company_id', $companyId)
            ->where('id', (int)$category)
            ->first();

        if (!$row) return response()->json(['message'=>'Not found.'], 404);

        // Safety: block delete if category used by expenses
        $used = DB::table('finance_expenses')
            ->where('company_id', $companyId)
            ->where('category_id', (int)$category)
            ->exists();

        if ($used) {
            return response()->json([
                'message' => 'Cannot delete: this category is already used by one or more expenses. Disable it instead.'
            ], 422);
        }

        DB::table('finance_expense_categories')
            ->where('company_id', $companyId)
            ->where('id', (int)$category)
            ->delete();

        return response()->json(['message'=>'Deleted.']);
    }

    public function bulkDelete(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $ids = $request->input('ids', []);

        if (!is_array($ids) || !count($ids)) {
            return response()->json(['message'=>'No rows selected.'], 422);
        }

        $ids = array_map('intval', $ids);

        // Block IDs that are used
        $usedIds = DB::table('finance_expenses')
            ->where('company_id', $companyId)
            ->whereIn('category_id', $ids)
            ->pluck('category_id')
            ->unique()
            ->values()
            ->all();

        $safeIds = array_values(array_diff($ids, $usedIds));

        if (!count($safeIds)) {
            return response()->json([
                'message' => 'None deleted: selected categories are used by expenses. Disable them instead.'
            ], 422);
        }

        DB::table('finance_expense_categories')
            ->where('company_id', $companyId)
            ->whereIn('id', $safeIds)
            ->delete();

        $msg = 'Selected categories deleted.';
        if (count($usedIds)) {
            $msg .= ' Some were skipped because they are used by expenses.';
        }

        return response()->json(['message'=>$msg]);
    }

    private function validateCategory(Request $request): array
    {
        $rules = [
            'name' => ['required','string','max:150'],
            'gl_account_id' => ['nullable','integer','exists:finance_accounts,id'],
            'is_active' => ['nullable','boolean'],
        ];

        $data = Validator::make($request->all(), $rules)->validate();

        $data['name'] = trim($data['name']);
        $data['gl_account_id'] = !empty($data['gl_account_id']) ? (int)$data['gl_account_id'] : null;
        $data['is_active'] = array_key_exists('is_active',$data) ? (int)!!$data['is_active'] : 1;

        return $data;
    }
}