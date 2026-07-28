<?php

namespace Modules\Document\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Document\Models\DocumentCategory;
use Modules\Document\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

#[PermissionMiddleware('documents.types.view')]
#[PermissionMiddleware('documents.types.create')]
#[PermissionMiddleware('documents.types.edit')]
#[PermissionMiddleware('documents.types.delete')]

class DocumentTypeController extends Controller
{

    public function index()
    {
        $types = DocumentType::with('category')->latest()->paginate(20);
        $categories = DocumentCategory::where('is_active', 1)->orderBy('name')->get();

        return view('documents.types.index', compact('types', 'categories'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id'          => ['nullable', 'exists:document_categories,id'],
            'name'                 => ['required', 'string', 'max:150'],
            'code'                 => ['nullable', 'string', 'max:50'],
            'description'          => ['nullable', 'string'],
            'allowed_extensions'   => ['nullable', 'string', 'max:255'],
            'max_file_size_mb'     => ['required', 'integer', 'min:1', 'max:100'],
            'requires_expiry_date' => ['nullable', 'boolean'],
            'is_active'            => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DocumentType::create([
            'company_id'            => Auth::user()->company_id ?? null,
            'category_id'           => $request->category_id,
            'name'                  => $request->name,
            'code'                  => $request->code,
            'description'           => $request->description,
            'allowed_extensions'    => strtolower((string) $request->allowed_extensions),
            'max_file_size_mb'      => $request->max_file_size_mb,
            'requires_expiry_date'  => $request->boolean('requires_expiry_date', false),
            'is_active'             => $request->boolean('is_active', true),
            'created_by'            => Auth::id(),
            'updated_by'            => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Document type created successfully.');
    }

    public function update(Request $request, $id)
    {
        $type = DocumentType::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'category_id'          => ['nullable', 'exists:document_categories,id'],
            'name'                 => ['required', 'string', 'max:150'],
            'code'                 => ['nullable', 'string', 'max:50'],
            'description'          => ['nullable', 'string'],
            'allowed_extensions'   => ['nullable', 'string', 'max:255'],
            'max_file_size_mb'     => ['required', 'integer', 'min:1', 'max:100'],
            'requires_expiry_date' => ['nullable', 'boolean'],
            'is_active'            => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $type->update([
            'category_id'           => $request->category_id,
            'name'                  => $request->name,
            'code'                  => $request->code,
            'description'           => $request->description,
            'allowed_extensions'    => strtolower((string) $request->allowed_extensions),
            'max_file_size_mb'      => $request->max_file_size_mb,
            'requires_expiry_date'  => $request->boolean('requires_expiry_date', false),
            'is_active'             => $request->boolean('is_active', true),
            'updated_by'            => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Document type updated successfully.');
    }

    public function destroy($id)
    {
        $type = DocumentType::findOrFail($id);
        $type->delete();

        return redirect()->back()->with('success', 'Document type deleted successfully.');
    }
}