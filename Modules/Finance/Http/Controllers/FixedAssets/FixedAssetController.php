<?php

namespace Modules\Finance\Http\Controllers\FixedAssets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\FixedAssets\FixedAsset;
use Modules\Finance\Models\FixedAssets\FixedAssetCategory;

class FixedAssetController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_assets.view'), 403);

        $companyId = $request->user()->company_id;

        $categories = FixedAssetCategory::where('company_id',$companyId)->whereNull('deleted_at')->orderBy('name')->get();

        $accounts = DB::table('finance_accounts')
            ->where('company_id',$companyId)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id','code','name']);

        return view('finance.fixed_assets.index', compact('categories','accounts'));
    }

    public function datatable(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_assets.view'), 403);

        $companyId = $request->user()->company_id;

        $rows = FixedAsset::with('category:id,name')
            ->where('company_id',$companyId)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => $rows->map(function($a){
                return [
                    'id' => $a->id,
                    'asset_code' => $a->asset_code,
                    'name' => $a->name,
                    'category' => $a->category?->name,
                    'purchase_date' => $a->purchase_date,
                    'in_service_date' => $a->in_service_date,
                    'purchase_cost' => (float)$a->purchase_cost,
                    'status' => $a->status,
                ];
            })
        ]);
    }

    public function json(Request $request, $id)
    {
        abort_unless($request->user()->can('finance.fixed_assets.view'), 403);

        $companyId = $request->user()->company_id;

        $asset = FixedAsset::where('company_id',$companyId)->where('id',$id)->firstOrFail();
        return response()->json(['ok'=>true,'data'=>$asset]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_assets.create'), 403);

        $companyId = $request->user()->company_id;

        $data = $request->validate([
            'category_id' => 'required|integer',
            'asset_code' => 'required|string|max:60',
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',

            'purchase_date' => 'required|date',
            'in_service_date' => 'required|date',
            'purchase_cost' => 'required|numeric|min:0',
            'salvage_value' => 'nullable|numeric|min:0',

            'depr_method' => 'required|in:straight_line,declining_balance',
            'useful_life_months' => 'required|integer|min:1',
            'depr_rate' => 'nullable|numeric|min:0',

            'asset_account_id' => 'nullable|integer',
            'accum_depr_account_id' => 'nullable|integer',
            'depr_expense_account_id' => 'nullable|integer',
            'disposal_gain_account_id' => 'nullable|integer',
            'disposal_loss_account_id' => 'nullable|integer',

            'location' => 'nullable|string|max:150',
            'serial_no' => 'nullable|string|max:120',
            'supplier_name' => 'nullable|string|max:150',
            'invoice_no' => 'nullable|string|max:80',
        ]);

        // defaults from category
        $cat = FixedAssetCategory::where('company_id',$companyId)->where('id',$data['category_id'])->firstOrFail();

        $data['company_id'] = $companyId;
        $data['salvage_value'] = $data['salvage_value'] ?? $cat->default_salvage_value ?? 0;

        $data['asset_account_id'] = $data['asset_account_id'] ?? $cat->default_asset_account_id;
        $data['accum_depr_account_id'] = $data['accum_depr_account_id'] ?? $cat->default_accum_depr_account_id;
        $data['depr_expense_account_id'] = $data['depr_expense_account_id'] ?? $cat->default_depr_expense_account_id;

        $data['disposal_gain_account_id'] = $data['disposal_gain_account_id'] ?? $cat->default_disposal_gain_account_id;
        $data['disposal_loss_account_id'] = $data['disposal_loss_account_id'] ?? $cat->default_disposal_loss_account_id;

        $data['status'] = 'draft';

        FixedAsset::create($data);

        return response()->json(['ok'=>true,'message'=>'Asset created (Draft).']);
    }

    public function update(Request $request, $id)
    {
        abort_unless($request->user()->can('finance.fixed_assets.update'), 403);

        $companyId = $request->user()->company_id;

        $asset = FixedAsset::where('company_id',$companyId)->where('id',$id)->firstOrFail();
        if ($asset->status !== 'draft') {
            return response()->json(['ok'=>false,'message'=>'Only Draft assets can be edited.'], 422);
        }

        $data = $request->validate([
            'category_id' => 'required|integer',
            'asset_code' => 'required|string|max:60',
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',

            'purchase_date' => 'required|date',
            'in_service_date' => 'required|date',
            'purchase_cost' => 'required|numeric|min:0',
            'salvage_value' => 'nullable|numeric|min:0',

            'depr_method' => 'required|in:straight_line,declining_balance',
            'useful_life_months' => 'required|integer|min:1',
            'depr_rate' => 'nullable|numeric|min:0',

            'asset_account_id' => 'nullable|integer',
            'accum_depr_account_id' => 'nullable|integer',
            'depr_expense_account_id' => 'nullable|integer',
            'disposal_gain_account_id' => 'nullable|integer',
            'disposal_loss_account_id' => 'nullable|integer',

            'location' => 'nullable|string|max:150',
            'serial_no' => 'nullable|string|max:120',
            'supplier_name' => 'nullable|string|max:150',
            'invoice_no' => 'nullable|string|max:80',
        ]);

        $cat = FixedAssetCategory::where('company_id',$companyId)->where('id',$data['category_id'])->firstOrFail();
        $data['salvage_value'] = $data['salvage_value'] ?? $cat->default_salvage_value ?? 0;

        // if user leaves these blank, apply category defaults
        $data['asset_account_id'] = $data['asset_account_id'] ?? $cat->default_asset_account_id;
        $data['accum_depr_account_id'] = $data['accum_depr_account_id'] ?? $cat->default_accum_depr_account_id;
        $data['depr_expense_account_id'] = $data['depr_expense_account_id'] ?? $cat->default_depr_expense_account_id;
        $data['disposal_gain_account_id'] = $data['disposal_gain_account_id'] ?? $cat->default_disposal_gain_account_id;
        $data['disposal_loss_account_id'] = $data['disposal_loss_account_id'] ?? $cat->default_disposal_loss_account_id;

        $asset->update($data);

        return response()->json(['ok'=>true,'message'=>'Asset updated.']);
    }

    public function destroy(Request $request, $id)
    {
        abort_unless($request->user()->can('finance.fixed_assets.delete'), 403);

        $companyId = $request->user()->company_id;

        $asset = FixedAsset::where('company_id',$companyId)->where('id',$id)->firstOrFail();
        if ($asset->status !== 'draft') {
            return response()->json(['ok'=>false,'message'=>'Only Draft assets can be deleted.'], 422);
        }

        $asset->delete();
        return response()->json(['ok'=>true,'message'=>'Asset deleted.']);
    }

    public function activate(Request $request, $id)
    {
        abort_unless($request->user()->can('finance.fixed_assets.activate'), 403);

        $companyId = $request->user()->company_id;

        $asset = FixedAsset::with('category')->where('company_id',$companyId)->where('id',$id)->firstOrFail();
        if ($asset->status !== 'draft') {
            return response()->json(['ok'=>false,'message'=>'Asset is not in Draft state.'], 422);
        }

        // controls: must have key accounts
        if (!$asset->asset_account_id || !$asset->accum_depr_account_id || !$asset->depr_expense_account_id) {
            return response()->json(['ok'=>false,'message'=>'Set Asset / Accum Depreciation / Depreciation Expense accounts.'], 422);
        }

        // disposal gain/loss defaults must exist at least on category (recommended)
        $gainAcct = $asset->disposal_gain_account_id ?: $asset->category?->default_disposal_gain_account_id;
        $lossAcct = $asset->disposal_loss_account_id ?: $asset->category?->default_disposal_loss_account_id;
        if (!$gainAcct || !$lossAcct) {
            return response()->json(['ok'=>false,'message'=>'Set Disposal Gain and Disposal Loss accounts on Category (or Asset).'], 422);
        }

        $asset->update(['status'=>'active']);
        return response()->json(['ok'=>true,'message'=>'Asset activated.']);
    }

    public function show(Request $request, $id)
    {
        abort_unless($request->user()->can('finance.fixed_assets.view'), 403);

        $companyId = $request->user()->company_id;

        $asset = FixedAsset::with('category')->where('company_id',$companyId)->where('id',$id)->firstOrFail();

        $deprPosted = (float)DB::table('finance_fixed_asset_depr_lines')
            ->join('finance_fixed_asset_depr_runs','finance_fixed_asset_depr_runs.id','=','finance_fixed_asset_depr_lines.depr_run_id')
            ->whereNull('finance_fixed_asset_depr_runs.deleted_at')
            ->where('finance_fixed_asset_depr_runs.company_id',$companyId)
            ->where('finance_fixed_asset_depr_runs.status','posted')
            ->where('finance_fixed_asset_depr_lines.asset_id',$asset->id)
            ->sum('finance_fixed_asset_depr_lines.amount');

        $txns = DB::table('finance_fixed_asset_transactions')
            ->where('company_id',$companyId)
            ->where('asset_id',$asset->id)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->get();

        $accounts = DB::table('finance_accounts')
            ->where('company_id',$companyId)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id','code','name']);

        return view('finance.fixed_assets.show', compact('asset','deprPosted','txns','accounts'));
    }
}