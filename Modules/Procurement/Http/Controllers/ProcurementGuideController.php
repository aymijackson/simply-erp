<?php

namespace Modules\Procurement\Http\Controllers;

use App\Http\Controllers\Controller;

class ProcurementGuideController extends Controller
{
    public function index()
    {
        return view('procurement.guide.index');
    }
}