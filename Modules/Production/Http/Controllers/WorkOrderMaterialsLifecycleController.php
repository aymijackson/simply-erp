<?php
// Modules/Production/Http/Controllers/WorkOrderMaterialsLifecycleController.php
namespace Modules\Production\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Production\Models\WorkOrderMaterial;
use Modules\Production\Services\WorkOrderPostingService;

class WorkOrderMaterialsLifecycleController extends Controller
{
    private function companyId(Request $r): int
    {
        return (int) ($r->user()->company_id ?? 1);
    }

    // POST: work-orders/materials/{material}/issue
    public function issue(Request $r, WorkOrderMaterial $material)
    {
        abort_unless($material->workOrder->company_id == $this->companyId($r), 404);

        $data = $r->validate([
            'qty'  => 'required|numeric|min:0.000001',
            'note' => 'nullable|string|max:255',
        ]);

        // Remaining that can be issued
        $remaining = (float)$material->planned_qty - (float)$material->issued_qty + (float)$material->returned_qty;
        if ($data['qty'] > $remaining + 1e-9) {
            return response()->json(['success'=>false,'message'=>'Quantity exceeds remaining planned'], 422);
        }

        $companyId = $material->workOrder->company_id;

        try {
            DB::transaction(function () use ($material, $data, $companyId) {
                // Snapshot the unit cost the first time this line is ever issued, so every
                // issue/return on this line - and the WIP total at completion - uses the same cost.
                if ($material->unit_cost === null) {
                    $material->unit_cost = (float) ($material->product_variant->price ?? 0);
                }

                $material->issued_qty  = (float)$material->issued_qty + (float)$data['qty'];
                // optionally append note
                if (!empty($data['note'])) {
                    $material->notes= trim(($material->notes? $material->note."\n" : '').'ISSUE: '.$data['note']);
                }
                $material->save();

                WorkOrderPostingService::postMaterialIssue(
                    $companyId,
                    $material->work_order_id,
                    (float) $data['qty'],
                    (float) $material->unit_cost,
                    'WO#'.$material->work_order_id.' material issue (line '.$material->id.')'
                );
            });
        } catch (\RuntimeException $e) {
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 422);
        }

        return response()->json(['success'=>true,'message'=>'Material issued']);
    }

    // POST: work-orders/materials/{material}/return
    public function return(Request $r, WorkOrderMaterial $material)
    {
        abort_unless($material->workOrder->company_id == $this->companyId($r), 404);

        $data = $r->validate([
            'qty'  => 'required|numeric|min:0.000001',
            'note' => 'nullable|string|max:255',
        ]);

        // Maximum allowable return = issued - returned
        $canReturn = (float)$material->issued_qty - (float)$material->returned_qty;
        if ($data['qty'] > $canReturn + 1e-9) {
            return response()->json(['success'=>false,'message'=>'Quantity exceeds available to return'], 422);
        }

        $companyId = $material->workOrder->company_id;

        try {
            DB::transaction(function () use ($material, $data, $companyId) {
                $material->returned_qty = (float)$material->returned_qty + (float)$data['qty'];
                if (!empty($data['note'])) {
                    $material->notes= trim(($material->notes ? $material->notes."\n" : '').'RETURN: '.$data['note']);
                }
                $material->save();

                WorkOrderPostingService::postMaterialReturn(
                    $companyId,
                    $material->work_order_id,
                    (float) $data['qty'],
                    (float) ($material->unit_cost ?? 0),
                    'WO#'.$material->work_order_id.' material return (line '.$material->id.')'
                );
            });
        } catch (\RuntimeException $e) {
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 422);
        }

        return response()->json(['success'=>true,'message'=>'Material returned']);
    }
}
