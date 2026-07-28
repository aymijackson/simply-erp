<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Inventory\Models\Product\BrandManufacturer;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ManufacturerController extends Controller
{
    public function index()
    {
        return view('inventory.products.manufacturers.index');
    }

    public function datatable(Request $request)
    {
        $manufacturers = BrandManufacturer::select(['id', 'manufacturer_name'])
            ->whereNotNull('manufacturer_name')
            ->where('manufacturer_name', '!=', '');

        return DataTables::of($manufacturers)
            ->addIndexColumn()
            ->addColumn('checkbox', fn($row) =>
                '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">'
            )
            ->addColumn('manufacturer_name', fn($row) => e($row->manufacturer_name))
            ->addColumn('action', function ($row) {
                $editBtn = '<button class="btn btn-sm btn-primary edit" data-id="'.$row->id.'" data-name="'.e($row->manufacturer_name).'">Edit</button>';
                $deleteBtn = '<button class="btn btn-sm btn-danger delete" data-id="'.$row->id.'">Delete</button>';
                return $editBtn.' '.$deleteBtn;
            })
            ->rawColumns(['checkbox', 'action'])
            ->make(true);
    }

    public function metrics()
    {
        return response()->json(['total' => BrandManufacturer::count()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'manufacturer_name' => ['required','string','max:255', Rule::unique('brand_manufacturers','manufacturer_name')],
        ]);

        $manufacturer = BrandManufacturer::create([
            'manufacturer_name' => $data['manufacturer_name'],
        ]);

        // audit: created
        $this->audit(
            action: 'created',
            description: 'Created manufacturer '.$manufacturer->manufacturer_name,
            subject: $manufacturer,
            meta: [
                'id' => $manufacturer->id,
                'manufacturer_name' => $manufacturer->manufacturer_name,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Manufacturer created successfully.',
            'manufacturer' => $manufacturer
        ]);
    }

    public function update(Request $request, BrandManufacturer $manufacturer)
    {
        $data = $request->validate([
            'manufacturer_name' => [
                'required','string','max:255',
                Rule::unique('brand_manufacturers','manufacturer_name')->ignore($manufacturer->id),
            ],
        ]);

        // BEFORE snapshot
        $before = [
            'id' => $manufacturer->id,
            'manufacturer_name' => $manufacturer->manufacturer_name,
        ];

        $manufacturer->update([
            'manufacturer_name' => $data['manufacturer_name'],
        ]);

        $manufacturerFresh = $manufacturer->fresh();

        // AFTER snapshot
        $after = [
            'id' => $manufacturerFresh->id,
            'manufacturer_name' => $manufacturerFresh->manufacturer_name,
        ];

        // Only log if something actually changed (optional but nice)
        $changes = array_diff_assoc($after, $before);

        $this->audit(
            action: 'updated',
            description: 'Updated manufacturer '.$manufacturerFresh->manufacturer_name.' (#'.$manufacturerFresh->id.')',
            subject: $manufacturerFresh,
            meta: [
                'before' => $before,
                'after'  => $after,
                'changes'=> $changes, // shows only changed keys
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Manufacturer updated successfully.',
            'manufacturer' => $manufacturerFresh
        ]);
    }

    public function destroy($id)
    {
        $manufacturer = BrandManufacturer::findOrFail($id);

        $meta = [
            'id' => $manufacturer->id,
            'manufacturer_name' => $manufacturer->manufacturer_name,
        ];

        $manufacturer->delete();

        $this->audit(
            action: 'deleted',
            description: 'Deleted manufacturer '.$meta['manufacturer_name'].' (#'.$meta['id'].')',
            subject: null,
            meta: $meta
        );

        return response()->json(['success' => true, 'message' => 'Manufacturer deleted successfully.']);
    }

    public function bulkDelete(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required','array','min:1'],
            'ids.*' => ['integer', Rule::exists('brand_manufacturers','id')],
        ]);

        $items = BrandManufacturer::whereIn('id', $data['ids'])
            ->get(['id','manufacturer_name']);

        BrandManufacturer::whereIn('id', $data['ids'])->delete();

        $this->audit(
            action: 'bulk.deleted',
            description: 'Bulk deleted manufacturers (count: '.count($data['ids']).')',
            subject: null,
            meta: [
                'count' => count($data['ids']),
                'ids' => $data['ids'],
                'items' => $items->map(fn($m) => [
                    'id' => $m->id,
                    'manufacturer_name' => $m->manufacturer_name,
                ])->values()->toArray(),
            ]
        );

        return response()->json(['message' => 'Selected manufacturers deleted.']);
    }

    private function audit(string $action, ?string $description = null, $subject = null, array $meta = []): void
    {
        // module label for this controller
        $module = 'inventory.manufacturers';

        auth()->user()?->audit(
            module: $module,
            action: $action,
            description: $description,
            subject: $subject,
            meta: $meta
        );
    }
}
