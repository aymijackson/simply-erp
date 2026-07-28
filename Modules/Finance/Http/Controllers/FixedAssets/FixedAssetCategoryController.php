<?php

namespace Modules\Finance\Http\Controllers\FixedAssets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\FixedAssets\FixedAssetCategory;

class FixedAssetCategoryController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_asset_categories.view'), 403);

        $companyId = $request->user()->company_id;

        $accounts = DB::table('finance_accounts')
            ->where('company_id',$companyId)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id','code','name']);

        return view('finance.fixed_assets.categories', compact('accounts'));
    }

    public function datatable(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_asset_categories.view'), 403);

        $companyId = $request->user()->company_id;

        $rows = FixedAssetCategory::where('company_id',$companyId)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->get();

        return response()->json(['data'=>$rows]);
    }

    public function json(Request $request, $id)
    {
        abort_unless($request->user()->can('finance.fixed_asset_categories.view'), 403);

        $companyId = $request->user()->company_id;

        $row = FixedAssetCategory::where('company_id',$companyId)->where('id',$id)->firstOrFail();
        return response()->json(['ok'=>true,'data'=>$row]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_asset_categories.create'), 403);

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:50',

            'default_asset_account_id' => 'nullable|integer',
            'default_accum_depr_account_id' => 'nullable|integer',
            'default_depr_expense_account_id' => 'nullable|integer',
            'default_disposal_gain_account_id' => 'nullable|integer',
            'default_disposal_loss_account_id' => 'nullable|integer',

            'default_depr_method' => 'required|in:straight_line,declining_balance',
            'default_useful_life_months' => 'nullable|integer|min:1',
            'default_salvage_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $data['company_id'] = $request->user()->company_id;
        $data['default_salvage_value'] = $data['default_salvage_value'] ?? 0;
        $data['is_active'] = isset($data['is_active']) ? (int)$data['is_active'] : 1;

        FixedAssetCategory::create($data);

        return response()->json(['ok'=>true,'message'=>'Category created.']);
    }

    public function update(Request $request, $id)
    {
        abort_unless($request->user()->can('finance.fixed_asset_categories.update'), 403);

        $companyId = $request->user()->company_id;

        $row = FixedAssetCategory::where('company_id',$companyId)->where('id',$id)->firstOrFail();

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:50',

            'default_asset_account_id' => 'nullable|integer',
            'default_accum_depr_account_id' => 'nullable|integer',
            'default_depr_expense_account_id' => 'nullable|integer',
            'default_disposal_gain_account_id' => 'nullable|integer',
            'default_disposal_loss_account_id' => 'nullable|integer',

            'default_depr_method' => 'required|in:straight_line,declining_balance',
            'default_useful_life_months' => 'nullable|integer|min:1',
            'default_salvage_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $data['default_salvage_value'] = $data['default_salvage_value'] ?? 0;
        $data['is_active'] = isset($data['is_active']) ? (int)$data['is_active'] : 1;

        $row->update($data);

        return response()->json(['ok'=>true,'message'=>'Category updated.']);
    }

    public function destroy(Request $request, $id)
    {
        abort_unless($request->user()->can('finance.fixed_asset_categories.delete'), 403);

        $companyId = $request->user()->company_id;

        $row = FixedAssetCategory::where('company_id',$companyId)->where('id',$id)->firstOrFail();
        $row->delete();

        return response()->json(['ok'=>true,'message'=>'Category deleted.']);
    }
}