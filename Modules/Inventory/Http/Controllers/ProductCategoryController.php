<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Inventory\Models\Product\Category;
use Modules\Inventory\Models\Product\Product;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductCategoryController extends Controller
{
    public function index()
    {
        return view('inventory.products.categories.index');
    }

    public function datatable(Request $request)
    {
        $categories = Category::select(['id', 'name', 'description'])
            ->whereNotNull('name')
            ->where('name', '!=', '');

        return DataTables::of($categories)
            ->addIndexColumn()
            ->addColumn('checkbox', fn($row) =>
                '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">'
            )
            ->addColumn('name', function ($row) {
                return '<a href="'.route('admin.inventory.products.categories.show', ['category' => $row->id]).'">'.e($row->name).'</a>';
            })
            ->addColumn('description', fn($row) => e($row->description))
            ->addColumn('action', function ($row) {
                $editBtn = '<button class="btn btn-sm btn-primary edit"
                              data-id="'.$row->id.'"
                              data-name="'.e($row->name).'"
                              data-description="'.e($row->description).'">Edit</button>';

                $showBtn = '<a href="'.route('admin.inventory.products.categories.show', ['category' => $row->id]).'"
                              class="btn btn-sm btn-info">View</a>';

                $deleteBtn = '<button class="btn btn-sm btn-danger delete" data-id="'.$row->id.'">Delete</button>';

                return $editBtn.' '.$showBtn.' '.$deleteBtn;
            })
            ->rawColumns(['checkbox', 'name', 'action'])
            ->make(true);
    }

    public function show(Category $category)
    {
        $category->load(['products:id,product_code,product_name,brand_id,unit_id,product_price']);

        return view('inventory.products.categories.show', [
            'category' => $category,
        ]);
    }

    public function attachProducts(Request $request, Category $category)
    {
        $data = $request->validate([
            'product_ids'   => ['required','array','min:1'],
            'product_ids.*' => ['integer', Rule::exists('products','id')],
        ]);

        // BEFORE snapshot
        $beforeIds = $category->products()->pluck('products.id')->map(fn($x)=>(int)$x)->values()->toArray();

        $category->products()->syncWithoutDetaching($data['product_ids']);

        // AFTER snapshot
        $afterIds = $category->fresh()->products()->pluck('products.id')->map(fn($x)=>(int)$x)->values()->toArray();

        $added = array_values(array_diff($afterIds, $beforeIds));

        $this->audit(
            action: 'products.attached',
            description: 'Attached products to category '.$category->name.' (#'.$category->id.')',
            subject: $category,
            meta: [
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                ],
                'requested_product_ids' => $data['product_ids'],
                'before_product_ids' => $beforeIds,
                'after_product_ids' => $afterIds,
                'added_product_ids' => $added,
            ]
        );

        return response()->json(['message' => 'Products attached to category.']);
    }

    public function detachProduct(Category $category, Product $product)
    {
        // BEFORE snapshot
        $beforeIds = $category->products()->pluck('products.id')->map(fn($x)=>(int)$x)->values()->toArray();

        $category->products()->detach($product->id);

        // AFTER snapshot
        $afterIds = $category->fresh()->products()->pluck('products.id')->map(fn($x)=>(int)$x)->values()->toArray();

        $this->audit(
            action: 'product.detached',
            description: 'Detached product '.$product->product_name.' (#'.$product->id.') from category '.$category->name.' (#'.$category->id.')',
            subject: $category,
            meta: [
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                ],
                'product' => [
                    'id' => $product->id,
                    'product_code' => $product->product_code ?? null,
                    'product_name' => $product->product_name ?? null,
                ],
                'before_product_ids' => $beforeIds,
                'after_product_ids' => $afterIds,
                'removed_product_id' => $product->id,
            ]
        );

        return response()->json(['message' => 'Product detached.']);
    }

    public function metrics()
    {
        return response()->json(['total' => Category::count()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255', Rule::unique('categories','name')],
            'description' => ['nullable','string','max:255'],
        ]);

        $category = Category::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        $this->audit(
            action: 'created',
            description: 'Created category '.$category->name.' (#'.$category->id.')',
            subject: $category,
            meta: [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'category' => $category
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => [
                'required','string','max:255',
                Rule::unique('categories','name')->ignore($category->id),
            ],
            'description' => ['nullable','string','max:255'],
        ]);

        // BEFORE snapshot
        $before = [
            'id' => $category->id,
            'name' => $category->name,
            'description' => $category->description,
        ];

        $category->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        $categoryFresh = $category->fresh();

        // AFTER snapshot
        $after = [
            'id' => $categoryFresh->id,
            'name' => $categoryFresh->name,
            'description' => $categoryFresh->description,
        ];

        $changes = array_diff_assoc($after, $before);

        $this->audit(
            action: 'updated',
            description: 'Updated category '.$categoryFresh->name.' (#'.$categoryFresh->id.')',
            subject: $categoryFresh,
            meta: [
                'before' => $before,
                'after' => $after,
                'changes' => $changes,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'category' => $categoryFresh
        ]);
    }

    public function destroy($id)
    {
        $category = Category::withCount('products')->findOrFail($id);

        $meta = [
            'id' => $category->id,
            'name' => $category->name,
            'description' => $category->description,
            'products_count' => (int) ($category->products_count ?? 0),
        ];

        $category->delete();

        $this->audit(
            action: 'deleted',
            description: 'Deleted category '.$meta['name'].' (#'.$meta['id'].')',
            subject: null,
            meta: $meta
        );

        return response()->json(['success' => true, 'message' => 'Category deleted successfully.']);
    }

    public function bulkDelete(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required','array','min:1'],
            'ids.*' => ['integer', Rule::exists('categories','id')],
        ]);

        $items = Category::whereIn('id', $data['ids'])
            ->withCount('products')
            ->get(['id','name','description']);

        Category::whereIn('id', $data['ids'])->delete();

        $this->audit(
            action: 'bulk.deleted',
            description: 'Bulk deleted categories (count: '.count($data['ids']).')',
            subject: null,
            meta: [
                'count' => count($data['ids']),
                'ids'   => $data['ids'],
                'items' => $items->map(fn($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'description' => $c->description,
                    'products_count' => (int) ($c->products_count ?? 0),
                ])->values()->toArray(),
            ]
        );

        return response()->json(['message' => 'Selected categories deleted.']);
    }

    private function audit(string $action, ?string $description = null, $subject = null, array $meta = []): void
    {
        $module = 'inventory.product_categories';

        auth()->user()?->audit(
            module: $module,
            action: $action,
            description: $description,
            subject: $subject,
            meta: $meta
        );
    }
}
