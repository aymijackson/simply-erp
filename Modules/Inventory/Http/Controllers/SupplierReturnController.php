<?php

// Modules/Inventory/Http/Controllers/SupplierReturnController.php
namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Inventory\Models\StockReturn;
use Modules\Inventory\Services\ReturnService;
use Yajra\DataTables\Facades\DataTables;

class SupplierReturnController extends Controller
{
    public function index() { return view('inventory.returns.customer.index'); }

    public function datatable()
    {
        return DataTables::eloquent(
            StockReturn::customer()->with('store')->latest()
        )->addColumn('actions',fn($r)=>view('inventory.returns.partials.actions',compact('r')))
         ->make();
    }

    public function store(Request $r, ReturnService $svc)
    {
        $hdr = $r->validate([
            'store_id'=>'required|exists:location_stores,id',
            'reason'  =>'nullable|string'
        ]) + ['return_type'=>'customer'];

        $lines = collect($r->input('lines',[]))
                 ->filter(fn($l)=>$l['qty']>0)
                 ->toArray();

        $svc->store($hdr,$lines);
        return back()->with('ok','Saved');
    }

    public function approve(StockReturn $return) { /* ... */ }
    public function post(StockReturn $return, ReturnService $svc)
    {
        $svc->post($return->load('lines.variant'));
        return back()->with('ok','Posted');
    }
}