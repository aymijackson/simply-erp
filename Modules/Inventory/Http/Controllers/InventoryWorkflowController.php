<?php

namespace Modules\Inventory\Http\Controllers;   

use App\Http\Controllers\Controller;

class InventoryWorkflowController extends Controller
{
    public function index()
    {
        return view('inventory.stock.workflow.index');
    }

    // Printable HTML SOP (good for browser printing)
    public function sop()
    {
        return view('inventory.stock.workflow.sop.index');
    }

    // PDF export (requires barryvdh/laravel-dompdf)
    public function sopPdf()
    {
        $pdf = app('dompdf.wrapper')->loadView('inventory.stock.workflow.sop.index', [
            'exportMode' => true
        ]);

        return $pdf->download('inventory-workflow-sop.pdf');
    }
}
