<?php

namespace Modules\Document\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Document\Models\DocumentCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Spatie\Permission\Middlewares\PermissionMiddleware;

#[PermissionMiddleware('documents.categories.view.index')]
#[PermissionMiddleware('documents.categories.create')]
#[PermissionMiddleware('documents.categories.edit')]
#[PermissionMiddleware('documents.categories.delete')]
class DocumentCategoryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = DocumentCategory::query()->orderByDesc('id');

            return DataTables::of($query)

                ->addColumn('actions', function ($row) {
                    $buttons = '';

                    if (Auth::user()->can('documents.categories.edit')) {
                        $buttons .= '
                            <button class="btn btn-sm btn-outline-warning editCategoryBtn"
                                data-id="'.$row->id.'"
                                data-name="'.$row->name.'"
                                data-code="'.$row->code.'"
                                data-description="'.$row->description.'"
                                data-active="'.$row->is_active.'">
                                <i class="fas fa-edit"></i>
                            </button>
                        ';
                    }

                    if (Auth::user()->can('documents.categories.delete')) {
                        $buttons .= '
                            <button class="btn btn-sm btn-outline-danger deleteCategoryBtn"
                                data-id="'.$row->id.'">
                                <i class="fas fa-trash"></i>
                            </button>
                        ';
                    }

                    return $buttons;
                })

                ->rawColumns(['actions'])
                ->make(true);
        }

        return view('documents.categories.index');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => ['required', 'string', 'max:150'],
            'code'        => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        DocumentCategory::create([
            'company_id'   => Auth::user()->company_id ?? null,
            'name'         => $request->name,
            'code'         => $request->code,
            'description'  => $request->description,
            'is_active'    => $request->boolean('is_active', true),
            'created_by'   => Auth::id(),
            'updated_by'   => Auth::id(),
        ]);

        return response()->json(['status' => 'success']);
    }

    public function update(Request $request, $id)
    {
        $category = DocumentCategory::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'        => ['required', 'string', 'max:150'],
            'code'        => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $category->update([
            'name'        => $request->name,
            'code'        => $request->code,
            'description' => $request->description,
            'is_active'   => $request->boolean('is_active', true),
            'updated_by'  => Auth::id(),
        ]);

        return response()->json(['status' => 'success']);
    }

    public function destroy($id)
    {
        $category = DocumentCategory::findOrFail($id);
        $category->delete();

        return response()->json(['status' => 'success']);
    }
}