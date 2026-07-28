<?php

namespace Modules\Projects\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;

class ProjectDocsController extends Controller
{
    public function index()
    {
        return view('projects.docs.index', [
            'appName' => config('app.name', 'ERP'),
        ]);
    }

    public function pdf()
    {
        $pdf = Pdf::loadView('projects.docs.pdf', [
            'appName' => config('app.name', 'ERP'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('projects_module_guide.pdf');
    }
}