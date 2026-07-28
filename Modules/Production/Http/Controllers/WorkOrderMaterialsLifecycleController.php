<?php
// Modules/Production/Http/Controllers/WorkOrderMaterialsLifecycleController.php
namespace Modules\Production\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Production\Models\WorkOrderMaterial;

class WorkOrderMaterialsLifecycleController extends Controller
{
    // POST: work-orders/materials/{material}/issue
    public function issue(Request $r, WorkOrderMaterial $material)
    {
        $data = $r->validate([
            'qty'  => 'required|numeric|min:0.000001',
            'note' => 'nullable|string|max:255',
        ]);

        // Remaining that can be issued
        $remaining = (float)$material->planned_qty - (float)$material->issued_qty + (float)$material->returned_qty;
        if ($data['qty'] > $remaining + 1e-9) {
            return response()->json(['success'=>false,'message'=>'Quantity exceeds remaining planned'], 422);
        }

        DB::transaction(function () use ($material, $data) {
            $material->issued_qty  = (float)$material->issued_qty + (float)$data['qty'];
            // optionally append note
            if (!empty($data['note'])) {
                $material->notes= trim(($material->notes? $material->note."\n" : '').'ISSUE: '.$data['note']);
            }
            $material->save();

            // TODO: if you keep a stock ledger, insert a ledger row here
        });

        return response()->json(['success'=>true,'message'=>'Material issued']);
    }

    // POST: work-orders/materials/{material}/return
    public function return(Request $r, WorkOrderMaterial $material)
    {
        $data = $r->validate([
            'qty'  => 'required|numeric|min:0.000001',
            'note' => 'nullable|string|max:255',
        ]);

        // Maximum allowable return = issued - returned
        $canReturn = (float)$material->issued_qty - (float)$material->returned_qty;
        if ($data['qty'] > $canReturn + 1e-9) {
            return response()->json(['success'=>false,'message'=>'Quantity exceeds available to return'], 422);
        }

        DB::transaction(function () use ($material, $data) {
            $material->returned_qty = (float)$material->returned_qty + (float)$data['qty'];
            if (!empty($data['note'])) {
                $material->notes= trim(($material->notes ? $material->notes."\n" : '').'RETURN: '.$data['note']);
            }
            $material->save();

            // TODO: if you keep a stock ledger, insert a ledger row here
        });

        return response()->json(['success'=>true,'message'=>'Material returned']);
    }
}
