<?php

namespace Modules\Finance\Http\Controllers\FixedAssets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\FixedAssets\AssetCapitalisation;
use Modules\Finance\Models\FixedAssets\FixedAsset;
use Modules\Finance\Models\FixedAssets\FixedAssetCategory;

class AssetCapitalisationController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('finance.asset_capitalisations.view'), 403);

        $companyId = $request->user()->company_id ?? 1;

        $categories = FixedAssetCategory::where('company_id',$companyId)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id','name']);

        return view('finance.fixed_assets.capitalisations', compact('categories'));
    }

    public function datatable(Request $request)
    {
        abort_unless($request->user()->can('finance.asset_capitalisations.view'), 403);

        $companyId = $request->user()->company_id ?? 1;

        $rows = AssetCapitalisation::where('company_id',$companyId)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->get();

        return response()->json(['data'=>$rows]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('finance.asset_capitalisations.create'), 403);

        $companyId = $request->user()->company_id ?? 1;

        $data = $request->validate([
            'source_module' => 'required|string|max:50',
            'source_table' => 'required|string|max:100',
            'source_id' => 'required|integer',
            'reference_no' => 'nullable|string|max:120',
            'supplier_id' => 'nullable|integer',

            'asset_category_id' => 'required|integer',
            'asset_name' => 'required|string|max:255',
            'asset_description' => 'nullable|string',

            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'required|numeric|min:0',
            'total_cost' => 'nullable|numeric|min:0',

            'purchase_date' => 'required|date',
            'in_service_date' => 'nullable|date',
        ]);

        $cat = FixedAssetCategory::where('company_id',$companyId)->where('id',$data['asset_category_id'])->firstOrFail();

        $total = $data['total_cost'] ?? ((float)$data['unit_cost'] * (int)$data['quantity']);

        AssetCapitalisation::create([
            'company_id'=>$companyId,
            'source_module'=>$data['source_module'],
            'source_table'=>$data['source_table'],
            'source_id'=>$data['source_id'],
            'supplier_id'=>$data['supplier_id'] ?? null,
            'reference_no'=>$data['reference_no'] ?? null,
            'asset_category_id'=>$cat->id,
            'asset_name'=>$data['asset_name'],
            'asset_description'=>$data['asset_description'] ?? null,
            'quantity'=>$data['quantity'],
            'unit_cost'=>round((float)$data['unit_cost'],2),
            'total_cost'=>round((float)$total,2),
            'purchase_date'=>$data['purchase_date'],
            'in_service_date'=>$data['in_service_date'] ?? null,
            'status'=>'pending',
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);

        return response()->json(['ok'=>true,'message'=>'Capitalisation queued (Pending).']);
    }

    public function convert(Request $request, $id)
    {
        abort_unless($request->user()->can('finance.asset_capitalisations.convert'), 403);

        $companyId = $request->user()->company_id ?? 1;

        $row = AssetCapitalisation::where('company_id',$companyId)->where('id',$id)->firstOrFail();
        if ($row->status !== 'pending') return response()->json(['ok'=>false,'message'=>'Only Pending items can be converted.'], 422);

        $cat = FixedAssetCategory::where('company_id',$companyId)->where('id',$row->asset_category_id)->firstOrFail();

        // Convert as a single asset by default (enterprise ERPs often do this).
        // If you want 1 asset per quantity, tell me and I’ll switch.
        $asset = FixedAsset::create([
            'company_id'=>$companyId,
            'category_id'=>$cat->id,
            'asset_code'=>'FA-'.time(), // replace with your code generator if you have one
            'name'=>$row->asset_name,
            'description'=>$row->asset_description,
            'purchase_date'=>$row->purchase_date,
            'in_service_date'=>$row->in_service_date ?? $row->purchase_date,
            'purchase_cost'=>$row->total_cost,
            'salvage_value'=>$cat->default_salvage_value ?? 0,

            'depr_method'=>$cat->default_depr_method,
            'useful_life_months'=>$cat->default_useful_life_months,
            'depr_rate'=>null,

            'asset_account_id'=>$cat->default_asset_account_id,
            'accum_depr_account_id'=>$cat->default_accum_depr_account_id,
            'depr_expense_account_id'=>$cat->default_depr_expense_account_id,

            'disposal_gain_account_id'=>$cat->default_disposal_gain_account_id,
            'disposal_loss_account_id'=>$cat->default_disposal_loss_account_id,

            'supplier_name'=>null,
            'invoice_no'=>$row->reference_no,

            'status'=>'active',

            'created_at'=>now(),
            'updated_at'=>now(),
        ]);

        $row->update([
            'status'=>'converted',
            'converted_asset_id'=>$asset->id,
            'converted_at'=>now(),
            'converted_by'=>$request->user()->id,
            'updated_at'=>now(),
        ]);

        return response()->json(['ok'=>true,'message'=>'Converted to Fixed Asset successfully.','asset_id'=>$asset->id]);
    }

    public function void(Request $request, $id)
    {
        abort_unless($request->user()->can('finance.asset_capitalisations.void'), 403);

        $companyId = $request->user()->company_id ?? 1;
        $data = $request->validate(['reason'=>'required|string|max:255']);

        $row = AssetCapitalisation::where('company_id',$companyId)->where('id',$id)->firstOrFail();
        if (!in_array($row->status, ['pending','converted'])) {
            return response()->json(['ok'=>false,'message'=>'Item cannot be voided.'], 422);
        }

        $row->update([
            'status'=>'voided',
            'voided_at'=>now(),
            'voided_by'=>$request->user()->id,
            'void_reason'=>$data['reason'],
            'updated_at'=>now(),
        ]);

        return response()->json(['ok'=>true,'message'=>'Capitalisation entry voided.']);
    }
}