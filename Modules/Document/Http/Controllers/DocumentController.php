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
use Modules\Document\Models\DocumentCategory;
use Modules\Document\Models\DocumentType;

#[PermissionMiddleware('documents.view.index')]
#[PermissionMiddleware('documents.create')]
#[PermissionMiddleware('documents.store')]
#[PermissionMiddleware('documents.edit')]
#[PermissionMiddleware('documents.delete')]
#[PermissionMiddleware('documents.preview')]

class DocumentController extends Controller
{

    public function index(Request $request)
    {
        $query = Document::with(['category', 'type', 'uploader']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('type_id')) {
            $query->where('type_id', $request->type_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('confidentiality_level')) {
            $query->where('confidentiality_level', $request->confidentiality_level);
        }

        if ($request->filled('q')) {
            $search = trim((string) $request->q);
            $query->where(function ($q) use ($search) {
                $q->where('document_no', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('original_file_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $documents = $query->latest()->paginate(20)->withQueryString();

        $categories = DocumentCategory::where('is_active', 1)->orderBy('name')->get();
        $types = DocumentType::where('is_active', 1)->orderBy('name')->get();

        return view('documents.index', compact('documents', 'categories', 'types'));
    }

    public function show($id)
    {
        $document = Document::with([
            'category',
            'type',
            'uploader',
            'approver',
            'versions',
            'links',
        ])->findOrFail($id);
    
        return view('documents.show', compact('document'));
    }

    public function auditData(Document $document)
    {
        $query = $document->audits()
            ->with('user')
            ->select('document_audits.*');
    
        return datatables()
            ->eloquent($query)
            ->editColumn('created_at', fn($row) =>
                $row->created_at?->format('d M Y H:i')
            )
            ->addColumn('user_name', fn($row) =>
                $row->user->name ?? '-'
            )
            ->addColumn('changes', function ($row) {
                if (!$row->old_values && !$row->new_values) {
                    return '-';
                }
    
                $old = json_decode($row->old_values, true) ?: [];
                $new = json_decode($row->new_values, true) ?: [];
    
                $html = '<ul class="mb-0">';
                foreach ($new as $field => $value) {
                    $oldVal = $old[$field] ?? '-';
                    $newVal = $value ?? '-';
    
                    $html .= "<li><strong>{$field}</strong>: {$oldVal} → {$newVal}</li>";
                }
                $html .= '</ul>';
    
                return $html;
            })
            ->rawColumns(['changes'])
            ->make(true);
    }
    
    public function rowTemplate(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:documents,id']
        ]);
    
        $doc = Document::with(['category', 'type', 'uploader'])->findOrFail($request->id);
    
        return view('documents.partials.row', compact('doc'))->render();
    }

    public function store(Request $request)
    {
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
    
            'auto_attach'           => ['nullable', 'boolean'],
            'linkable_type'         => ['nullable', 'string', 'max:255'],
            'linkable_id'           => ['nullable', 'integer'],
            'relation_type'         => ['nullable', 'string', 'max:100'],
            'remarks'               => ['nullable', 'string', 'max:255'],
        ], [
            'file.max' => 'The file size must not exceed 20MB.',
        ]);
    
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors'  => $validator->errors(),
                ], 422);
            }
    
            return redirect()->back()->withErrors($validator)->withInput();
        }
    
        $type = $request->filled('type_id') ? DocumentType::find($request->type_id) : null;
        $file = $request->file('file');
    
        if ($type) {
            $allowedExtensions = method_exists($type, 'extensionsArray') ? $type->extensionsArray() : [];
    
            if (!empty($allowedExtensions)) {
                $ext = strtolower($file->getClientOriginalExtension());
    
                if (!in_array($ext, $allowedExtensions, true)) {
                    $msg = 'This file type is not allowed for the selected document type.';
    
                    if ($request->expectsJson()) {
                        return response()->json([
                            'message' => $msg,
                            'errors'  => ['file' => [$msg]],
                        ], 422);
                    }
    
                    return redirect()->back()->withErrors(['file' => $msg])->withInput();
                }
            }
    
            if ((bool) $type->requires_expiry_date && empty($request->expiry_date)) {
                $msg = 'Expiry date is required for the selected document type.';
    
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => $msg,
                        'errors'  => ['expiry_date' => [$msg]],
                    ], 422);
                }
    
                return redirect()->back()->withErrors(['expiry_date' => $msg])->withInput();
            }
    
            if ((int) $type->max_file_size_mb > 0) {
                $maxBytes = ((int) $type->max_file_size_mb) * 1024 * 1024;
    
                if ($file->getSize() > $maxBytes) {
                    $msg = 'The selected file exceeds the allowed size for this document type (' . $type->max_file_size_mb . 'MB).';
    
                    if ($request->expectsJson()) {
                        return response()->json([
                            'message' => $msg,
                            'errors'  => ['file' => [$msg]],
                        ], 422);
                    }
    
                    return redirect()->back()->withErrors(['file' => $msg])->withInput();
                }
            }
        }
    
        DB::beginTransaction();
    
        try {
            $disk = config('filesystems.default', 'public');
            $folder = 'documents/' . date('Y/m');
            $extension = strtolower($file->getClientOriginalExtension());
            $storedName = (string) Str::uuid() . ($extension ? '.' . $extension : '');
            $path = $file->storeAs($folder, $storedName, $disk);
    
            $document = Document::create([
                'company_id'            => Auth::user()->company_id ?? null,
                'uuid'                  => (string) Str::uuid(),
                'document_no'           => $this->generateDocumentNumber(),
                'parent_document_id'    => null,
                'version_no'            => 1,
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
    
            $linked = false;
            $linkId = null;
    
            if (
                $request->boolean('auto_attach') &&
                $request->filled('linkable_type') &&
                $request->filled('linkable_id')
            ) {
                $allowed = config('document.documents.linkable_models', []);
                $linkableType = $request->linkable_type;
                $linkableId = (int) $request->linkable_id;
    
                if (!class_exists($linkableType)) {
                    throw new \RuntimeException('Linked model class not found: ' . $linkableType);
                }
    
                if (!in_array($linkableType, $allowed, true)) {
                    throw new \RuntimeException('This record type is not allowed for document linking: ' . $linkableType);
                }
    
                $target = $linkableType::find($linkableId);
    
                if (!$target) {
                    throw new \RuntimeException('Target record for document linking was not found.');
                }
    
                $existingLink = $document->links()
                    ->where('linkable_type', $linkableType)
                    ->where('linkable_id', $linkableId)
                    ->first();
    
                if (!$existingLink) {
                    $link = $document->links()->create([
                        'linkable_type' => $linkableType,
                        'linkable_id'   => $linkableId,
                        'relation_type' => $request->relation_type,
                        'remarks'       => $request->remarks,
                        'created_by'    => Auth::id(),
                    ]);
    
                    $linked = true;
                    $linkId = $link->id;
    
                    $this->audit($document, 'linked', 'Document linked to record on upload', null, [
                        'document_link_id' => $link->id,
                        'linkable_type'    => $link->linkable_type,
                        'linkable_id'      => $link->linkable_id,
                        'relation_type'    => $link->relation_type,
                        'remarks'          => $link->remarks,
                    ]);
                }
            }
    
            $this->audit($document, 'created', 'Document uploaded', null, $document->fresh()->toArray());
    
            DB::commit();
    
            if ($request->expectsJson()) {
                return response()->json([
                    'message'      => $linked ? 'Document uploaded and attached successfully.' : 'Document uploaded successfully.',
                    'document_id'  => $document->id,
                    'document_no'  => $document->document_no,
                    'linked'       => $linked,
                    'link_id'      => $linkId,
                    'redirect_url' => route('admin.documents.show', $document->id),
                ]);
            }
    
            return redirect()
                ->route('admin.documents.show', $document->id)
                ->with('success', $linked ? 'Document uploaded and attached successfully.' : 'Document uploaded successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
    
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to upload document.',
                    'error'   => $e->getMessage(),
                ], 500);
            }
    
            return redirect()->back()
                ->with('error', 'Failed to upload document: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        $document = Document::findOrFail($id);
    
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
        ]);
    
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors'  => $validator->errors(),
                ], 422);
            }
    
            return redirect()->back()->withErrors($validator)->withInput();
        }
    
        $type = $request->filled('type_id') ? DocumentType::find($request->type_id) : null;
    
        if ($type && (bool) $type->requires_expiry_date && empty($request->expiry_date)) {
            $msg = 'Expiry date is required for the selected document type.';
    
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $msg,
                    'errors'  => ['expiry_date' => [$msg]],
                ], 422);
            }
    
            return redirect()->back()
                ->withErrors(['expiry_date' => $msg])
                ->withInput();
        }
    
        $oldValues = $document->toArray();
    
        $document->update([
            'category_id'           => $request->category_id,
            'type_id'               => $request->type_id,
            'title'                 => $request->title,
            'description'           => $request->description,
            'notes'                 => $request->notes,
            'status'                => $request->status,
            'confidentiality_level' => $request->confidentiality_level,
            'effective_date'        => $request->effective_date ?: null,
            'expiry_date'           => $request->expiry_date ?: null,
        ]);
    
        $fresh = $document->fresh(['category', 'type', 'uploader']);
    
        $this->audit(
            $document,
            'updated',
            'Document metadata updated',
            $oldValues,
            $fresh->toArray()
        );
    
        if ($request->expectsJson()) {
            return response()->json([
                'message'  => 'Document updated successfully.',
                'document' => [
                    'id'                    => $fresh->id,
                    'document_no'           => $fresh->document_no,
                    'title'                 => $fresh->title,
                    'category'              => optional($fresh->category)->name ?: '-',
                    'type'                  => optional($fresh->type)->name ?: '-',
                    'status'                => $fresh->status,
                    'confidentiality_level' => $fresh->confidentiality_level,
                    'original_file_name'    => $fresh->original_file_name,
                    'human_file_size'       => $fresh->human_file_size,
                    'uploaded_by'           => optional($fresh->uploader)->name ?: '-',
                    'expiry'                => $fresh->expiry_date ? $fresh->expiry_date->format('d M Y') : '-',
                    'description'           => $fresh->description,
                    'notes'                 => $fresh->notes,
                    'category_id'           => $fresh->category_id,
                    'type_id'               => $fresh->type_id,
                    'effective_date'        => $fresh->effective_date ? $fresh->effective_date->format('Y-m-d') : '',
                    'expiry_date'           => $fresh->expiry_date ? $fresh->expiry_date->format('Y-m-d') : '',
                ],
            ]);
        }
    
        return redirect()
            ->route('admin.documents.show', $document->id)
            ->with('success', 'Document updated successfully.');
    }
    
    public function destroy($id)
    {
        $document = Document::findOrFail($id);
    
        try {
            $document->delete();
    
            return response()->json([
                'message' => 'Document deleted successfully.'
            ]);
    
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to delete document.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function download($id)
    {
        $document = Document::findOrFail($id);

        if (!Storage::disk($document->file_disk)->exists($document->file_path)) {
            return redirect()->back()->with('error', 'File not found in storage.');
        }

        $this->audit($document, 'downloaded', 'Document downloaded', null, null);

        return Storage::disk($document->file_disk)->download(
            $document->file_path,
            $document->original_file_name
        );
    }

    public function preview($id)
    {
        $document = Document::findOrFail($id);
    
        if (!Storage::disk($document->file_disk)->exists($document->file_path)) {
            abort(404, 'File not found.');
        }
    
        $this->audit($document, 'previewed', 'Document previewed', null, null);
    
        $ext = strtolower($document->file_extension ?: pathinfo($document->original_file_name, PATHINFO_EXTENSION));
        $mime = $document->mime_type ?: Storage::disk($document->file_disk)->mimeType($document->file_path);
    
        $directPreviewExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'txt'];
    
        if (in_array($ext, $directPreviewExtensions, true)) {
            return response(
                Storage::disk($document->file_disk)->get($document->file_path),
                200,
                [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'inline; filename="' . addslashes($document->original_file_name) . '"',
                ]
            );
        }
    
        $officeExtensions = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
    
        if (in_array($ext, $officeExtensions, true)) {
            $signedUrl = $this->getTemporaryPreviewUrl($document);
    
            return redirect()->away(
                'https://view.officeapps.live.com/op/view.aspx?src=' . urlencode($signedUrl)
            );
        }
    
        return redirect()->route('admin.documents.download', $document->id);
    }

    protected function getTemporaryPreviewUrl(Document $document): string
    {
        return \URL::temporarySignedRoute(
            'documents.public-preview',
            now()->addMinutes(10),
            ['id' => $document->id]
        );
    }
    
    public function publicPreview($id)
    {
        $document = Document::findOrFail($id);
    
        if (!request()->hasValidSignature()) {
            abort(403, 'Invalid or expired preview link.');
        }
    
        if (!Storage::disk($document->file_disk)->exists($document->file_path)) {
            abort(404, 'File not found.');
        }
    
        $mime = $document->mime_type ?: Storage::disk($document->file_disk)->mimeType($document->file_path);
    
        return response()->stream(function () use ($document) {
            $stream = Storage::disk($document->file_disk)->readStream($document->file_path);
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . addslashes($document->original_file_name) . '"',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    protected function getPublicPreviewUrl($document): ?string
    {
        if ($document->file_disk === 'public') {
            return url('/storage/' . ltrim($document->file_path, '/'));
        }
    
        try {
            $rawUrl = Storage::disk($document->file_disk)->url($document->file_path);
    
            if (str_starts_with($rawUrl, 'http://') || str_starts_with($rawUrl, 'https://')) {
                return $rawUrl;
            }
    
            return url($rawUrl);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function generateDocumentNumber(): string
    {
        $prefix = 'DOC-' . now()->format('Ym') . '-';
    
        $docNumbers = \Modules\Document\Models\Document::withTrashed()
            ->whereNull('parent_document_id')
            ->where('document_no', 'like', $prefix . '%')
            ->lockForUpdate()
            ->pluck('document_no');
    
        $maxSequence = 0;
    
        foreach ($docNumbers as $docNo) {
            if (preg_match('/^DOC-\d{6}-(\d{5})$/', $docNo, $matches)) {
                $seq = (int) $matches[1];
                if ($seq > $maxSequence) {
                    $maxSequence = $seq;
                }
            }
        }
    
        return $prefix . str_pad((string) ($maxSequence + 1), 5, '0', STR_PAD_LEFT);
    }
    
    protected function audit(
        Document $document,
        string $action,
        ?string $description = null,
        $oldValues = null,
        $newValues = null
    ): void {
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