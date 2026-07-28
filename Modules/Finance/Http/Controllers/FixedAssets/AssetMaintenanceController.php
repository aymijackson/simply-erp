<?php

namespace Modules\Finance\Http\Controllers\FixedAssets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\FixedAssets\FixedAsset;
use Modules\Finance\Models\FixedAssets\FixedAssetMaintenance;
use Modules\Finance\Services\JournalPostingService;

class AssetMaintenanceController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_asset_maintenance.view'), 403);

        $companyId = $request->user()->company_id;

        $accounts = DB::table('finance_accounts')
            ->where('company_id',$companyId)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id','code','name']);

        return view('finance.fixed_assets.maintenance', compact('accounts'));
    }

    public function datatable(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_asset_maintenance.view'), 403);

        $companyId = $request->user()->company_id;

        $rows = FixedAssetMaintenance::where('company_id',$companyId)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->get();

        return response()->json(['data'=>$rows]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('finance.fixed_asset_maintenance.create'), 403);

        $companyId = $request->user()->company_id;

        $data = $request->validate([
            'asset_id' => 'required|integer',
            'component_id' => 'nullable|integer',
            'service_date' => 'required|date',
            'vendor_name' => 'nullable|string|max:255',
            'reference_no' => 'nullable|string|max:100',
            'maintenance_type' => 'required|in:preventive,corrective,inspection,calibration,warranty',
            'description' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
            'expense_account_id' => 'required|integer',
        ]);

        FixedAsset::where('company_id',$companyId)->where('id',$data['asset_id'])->firstOrFail();

        FixedAssetMaintenance::create([
            'company_id'=>$companyId,
            'asset_id'=>$data['asset_id'],
            'component_id'=>$data['component_id'] ?? null,
            'service_date'=>$data['service_date'],
            'vendor_name'=>$data['vendor_name'] ?? null,
            'reference_no'=>$data['reference_no'] ?? null,
            'maintenance_type'=>$data['maintenance_type'],
            'description'=>$data['description'] ?? null,
            'cost'=>round((float)$data['cost'],2),
            'expense_account_id'=>$data['expense_account_id'],
            'status'=>'draft',
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);

        return response()->json(['ok'=>true,'message'=>'Maintenance log created (Draft).']);
    }

    public function post(Request $request, $id, JournalPostingService $jps)
    {
        abort_unless($request->user()->can('finance.fixed_asset_maintenance.post'), 403);

        $companyId = $request->user()->company_id;

        $row = FixedAssetMaintenance::where('company_id',$companyId)->where('id',$id)->firstOrFail();
        if ($row->status !== 'draft') return response()->json(['ok'=>false,'message'=>'Only Draft items can be posted.'], 422);
        if ((float)$row->cost <= 0) return response()->json(['ok'=>false,'message'=>'Cost must be greater than 0 to post.'], 422);

        // Posting logic (simple best practice):
        // Dr Maintenance Expense / Cr Accounts Payable (or a clearing account)
        // If you want Bank instead, swap credit account to your bank account id.
        $apAccountId = (int) config('finance.default_ap_account_id', 0);
        if ($apAccountId <= 0) {
            return response()->json(['ok'=>false,'message'=>'Set finance.default_ap_account_id in config OR change credit account logic.'], 422);
        }

        $jid = $jps->createJournal([
            'company_id'=>$companyId,
            'entry_date'=>$row->service_date,
            'reference'=>"FA-MAINT #{$row->id}",
            'memo'=>$row->description,
            'status'=>'posted',
            'source_type'=>'fixed_asset_maintenance',
            'source_id'=>$row->id,
            'posted_by'=>$request->user()->id,
            'posted_at'=>now(),
        ], [
            [
                'account_id'=>$row->expense_account_id,
                'description'=>'Asset maintenance expense',
                'debit'=>round((float)$row->cost,2),
                'credit'=>0
            ],
            [
                'account_id'=>$apAccountId,
                'description'=>'Maintenance payable',
                'debit'=>0,
                'credit'=>round((float)$row->cost,2)
            ],
        ]);

        $row->update([
            'status'=>'posted',
            'journal_entry_id'=>$jid,
            'posted_at'=>now(),
            'posted_by'=>$request->user()->id,
            'updated_at'=>now(),
        ]);

        return response()->json(['ok'=>true,'message'=>'Maintenance posted to GL.','journal_entry_id'=>$jid]);
    }

    public function void(Request $request, $id, JournalPostingService $jps)
    {
        abort_unless($request->user()->can('finance.fixed_asset_maintenance.void'), 403);

        $companyId = $request->user()->company_id;
        $data = $request->validate(['reason'=>'required|string|max:255']);

        $row = FixedAssetMaintenance::where('company_id',$companyId)->where('id',$id)->firstOrFail();
        if ($row->status !== 'posted') return response()->json(['ok'=>false,'message'=>'Only posted items can be voided.'], 422);
        if (!$row->journal_entry_id) return response()->json(['ok'=>false,'message'=>'No linked journal to reverse.'], 422);

        $revId = $jps->reverseJournal($companyId, (int)$row->journal_entry_id, [
            'entry_date'=>now()->toDateString(),
            'reference'=>'REV-FA-MAINT-'.$row->id,
            'memo'=>'Void maintenance #'.$row->id.' - '.$data['reason'],
            'posted_by'=>$request->user()->id,
            'source_type'=>'fixed_asset_maintenance_void',
            'source_id'=>$row->id,
        ]);

        $row->update([
            'status'=>'voided',
            'voided_at'=>now(),
            'voided_by'=>$request->user()->id,
            'void_reason'=>$data['reason'],
            'updated_at'=>now(),
        ]);

        return response()->json(['ok'=>true,'message'=>'Maintenance voided and GL reversal posted.','reversal_journal_id'=>$revId]);
    }
}