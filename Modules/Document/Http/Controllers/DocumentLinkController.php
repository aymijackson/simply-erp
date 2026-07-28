<?php

namespace Modules\Document\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentAudit;
use Modules\Document\Models\DocumentLink;

#[PermissionMiddleware('documents.links.create')]
#[PermissionMiddleware('documents.links.delete')]
class DocumentLinkController extends Controller
{
    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'document_id'    => ['required', 'integer', 'exists:documents,id'],
            'linkable_type'  => ['required', 'string', 'max:255'],
            'linkable_id'    => ['required', 'integer'],
            'relation_type'  => ['nullable', 'string', 'max:100'],
            'remarks'        => ['nullable', 'string', 'max:255'],
        ])->validate();

        $this->guardAllowedType($data['linkable_type']);

        $document = Document::findOrFail($data['document_id']);

        $existing = DocumentLink::where('document_id', $document->id)
            ->where('linkable_type', $data['linkable_type'])
            ->where('linkable_id', $data['linkable_id'])
            ->first();

        if ($existing) {
            return redirect()->back()->with('warning', 'This document is already attached to the selected record.');
        }

        $link = DocumentLink::create([
            'document_id'   => $document->id,
            'linkable_type' => $data['linkable_type'],
            'linkable_id'   => $data['linkable_id'],
            'relation_type' => $data['relation_type'],
            'remarks'       => $data['remarks'],
            'created_by'    => Auth::id(),
        ]);

        $this->audit($document, 'linked', 'Document linked to record', null, [
            'document_link_id' => $link->id,
            'linkable_type'    => $link->linkable_type,
            'linkable_id'      => $link->linkable_id,
            'relation_type'    => $link->relation_type,
            'remarks'          => $link->remarks,
        ]);

        return redirect()->back()->with('success', 'Document attached successfully.');
    }

    public function destroy($id)
    {
        $link = DocumentLink::with('document')->findOrFail($id);

        $document = $link->document;

        $old = [
            'id'            => $link->id,
            'document_id'   => $link->document_id,
            'linkable_type' => $link->linkable_type,
            'linkable_id'   => $link->linkable_id,
            'relation_type' => $link->relation_type,
            'remarks'       => $link->remarks,
        ];

        $link->delete();

        if ($document) {
            $this->audit($document, 'unlinked', 'Document detached from record', $old, null);
        }

        return redirect()->back()->with('success', 'Document detached successfully.');
    }

    protected function guardAllowedType(string $fqcn): void
    {
        $allowed = config('document.documents.linkable_models', []);
    
        if (!class_exists($fqcn)) {
            abort(404, 'Linked model class not found: ' . $fqcn);
        }
    
        if (empty($allowed)) {
            abort(500, 'Document linkable models config is empty. Check config/documents.php and clear config cache.');
        }
    
        if (!in_array($fqcn, $allowed, true)) {
            abort(403, 'This record type is not allowed for document linking: ' . $fqcn);
        }
    }

    protected function audit(Document $document, string $action, ?string $description = null, $oldValues = null, $newValues = null): void
    {
        DocumentAudit::create([
            'document_id'  => $document->id,
            'action'       => $action,
            'description'  => $description,
            'old_values'   => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
            'new_values'   => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
            'performed_by' => Auth::id(),
            'ip_address'   => request()->ip(),
            'user_agent'   => request()->userAgent(),
            'created_at'   => now(),
        ]);
    }
}