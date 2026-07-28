<?php

namespace Modules\Document\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentAudit;
use Modules\Document\Models\DocumentType;

#[PermissionMiddleware('documents.versions.create')]
class DocumentVersionController extends Controller
{

    public function store(Request $request, $id)
    {
        $current = Document::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title'                 => ['required', 'string', 'max:255'],
            'category_id'           => ['nullable', 'exists:document_categories,id'],
            'type_id'               => ['nullable', 'exists:document_types,id'],
            'description'           => ['nullable', 'string'],
            'notes'                 => ['nullable', 'string'],
            'status'                => ['required', 'in:draft,active,archived,obsolete'],
            'confidentiality_level' => ['required', 'in:public,internal,restricted,confidential'],
            'effective_date'        => ['nullable', 'date'],
            'expiry_date'           => ['nullable', 'date', 'after_or_equal:effective_date'],
            'file'                  => ['required', 'file', 'max:20480'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $type = $request->filled('type_id') ? DocumentType::find($request->type_id) : null;
        $file = $request->file('file');

        if ($type) {
            $allowed = method_exists($type, 'extensionsArray') ? $type->extensionsArray() : [];

            if (!empty($allowed)) {
                $ext = strtolower($file->getClientOriginalExtension());
                if (!in_array($ext, $allowed, true)) {
                    return redirect()->back()
                        ->withErrors(['file' => 'This file type is not allowed for the selected document type.'])
                        ->withInput();
                }
            }

            if ((bool) $type->requires_expiry_date && empty($request->expiry_date)) {
                return redirect()->back()
                    ->withErrors(['expiry_date' => 'Expiry date is required for the selected document type.'])
                    ->withInput();
            }
        }

        DB::beginTransaction();

        try {
            $root = $current->parent_document_id ? ($current->parent ?: $current) : $current;

            Document::where('id', $root->id)->update(['is_latest' => 0]);
            Document::where('parent_document_id', $root->id)->update(['is_latest' => 0]);

            $disk = config('filesystems.default', 'public');
            $folder = 'documents/' . date('Y/m');
            $extension = strtolower($file->getClientOriginalExtension());
            $storedName = (string) Str::uuid() . ($extension ? '.' . $extension : '');
            $path = $file->storeAs($folder, $storedName, $disk);

            $nextVersionNo = $this->getNextVersionNo($root->id);

            $newVersion = Document::create([
                'company_id'            => $current->company_id,
                'uuid'                  => (string) Str::uuid(),
                'document_no'           => $this->generateVersionDocumentNumber($root, $nextVersionNo),
                'parent_document_id'    => $root->id,
                'version_no'            => $nextVersionNo,
                'is_latest'             => 1,
                'category_id'           => $request->category_id,
                'type_id'               => $request->type_id,
                'title'                 => $request->title,
                'description'           => $request->description,
                'notes'                 => $request->notes,
                'original_file_name'    => $file->getClientOriginalName(),
                'file_name'             => $storedName,
                'file_path'             => $path,
                'file_disk'             => $disk,
                'mime_type'             => $file->getMimeType(),
                'file_extension'        => $extension,
                'file_size'             => $file->getSize(),
                'checksum'              => hash_file('sha256', $file->getRealPath()),
                'status'                => $request->status,
                'confidentiality_level' => $request->confidentiality_level,
                'effective_date'        => $request->effective_date ?: null,
                'expiry_date'           => $request->expiry_date ?: null,
                'uploaded_by'           => Auth::id(),
            ]);

            $links = $root->links()->get();
            foreach ($links as $link) {
                $newVersion->links()->create([
                    'linkable_type' => $link->linkable_type,
                    'linkable_id'   => $link->linkable_id,
                    'relation_type' => $link->relation_type,
                    'remarks'       => $link->remarks,
                    'created_by'    => Auth::id(),
                ]);
            }

            $this->audit($newVersion, 'version_created', 'New document version uploaded', [
                'source_document_id' => $current->id,
                'root_document_id'   => $root->id,
            ], $newVersion->toArray());

            DB::commit();

            return redirect()
                ->route('admin.documents.show', $newVersion->id)
                ->with('success', 'New version uploaded successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Failed to create new version: ' . $e->getMessage())
                ->withInput();
        }
    }

    protected function getNextVersionNo(int $rootId): int
    {
        $maxChild = Document::where('parent_document_id', $rootId)->max('version_no');
        $root = Document::findOrFail($rootId);

        return max((int) $root->version_no, (int) $maxChild) + 1;
    }

    protected function generateVersionDocumentNumber(Document $root): string
    {
        $rootNumber = $root->document_no;
        $nextVersion = $this->getNextVersionNo($root->id);
    
        return $rootNumber . '-V' . $nextVersion;
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