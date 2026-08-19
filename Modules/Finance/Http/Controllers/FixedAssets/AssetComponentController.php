<?php

namespace Modules\Finance\Http\Controllers\FixedAssets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\FixedAssets\FixedAsset;
use Modules\Finance\Models\FixedAssets\FixedAssetComponent;

class AssetComponentController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_asset_components.view'), 403);
        return view('finance.fixed_assets.components');
    }

    public function datatable(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_asset_components.view'), 403);

        $companyId = $request->user()->company_id ?? 1;

        $rows = FixedAssetComponent::where('company_id',$companyId)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->get();

        return response()->json(['data'=>$rows]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_asset_components.create'), 403);

        $companyId = $request->user()->company_id ?? 1;

        $data = $request->validate([
            'parent_asset_id' => 'required|integer',
            'component_code' => 'nullable|string|max:100',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
            'salvage_value' => 'nullable|numeric|min:0',
            'depr_method' => 'required|in:straight_line,reducing_balance',
            'useful_life_months' => 'required|integer|min:1',
            'depr_rate' => 'nullable|numeric|min:0',
        ]);

        $parent = FixedAsset::where('company_id',$companyId)->where('id',$data['parent_asset_id'])->firstOrFail();

        FixedAssetComponent::create([
            'company_id'=>$companyId,
            'parent_asset_id'=>$parent->id,
            'component_code'=>$data['component_code'] ?? null,
            'name'=>$data['name'],
            'description'=>$data['description'] ?? null,
            'cost'=>round((float)$data['cost'],2),
            'salvage_value'=>round((float)($data['salvage_value'] ?? 0),2),
            'depr_method'=>$data['depr_method'],
            'useful_life_months'=>$data['useful_life_months'],
            'depr_rate'=>$data['depr_rate'] ?? null,
            'status'=>'draft',
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);

        return response()->json(['ok'=>true,'message'=>'Component created (Draft).']);
    }

    public function activate(Request $request, $id)
    {
        abort_unless($request->user()->can('finance.fixed_asset_components.activate'), 403);

        $companyId = $request->user()->company_id ?? 1;

        $row = FixedAssetComponent::where('company_id',$companyId)->where('id',$id)->firstOrFail();
        if ($row->status !== 'draft') return response()->json(['ok'=>false,'message'=>'Only Draft components can be activated.'], 422);

        $row->update(['status'=>'active','updated_at'=>now()]);

        return response()->json(['ok'=>true,'message'=>'Component activated.']);
    }

    public function retire(Request $request, $id)
    {
        abort_unless($request->user()->can('finance.fixed_asset_components.retire'), 403);

        $companyId = $request->user()->company_id ?? 1;

        $row = FixedAssetComponent::where('company_id',$companyId)->where('id',$id)->firstOrFail();
        if (!in_array($row->status, ['draft','active'])) return response()->json(['ok'=>false,'message'=>'Component cannot be retired.'], 422);

        $row->update(['status'=>'retired','updated_at'=>now()]);

        return response()->json(['ok'=>true,'message'=>'Component retired.']);
    }
}