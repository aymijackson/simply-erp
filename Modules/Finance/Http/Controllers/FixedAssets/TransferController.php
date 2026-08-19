<?php

namespace Modules\Finance\Http\Controllers\FixedAssets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\FixedAssets\Transfer;
use Modules\Finance\Models\FixedAssets\FixedAsset;
use Modules\Finance\Services\JournalPostingService;

class TransferController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_asset_transfers.view'), 403);
        return view('finance.fixed_assets.transfers');
    }

    public function datatable(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_asset_transfers.view'), 403);
        $companyId = $request->user()->company_id ?? 1;

        $rows = Transfer::where('company_id',$companyId)->whereNull('deleted_at')->orderByDesc('id')->get();
        return response()->json(['data'=>$rows]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_asset_transfers.create'), 403);

        $companyId = $request->user()->company_id ?? 1;

        $data = $request->validate([
            'asset_id' => 'required|integer',
            'transfer_date' => 'required|date',
            'from_location' => 'nullable|string|max:150',
            'to_location' => 'nullable|string|max:150',
            'from_department' => 'nullable|string|max:150',
            'to_department' => 'nullable|string|max:150',
            'memo' => 'nullable|string|max:255',
        ]);

        $asset = FixedAsset::where('company_id',$companyId)->where('id',$data['asset_id'])->firstOrFail();
        if ($asset->status !== 'active') return response()->json(['ok'=>false,'message'=>'Only Active assets can be transferred.'], 422);

        Transfer::create([
            'company_id'=>$companyId,
            'asset_id'=>$asset->id,
            'transfer_date'=>$data['transfer_date'],
            'from_location'=>$data['from_location'] ?? $asset->location,
            'to_location'=>$data['to_location'] ?? null,
            'from_department'=>$data['from_department'] ?? null,
            'to_department'=>$data['to_department'] ?? null,
            'memo'=>$data['memo'] ?? null,
            'status'=>'draft',
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);

        return response()->json(['ok'=>true,'message'=>'Transfer created (Draft).']);
    }

    public function post(Request $request, $id)
    {
        abort_unless($request->user()->can('finance.fixed_asset_transfers.post'), 403);

        $companyId = $request->user()->company_id ?? 1;
        $row = Transfer::where('company_id',$companyId)->where('id',$id)->firstOrFail();

        if ($row->status !== 'draft') return response()->json(['ok'=>false,'message'=>'Only Draft transfers can be posted.'], 422);

        $asset = FixedAsset::where('company_id',$companyId)->where('id',$row->asset_id)->firstOrFail();

        // Typically: NO GL. Update master record fields.
        $asset->update([
            'location' => $row->to_location ?: $asset->location,
        ]);

        $row->update([
            'status'=>'posted',
            'posted_at'=>now(),
            'posted_by'=>$request->user()->id,
            'updated_at'=>now(),
        ]);

        return response()->json(['ok'=>true,'message'=>'Transfer posted. (Non-financial update applied)']);
    }

    public function void(Request $request, $id)
    {
        abort_unless($request->user()->can('finance.fixed_asset_transfers.void'), 403);

        $companyId = $request->user()->company_id ?? 1;
        $data = $request->validate(['reason'=>'required|string|max:255']);

        $row = Transfer::where('company_id',$companyId)->where('id',$id)->firstOrFail();
        if ($row->status !== 'posted') return response()->json(['ok'=>false,'message'=>'Only posted transfers can be voided.'], 422);

        $row->update([
            'status'=>'voided',
            'voided_at'=>now(),
            'voided_by'=>$request->user()->id,
            'void_reason'=>$data['reason'],
            'updated_at'=>now(),
        ]);

        return response()->json(['ok'=>true,'message'=>'Transfer voided. (Master record not auto-reverted)']);
    }
}