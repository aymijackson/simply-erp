<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\BaseController;

class CrmDocsController extends BaseController
{
    public function workflowPrivileges()
    {
        // Optional: audit page view
        $this->audit(
            module: 'crm',
            action: 'docs.workflow_privileges.viewed',
            description: 'Viewed CRM Workflow & Privileges SOP',
            subject: null,
            meta: []
        );

        return view('crm.docs.work_flow.workflow_privileges');
    }

    // OPTIONAL: downloads (place files in public/docs/)
    public function downloadPdf()
    {
        $path = public_path('docs/CRM_Workflow_and_Privileges_SOP.pdf');
        abort_unless(file_exists($path), 404);
        return response()->download($path, 'CRM_Workflow_and_Privileges_SOP.pdf');
    }

    public function downloadHtml()
    {
        $path = public_path('docs/crm_workflow_privileges.html');
        abort_unless(file_exists($path), 404);
        return response()->download($path, 'crm_workflow_privileges.html');
    }
}
