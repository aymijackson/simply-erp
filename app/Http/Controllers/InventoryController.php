<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Inventory\Models\Product\BrandManufacturer;
use Yajra\DataTables\Facades\DataTables;

class InventoryController extends Controller
{
    /**
     * Audit helper (same pattern you used elsewhere)
     */
    private function audit(string $action, ?string $description = null, $subject = null, array $meta = []): void
    {
        $module = 'inventory.brand_manufacturers';

        auth()->user()?->audit(
            module: $module,
            action: $action,
            description: $description,
            subject: $subject,
            meta: $meta
        );
    }

    public function index()
    {
        return view('brands.manufacturers.index');
    }

    public function list(Request $request)
    {
        $query = BrandManufacturer::select('id','name','website','created_at');

        return DataTables::of($query)
            ->addColumn('checkbox', fn($m) => '<input type="checkbox" name="ids[]" value="'.$m->id.'">')
            ->addColumn('actions', fn($m) =>
                '<button class="btn btn-warning btn-sm edit-btn" data-id="'.$m->id.'">Edit</button>
                 <button class="btn btn-danger btn-sm delete-btn" data-id="'.$m->id.'">Delete</button>'
            )
            ->rawColumns(['checkbox','actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => ['required','string','max:255', Rule::unique('brand_manufacturers','name')],
            'website' => ['nullable','url','max:255'],
        ]);

        $bm = BrandManufacturer::create($data);

        $this->audit(
            action: 'created',
            description: 'Created brand manufacturer ' . ($bm->name ?: '#'.$bm->id),
            subject: $bm,
            meta: [
                'id'      => $bm->id,
                'name'    => $bm->name,
                'website' => $bm->website,
            ]
        );

        return response()->json(['message' => 'Brand Manufacturer created']);
    }

    public function edit($id)
    {
        $bm = BrandManufacturer::findOrFail($id);
        return response()->json(['brandManufacturer' => $bm]);
    }

    public function update(Request $request, $id)
    {
        $bm = BrandManufacturer::findOrFail($id);

        $data = $request->validate([
            'name'    => ['required','string','max:255', Rule::unique('brand_manufacturers','name')->ignore($bm->id)],
            'website' => ['nullable','url','max:255'],
        ]);

        $before = $bm->only(['id','name','website']);

        $bm->update($data);

        $after = $bm->fresh()->only(['id','name','website']);

        $this->audit(
            action: 'updated',
            description: 'Updated brand manufacturer ' . ($after['name'] ?: '#'.$bm->id),
            subject: $bm,
            meta: [
                'before' => $before,
                'after'  => $after,
            ]
        );

        return response()->json(['message' => 'Brand Manufacturer updated']);
    }

    public function destroy($id)
    {
        $bm = BrandManufacturer::findOrFail($id);

        $meta = $bm->only(['id','name','website']);

        $bm->delete();

        $this->audit(
            action: 'deleted',
            description: 'Deleted brand manufacturer ' . ($meta['name'] ?: '#'.$meta['id']),
            subject: null,
            meta: $meta
        );

        return response()->json(['message' => 'Brand Manufacturer deleted']);
    }

    public function bulkDelete(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required','array','min:1'],
            'ids.*' => ['integer', Rule::exists('brand_manufacturers','id')],
        ]);

        $items = BrandManufacturer::whereIn('id', $data['ids'])
            ->get(['id','name','website'])
            ->map(fn($x) => $x->only(['id','name','website']))
            ->values();

        BrandManufacturer::whereIn('id', $data['ids'])->delete();

        $this->audit(
            action: 'bulk_deleted',
            description: 'Bulk deleted brand manufacturers (count: '.count($data['ids']).')',
            subject: null,
            meta: [
                'count' => count($data['ids']),
                'ids'   => $data['ids'],
                'items' => $items,
            ]
        );

        return response()->json(['message' => 'Selected Brand Manufacturers deleted']);
    }
}
