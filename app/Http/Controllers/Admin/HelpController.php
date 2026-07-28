<?php

// app/Http/Controllers/Admin/HelpController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;

class HelpController extends Controller
{
    public function salesModule()
    {
        return view('help.sales-module');
    }

    public function salesModulePdf()
    {
        $pdf = Pdf::loadView('help.sales-module-pdf')
            ->setPaper('a4', 'portrait');

        return $pdf->download('sales-module-guide.pdf');
    }
}
