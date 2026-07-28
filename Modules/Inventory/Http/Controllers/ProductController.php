<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Inventory\Models\Product\Brand;
use Modules\Inventory\Models\Product\BrandManufacturer as Manufacturer;
use Modules\Inventory\Models\Product\Category;
use Modules\Inventory\Models\Product\Product;
use Modules\Inventory\Models\Product\ProductAttribute;
use Modules\Inventory\Models\Product\ProductAttributeType;
use Modules\Inventory\Models\Product\ProductAttributeValue;
use Modules\Inventory\Models\Product\ProductImage;
use Modules\Inventory\Models\Product\ProductVariant;
use Modules\Inventory\Models\Product\ProductVariantImage;
use Modules\Inventory\Models\Product\Unit;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    private string $products = 'products';
    private string $variants = 'product_variants';
    private string $attrTypes = 'product_attribute_types';
    private string $productAttributes = 'product_attributes';
    private string $attributeValues = 'product_attribute_values';
    private string $variantAttrValues = 'product_attribute_value_product_variant';

    private function audit(string $action, ?string $description = null, $subject = null, array $meta = []): void
    {
        $module = 'inventory.products';

        auth()->user()?->audit(
            module: $module,
            action: $action,
            description: $description,
            subject: $subject,
            meta: $meta
        );
    }

    private function tableExists(string $name): bool
    {
        static $cache = [];
        if (array_key_exists($name, $cache)) {
            return $cache[$name];
        }

        $cache[$name] = DB::getSchemaBuilder()->hasTable($name);
        return $cache[$name];
    }

    private function productSnapshot(Product $p): array
    {
        $p->loadMissing([
            'brand:id,brand_name,manufacturer_id',
            'unit:id,name,symbol',
            'categories:id,name',
            'images:id,product_id,file_path,is_primary,sort_order',
            'documentLinks:id,document_id,linkable_type,linkable_id,relation_type',
        ]);

        return [
            'id' => $p->id,
            'product_code' => $p->product_code,
            'product_name' => $p->product_name,
            'product_description' => $p->product_description,
            'product_price' => is_null($p->product_price) ? null : (float) $p->product_price,
            'average_cost' => is_null($p->average_cost) ? null : (float) $p->average_cost,
            'product_stock_quantity' => (int) ($p->product_stock_quantity ?? 0),
            'pack_size' => $p->pack_size,
            'is_active' => (int) ($p->is_active ?? 0),

            'brand' => $p->brand ? [
                'id' => $p->brand->id,
                'name' => $p->brand->brand_name,
                'manufacturer_id' => $p->brand->manufacturer_id,
            ] : null,

            'unit' => $p->unit ? [
                'id' => $p->unit->id,
                'name' => $p->unit->name,
                'symbol' => $p->unit->symbol,
            ] : null,

            'category_ids' => $p->categories?->pluck('id')->map(fn ($x) => (int) $x)->values()->toArray() ?? [],
            'category_names' => $p->categories?->pluck('name')->values()->toArray() ?? [],

            'image_path' => $p->image_path,
            'gallery_image_count' => $p->images?->count() ?? 0,
            'document_count' => $p->documentLinks?->count() ?? 0,
        ];
    }

    private function variantSnapshot(ProductVariant $v): array
    {
        $v->loadMissing([
            'product:id,product_name',
            'attributeValues:id,value,product_attribute_id',
            'attributeValues.attribute:id,attribute_type_id',
            'attributeValues.attribute.type:id,name',
            'images:id,product_variant_id,file_path,is_primary,sort_order',
            'documentLinks:id,document_id,linkable_type,linkable_id,relation_type',
        ]);

        $attrIds = $v->attributeValues?->pluck('id')->map(fn ($x) => (int) $x)->values()->toArray() ?? [];

        $attrLabels = $v->attributeValues?->map(function ($val) {
            $typeName = $val->attribute?->type?->name;
            return $typeName ? ($typeName . ' : ' . $val->value) : $val->value;
        })->filter()->values()->toArray() ?? [];

        return [
            'id' => $v->id,
            'product_id' => (int) $v->product_id,
            'product_name' => $v->product?->product_name,
            'sku' => $v->sku,
            'item_type' => $v->item_type,
            'price' => is_null($v->price) ? null : (float) $v->price,
            'stock_quantity' => (int) ($v->stock_quantity ?? 0),
            'reorder_point' => (int) ($v->reorder_point ?? 0),
            'attribute_value_ids' => $attrIds,
            'attribute_labels' => $attrLabels,
            'image_count' => $v->images?->count() ?? 0,
            'document_count' => $v->documentLinks?->count() ?? 0,
        ];
    }

    private function diffAssoc(array $before, array $after): array
    {
        $changes = [];

        foreach ($after as $k => $v) {
            $bv = $before[$k] ?? null;
            if ($v !== $bv) {
                $changes[$k] = ['before' => $bv, 'after' => $v];
            }
        }

        return $changes;
    }

    private function idsDiff(array $beforeIds, array $afterIds): array
    {
        $beforeIds = array_values(array_unique(array_map('intval', $beforeIds)));
        $afterIds  = array_values(array_unique(array_map('intval', $afterIds)));

        sort($beforeIds);
        sort($afterIds);

        return [
            'added'   => array_values(array_diff($afterIds, $beforeIds)),
            'removed' => array_values(array_diff($beforeIds, $afterIds)),
        ];
    }

    public function variantsSelect2(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        $q = ProductVariant::query()
            ->select(['product_variants.id', 'product_variants.sku', 'product_variants.product_id'])
            ->with(['product:id,product_name'])
            ->orderBy('product_variants.sku');

        if ($term !== '') {
            $q->where(function ($qq) use ($term) {
                $qq->where('product_variants.sku', 'like', "%{$term}%")
                    ->orWhereHas('product', function ($p) use ($term) {
                        $p->where('product_name', 'like', "%{$term}%");
                    });
            });
        }

        $items = $q->limit(20)->get()->map(function ($v) {
            $pname = optional($v->product)->product_name;
            $label = $v->sku . ($pname ? " — {$pname}" : "");

            return ['id' => $v->id, 'text' => $label];
        });

        return response()->json($items);
    }

    public function brandsByManufacturer($manufacturer_id)
    {
        $brands = Brand::where('manufacturer_id', $manufacturer_id)->pluck('brand_name', 'id');
        return response()->json($brands);
    }

    public function getAttributesByProduct($productId)
    {
        $attributes = ProductAttribute::with('type')
            ->where('product_id', $productId)
            ->get();

        return response()->json($attributes);
    }

    public function index()
    {
        $manufacturers = Manufacturer::all();
        $categories = Category::all();
        $units = Unit::all();
        $products_count = Product::count();

        return view('inventory.products.index', compact('manufacturers', 'categories', 'units', 'products_count'));
    }

    public function datatable(Request $request)
    {
        if ($request->ajax()) {
            $products = Product::with(['brand', 'categories', 'unit', 'primaryImage'])->select('products.*');

            return DataTables::of($products)
                ->addIndexColumn()
                ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
                ->addColumn('image', function ($row) {
                    $url = null;
                
                    if ($row->primaryImage) {
                        $url = $row->primaryImage->file_url;
                    } elseif ($row->image_path) {
                        $url = route('admin.inventory.products.images.legacy', $row->id);
                    }
                
                    return $url
                        ? '<img src="' . e($url) . '" width="50" height="50" style="object-fit:cover;border-radius:6px;">'
                        : 'N/A';
                })
                ->addColumn('brand_name', fn ($row) => $row->brand->brand_name ?? '')
                ->addColumn('category_names', fn ($p) => $p->categories->pluck('name')->join(', '))
                ->addColumn('uom', fn ($row) => $row->unit->name ?? '')
                ->addColumn('action', function ($row) {
                    return '
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="' . route('admin.inventory.products.details', $row->id) . '" class="btn btn-info" title="Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button class="btn btn-warning edit-product" data-id="' . $row->id . '" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="' . route('admin.inventory.products.variants.page', $row->id) . '" class="btn btn-primary" title="Variants">
                                <i class="fas fa-cubes"></i>
                            </a>
                            <button class="btn btn-danger delete-product" data-id="' . $row->id . '" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    ';
                })
                ->rawColumns(['checkbox', 'action', 'image'])
                ->make(true);
        }
    }

    public function select2(Request $r)
    {
        $q = trim($r->input('q', ''));
        $exclude = (array) $r->input('exclude', []);

        $rows = Product::query()
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('product_code', 'like', "%{$q}%")
                        ->orWhere('product_name', 'like', "%{$q}%");
                });
            })
            ->when(!empty($exclude), fn ($qq) => $qq->whereNotIn('id', $exclude))
            ->limit(25)
            ->get(['id', 'product_code', 'product_name']);

        return $rows->map(fn ($p) => [
            'id'   => $p->id,
            'text' => "{$p->product_code} — {$p->product_name}",
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $product = Product::with([
            'categories:id,name',
            'unit:id,name,symbol',
            'brand:id,brand_name,manufacturer_id',
            'brand.manufacturer:id,manufacturer_name',
            'images:id,product_id,file_path,is_primary,sort_order',
            'primaryImage',
        ])->findOrFail($id);
    
        $categoryIds = $product->relationLoaded('categories') && $product->categories
            ? $product->categories->pluck('id')->values()->all()
            : ($product->category_id ? [$product->category_id] : []);
    
        return response()->json([
            'id'                     => $product->id,
            'product_code'           => $product->product_code,
            'product_name'           => $product->product_name,
            'product_description'    => $product->product_description,
            'product_price'          => is_null($product->product_price) ? null : (float) $product->product_price,
            'average_cost'           => is_null($product->average_cost) ? null : (float) $product->average_cost,
            'product_stock_quantity' => (int) ($product->product_stock_quantity ?? 0),
            'pack_size'              => $product->pack_size,
            'is_active'              => (int) ($product->is_active ?? 0),
    
            'unit_id'                => $product->unit_id,
            'brand_id'               => $product->brand_id,
            'manufacturer_id'        => optional($product->brand)->manufacturer_id,
            'category_ids'           => $categoryIds,
    
            'unit' => $product->unit ? [
                'id'     => $product->unit->id,
                'name'   => $product->unit->name,
                'symbol' => $product->unit->symbol,
            ] : null,
    
            'brand' => $product->brand ? [
                'id'               => $product->brand->id,
                'name'             => $product->brand->brand_name,
                'manufacturer_id'  => $product->brand->manufacturer_id,
            ] : null,
    
            'manufacturer' => ($product->brand && $product->brand->manufacturer) ? [
                'id'   => $product->brand->manufacturer->id,
                'name' => $product->brand->manufacturer->manufacturer_name,
            ] : null,
    
            'image_url' => $product->primaryImage?->file_url
                ?? ($product->image_path ? route('admin.inventory.products.images.legacy', $product->id) : null),
    
            'gallery_images_count' => $product->images->count(),
        ]);
    }
    
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_name' => 'required|string|max:255',
            'product_code' => 'required|string|max:255|unique:products,product_code',
            'brand_id'     => 'required|exists:brands,id',
            'unit_id'      => 'required|exists:units,id',

            'category_ids'   => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'exists:categories,id'],

            'product_description' => 'nullable|string',
            'product_price'       => 'nullable|numeric|min:0',
            'average_cost'        => 'nullable|numeric|min:0',
            'pack_size'           => 'nullable|string|max:255',
            'is_active'           => 'nullable|boolean',

            'product_image'       => 'nullable|image|max:4096',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        DB::beginTransaction();
        try {
            $product = Product::create([
                'product_name'        => $data['product_name'],
                'product_code'        => $data['product_code'],
                'product_description' => $data['product_description'] ?? null,
                'product_price'       => $data['product_price'] ?? null,
                'average_cost'        => $data['average_cost'] ?? null,
                'pack_size'           => $data['pack_size'] ?? null,
                'is_active'           => $data['is_active'],
                'brand_id'            => $data['brand_id'],
                'unit_id'             => $data['unit_id'],
            ]);

            if ($request->hasFile('product_image') && $request->file('product_image')->isValid()) {
                $file = $request->file('product_image');
                $path = $file->store('products', 'public');

                $product->image_path = $path;
                $product->save();

                ProductImage::create([
                    'product_id'          => $product->id,
                    'title'               => $file->getClientOriginalName(),
                    'original_file_name'  => $file->getClientOriginalName(),
                    'file_name'           => basename($path),
                    'file_path'           => $path,
                    'file_disk'           => 'public',
                    'mime_type'           => $file->getMimeType(),
                    'file_extension'      => $file->getClientOriginalExtension(),
                    'file_size'           => $file->getSize(),
                    'is_primary'          => 1,
                    'is_active'           => 1,
                    'uploaded_by'         => auth()->id(),
                ]);
            }

            $product->categories()->sync($data['category_ids']);

            DB::commit();

            $after = $this->productSnapshot($product->fresh());

            $this->audit(
                action: 'created',
                description: 'Created product ' . $after['product_name'] . ' (' . $after['product_code'] . ')',
                subject: $product,
                meta: ['product' => $after]
            );

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully.',
                'product' => $product,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(Request $request, $id)
    {
        $product = Product::with('categories', 'images')->findOrFail($id);

        $data = $request->validate([
            'product_name' => 'required|string|max:255',
            'product_code' => 'required|string|max:255|unique:products,product_code,' . $id,
            'brand_id'     => 'required|exists:brands,id',
            'unit_id'      => 'required|exists:units,id',

            'category_ids'   => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'exists:categories,id'],

            'product_description' => 'nullable|string',
            'product_price'       => 'nullable|numeric|min:0',
            'average_cost'        => 'nullable|numeric|min:0',
            'pack_size'           => 'nullable|string|max:255',
            'is_active'           => 'nullable|boolean',

            'product_image'       => 'nullable|image|max:4096',
            'remove_image'        => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $removeImage = $request->boolean('remove_image');

        $before = $this->productSnapshot($product);

        DB::beginTransaction();
        try {
            $product->update([
                'product_name'        => $data['product_name'],
                'product_code'        => $data['product_code'],
                'product_description' => $data['product_description'] ?? null,
                'product_price'       => $data['product_price'] ?? null,
                'average_cost'        => $data['average_cost'] ?? null,
                'pack_size'           => $data['pack_size'] ?? null,
                'is_active'           => $data['is_active'],
                'brand_id'            => $data['brand_id'],
                'unit_id'             => $data['unit_id'],
            ]);

            $product->categories()->sync($data['category_ids']);

            $oldImage = $product->image_path;

            if ($removeImage && $oldImage) {
                Storage::disk('public')->delete($oldImage);

                if ($product->images()->count() === 0) {
                    $product->image_path = null;
                    $product->save();
                }
            }

            if ($request->hasFile('product_image') && $request->file('product_image')->isValid()) {
                $file = $request->file('product_image');

                if ($oldImage) {
                    Storage::disk('public')->delete($oldImage);
                }

                $path = $file->store('products', 'public');
                $product->image_path = $path;
                $product->save();

                ProductImage::create([
                    'product_id'          => $product->id,
                    'title'               => $file->getClientOriginalName(),
                    'original_file_name'  => $file->getClientOriginalName(),
                    'file_name'           => basename($path),
                    'file_path'           => $path,
                    'file_disk'           => 'public',
                    'mime_type'           => $file->getMimeType(),
                    'file_extension'      => $file->getClientOriginalExtension(),
                    'file_size'           => $file->getSize(),
                    'is_primary'          => $product->images()->exists() ? 0 : 1,
                    'is_active'           => 1,
                    'uploaded_by'         => auth()->id(),
                ]);
            }

            DB::commit();

            $after = $this->productSnapshot($product->fresh());
            $changes = $this->diffAssoc($before, $after);

            $this->audit(
                action: 'updated',
                description: 'Updated product ' . $after['product_name'] . ' (' . $after['product_code'] . ')',
                subject: $product,
                meta: [
                    'before'  => $before,
                    'after'   => $after,
                    'changes' => $changes,
                ]
            );

            return response()->json(['success' => true, 'message' => 'Product updated successfully.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy($id)
    {
        $product = Product::with([
            'categories:id,name',
            'brand:id,brand_name',
            'unit:id,name',
            'images',
            'variants.images',
        ])->findOrFail($id);

        $meta = $this->productSnapshot($product);

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        foreach ($product->images as $image) {
            Storage::disk($image->file_disk ?: 'public')->delete($image->file_path);
            $image->delete();
        }

        foreach ($product->variants as $variant) {
            foreach ($variant->images as $image) {
                Storage::disk($image->file_disk ?: 'public')->delete($image->file_path);
                $image->delete();
            }
        }

        $product->delete();

        $this->audit(
            action: 'deleted',
            description: 'Deleted product ' . $meta['product_name'] . ' (' . $meta['product_code'] . ')',
            subject: null,
            meta: ['product' => $meta]
        );

        return response()->json(['success' => true, 'message' => 'Product deleted successfully.']);
    }

    public function bulkDelete(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:products,id'],
        ]);

        $products = Product::with([
            'categories:id,name',
            'brand:id,brand_name',
            'unit:id,name',
            'images',
            'variants.images',
        ])->whereIn('id', $data['ids'])->get();

        $items = $products->map(fn ($p) => $this->productSnapshot($p))->values()->toArray();

        foreach ($products as $product) {
            if (!empty($product->image_path)) {
                Storage::disk('public')->delete($product->image_path);
            }

            foreach ($product->images as $image) {
                Storage::disk($image->file_disk ?: 'public')->delete($image->file_path);
                $image->delete();
            }

            foreach ($product->variants as $variant) {
                foreach ($variant->images as $image) {
                    Storage::disk($image->file_disk ?: 'public')->delete($image->file_path);
                    $image->delete();
                }
            }
        }

        Product::whereIn('id', $data['ids'])->delete();

        $this->audit(
            action: 'bulk.deleted',
            description: 'Bulk deleted products (count: ' . count($data['ids']) . ')',
            subject: null,
            meta: [
                'count' => count($data['ids']),
                'ids'   => $data['ids'],
                'items' => $items,
            ]
        );

        return response()->json(['success' => true, 'message' => 'Selected products deleted successfully.']);
    }

    public function productImages($productId)
    {
        $product = Product::findOrFail($productId);

        $images = ProductImage::where('product_id', $product->id)
            ->ordered()
            ->get();

        return response()->json($images);
    }

    public function uploadProductImages(Request $request, $productId)
    {
        $request->validate([
            'images'   => ['required', 'array', 'min:1'],
            'images.*' => ['required', 'image', 'max:4096'],
        ]);

        $product = Product::findOrFail($productId);

        DB::beginTransaction();
        try {
            foreach ($request->file('images', []) as $file) {
                $path = $file->store('products', 'public');

                ProductImage::create([
                    'product_id'          => $product->id,
                    'title'               => $file->getClientOriginalName(),
                    'original_file_name'  => $file->getClientOriginalName(),
                    'file_name'           => basename($path),
                    'file_path'           => $path,
                    'file_disk'           => 'public',
                    'mime_type'           => $file->getMimeType(),
                    'file_extension'      => $file->getClientOriginalExtension(),
                    'file_size'           => $file->getSize(),
                    'uploaded_by'         => auth()->id(),
                    'is_active'           => 1,
                    'is_primary'          => !$product->images()->exists() && empty($product->image_path),
                ]);

                if (empty($product->image_path)) {
                    $product->image_path = $path;
                    $product->save();
                }
            }

            DB::commit();

            $this->audit(
                action: 'images.uploaded',
                description: 'Uploaded product images for ' . $product->product_name,
                subject: $product,
                meta: ['product_id' => $product->id]
            );

            return response()->json(['success' => true, 'message' => 'Images uploaded successfully']);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function setPrimaryProductImage($id)
    {
        $image = ProductImage::findOrFail($id);
        $image->markAsPrimary();

        $product = $image->product;
        if ($product) {
            $product->image_path = $image->file_path;
            $product->save();
        }

        return response()->json(['success' => true]);
    }

    public function deleteProductImage($id)
    {
        $image = ProductImage::findOrFail($id);
        $product = $image->product;

        Storage::disk($image->file_disk ?: 'public')->delete($image->file_path);
        $image->delete();

        if ($product) {
            $primary = $product->primaryImage()->first();
            $product->image_path = $primary?->file_path;
            $product->save();
        }

        return response()->json(['success' => true]);
    }

    private function streamPublicFile(string $relativePath, ?string $mimeType = null)
    {
        $path = storage_path('app/public/' . ltrim($relativePath, '/'));
    
        abort_unless(is_file($path), 404);
    
        return response()->file($path, [
            'Content-Type' => $mimeType ?: mime_content_type($path) ?: 'application/octet-stream',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
    
    public function viewProductImage($id)
    {
        $image = ProductImage::findOrFail($id);
    
        return $this->streamPublicFile(
            relativePath: $image->file_path,
            mimeType: $image->mime_type
        );
    }
    
    public function viewVariantImage($id)
    {
        $image = ProductVariantImage::findOrFail($id);
    
        return $this->streamPublicFile(
            relativePath: $image->file_path,
            mimeType: $image->mime_type
        );
    }
    
    public function viewLegacyProductImage($productId)
    {
        $product = Product::findOrFail($productId);
    
        abort_unless(!empty($product->image_path), 404);
    
        return $this->streamPublicFile(
            relativePath: $product->image_path,
            mimeType: null
        );
    }

    public function productAttributeTypesIndex()
    {
        return view('inventory.products.attributes.types.index');
    }

    public function productAttributeTypesDatatable()
    {
        return DataTables::of(ProductAttributeType::query())
            ->addColumn('action', function ($row) {
                return '
                    <button class="btn btn-warning btn-sm edit-btn" data-id="' . $row->id . '" data-name="' . e($row->name) . '">Edit</button>
                    <button class="btn btn-danger btn-sm delete-btn" data-id="' . $row->id . '">Delete</button>
                ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function storeProductAttributeType(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:product_attribute_types,name',
        ]);

        $type = ProductAttributeType::create(['name' => $data['name']]);

        $this->audit(
            action: 'attribute_types.created',
            description: 'Created attribute type: ' . $type->name,
            subject: $type,
            meta: ['id' => $type->id, 'name' => $type->name]
        );

        return response()->json(['message' => 'Attribute Type created successfully.']);
    }

    public function updateProductAttributeType(Request $request, $id)
    {
        $type = ProductAttributeType::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:product_attribute_types,name,' . $id,
        ]);

        $before = ['id' => $type->id, 'name' => $type->name];

        $type->update(['name' => $data['name']]);

        $after = ['id' => $type->id, 'name' => $type->name];

        $this->audit(
            action: 'attribute_types.updated',
            description: 'Updated attribute type: ' . $after['name'],
            subject: $type,
            meta: [
                'before' => $before,
                'after' => $after,
                'changes' => $this->diffAssoc($before, $after),
            ]
        );

        return response()->json(['message' => 'Attribute Type updated successfully.']);
    }

    public function destroyProductAttributeTypes($id)
    {
        $type = ProductAttributeType::findOrFail($id);
        $meta = ['id' => $type->id, 'name' => $type->name];

        $type->delete();

        $this->audit(
            action: 'attribute_types.deleted',
            description: 'Deleted attribute type: ' . $meta['name'],
            subject: null,
            meta: $meta
        );

        return response()->json(['message' => 'Attribute Type deleted.']);
    }

    public function productAttributesIndex()
    {
        $products = Product::all();
        $attributeTypes = ProductAttributeType::all();

        return view('inventory.products.attributes.index', compact('products', 'attributeTypes'));
    }

    public function productAttributesDatatable()
    {
        $attributes = ProductAttribute::with(['product', 'type'])->select('product_attributes.*');

        return DataTables::of($attributes)
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="attr-checkbox" value="' . $row->id . '">';
            })
            ->addColumn('product_name', fn ($row) => $row->product->product_name ?? '-')
            ->addColumn('attribute_type_name', fn ($row) => $row->type->name ?? '-')
            ->addColumn('action', function ($row) {
                return '
                    <button class="btn btn-warning btn-sm edit-btn" data-id="' . $row->id . '" data-product-id="' . ($row->product->id ?? '') . '" data-attribute-type-id="' . ($row->type->id ?? '') . '">Edit</button>
                    <button class="btn btn-danger btn-sm delete-btn" data-id="' . $row->id . '">Delete</button>
                ';
            })
            ->rawColumns(['checkbox', 'action'])
            ->make(true);
    }

    public function storeProductAttribute(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'attribute_type_id' => 'required|exists:product_attribute_types,id',
        ]);

        $exists = ProductAttribute::where('product_id', $validated['product_id'])
            ->where('attribute_type_id', $validated['attribute_type_id'])
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'This product already has this attribute type.'], 409);
        }

        $attr = ProductAttribute::create($validated);
        $attr->loadMissing(['product:id,product_name', 'type:id,name']);

        $this->audit(
            action: 'attributes.created',
            description: 'Added attribute type "' . $attr->type?->name . '" to product "' . $attr->product?->product_name . '"',
            subject: $attr,
            meta: [
                'id' => $attr->id,
                'product_id' => $attr->product_id,
                'product_name' => $attr->product?->product_name,
                'attribute_type_id' => $attr->attribute_type_id,
                'attribute_type_name' => $attr->type?->name,
            ]
        );

        return response()->json(['success' => true, 'message' => 'Product attribute created successfully.']);
    }

    public function updateProductAttribute(Request $request, $id)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'attribute_type_id' => 'required|exists:product_attribute_types,id',
        ]);

        $exists = ProductAttribute::where('product_id', $validated['product_id'])
            ->where('attribute_type_id', $validated['attribute_type_id'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'This product already has this attribute type.'], 409);
        }

        $attr = ProductAttribute::with(['product:id,product_name', 'type:id,name'])->findOrFail($id);

        $before = [
            'id' => $attr->id,
            'product_id' => $attr->product_id,
            'product_name' => $attr->product?->product_name,
            'attribute_type_id' => $attr->attribute_type_id,
            'attribute_type_name' => $attr->type?->name,
        ];

        $attr->update($validated);
        $attr->loadMissing(['product:id,product_name', 'type:id,name']);

        $after = [
            'id' => $attr->id,
            'product_id' => $attr->product_id,
            'product_name' => $attr->product?->product_name,
            'attribute_type_id' => $attr->attribute_type_id,
            'attribute_type_name' => $attr->type?->name,
        ];

        $this->audit(
            action: 'attributes.updated',
            description: 'Updated product attribute mapping (#' . $attr->id . ')',
            subject: $attr,
            meta: [
                'before' => $before,
                'after' => $after,
                'changes' => $this->diffAssoc($before, $after),
            ]
        );

        return response()->json(['success' => true, 'message' => 'Product attribute updated successfully.']);
    }

    public function destroyProductAttribute($id)
    {
        $attr = ProductAttribute::with(['product:id,product_name', 'type:id,name'])->findOrFail($id);

        $meta = [
            'id' => $attr->id,
            'product_id' => $attr->product_id,
            'product_name' => $attr->product?->product_name,
            'attribute_type_id' => $attr->attribute_type_id,
            'attribute_type_name' => $attr->type?->name,
        ];

        $attr->delete();

        $this->audit(
            action: 'attributes.deleted',
            description: 'Deleted product attribute mapping (#' . $meta['id'] . ')',
            subject: null,
            meta: $meta
        );

        return response()->json(['message' => 'Attribute deleted.']);
    }

    public function bulkDeleteProductAttribute(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:product_attributes,id'],
        ]);

        $items = ProductAttribute::with(['product:id,product_name', 'type:id,name'])
            ->whereIn('id', $data['ids'])
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'product_id' => $a->product_id,
                'product_name' => $a->product?->product_name,
                'attribute_type_id' => $a->attribute_type_id,
                'attribute_type_name' => $a->type?->name,
            ])->values()->toArray();

        ProductAttribute::whereIn('id', $data['ids'])->delete();

        $this->audit(
            action: 'attributes.bulk.deleted',
            description: 'Bulk deleted product attributes (count: ' . count($data['ids']) . ')',
            subject: null,
            meta: ['count' => count($data['ids']), 'ids' => $data['ids'], 'items' => $items]
        );

        return response()->json(['message' => 'Selected product attributes deleted successfully.']);
    }

    public function productAttributeValuesIndex()
    {
        $attributes = ProductAttribute::with('product', 'type')->get();
        $products = Product::all();

        return view('inventory.products.attributes.values.index', compact('attributes', 'products'));
    }

    public function productAttributeValuesDatatable()
    {
        $query = ProductAttributeValue::query()
            ->with(['attribute.type:id,name', 'attribute.product:id,product_name'])
            ->select('product_attribute_values.*');

        return datatables()->eloquent($query)
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
            ->addColumn('product_name', fn ($row) => $row->attribute?->product?->product_name ?? '—')
            ->addColumn('attribute_type_name', fn ($row) => $row->attribute?->type?->name ?? '—')
            ->addColumn('value', fn ($row) => e($row->value))
            ->addColumn('created_at', fn ($row) => optional($row->created_at)->format('d-m-Y, h:i a'))
            ->addColumn('actions', function ($row) {
                return '
                    <button class="btn btn-sm btn-primary edit-value" data-id="' . $row->id . '" data-value="' . e($row->value) . '">Edit</button>
                    <button class="btn btn-sm btn-danger delete-value" data-id="' . $row->id . '">Delete</button>
                ';
            })
            ->rawColumns(['checkbox', 'actions'])
            ->make(true);
    }

    public function storeProductAttributeValue(Request $request)
    {
        $data = $request->validate([
            'product_attribute_id' => 'required|exists:product_attributes,id',
            'value' => 'required|string|max:255',
        ]);

        $val = ProductAttributeValue::create($data);
        $val->loadMissing(['attribute.product:id,product_name', 'attribute.type:id,name']);

        $this->audit(
            action: 'attribute_values.created',
            description: 'Created attribute value "' . $val->value . '"',
            subject: $val,
            meta: [
                'id' => $val->id,
                'value' => $val->value,
                'product_attribute_id' => $val->product_attribute_id,
                'product_name' => $val->attribute?->product?->product_name,
                'attribute_type_name' => $val->attribute?->type?->name,
            ]
        );

        return response()->json(['success' => true, 'message' => 'Attribute value created successfully.']);
    }

    public function editProductAttributeValue($id)
    {
        $pv = ProductAttributeValue::findOrFail($id);
        return response()->json($pv);
    }

    public function updateProductAttributeValue(Request $request, $id)
    {
        $val = ProductAttributeValue::with(['attribute.product:id,product_name', 'attribute.type:id,name'])->findOrFail($id);

        $data = $request->validate([
            'value' => 'required|string|max:255',
        ]);

        $before = [
            'id' => $val->id,
            'value' => $val->value,
            'product_attribute_id' => $val->product_attribute_id,
            'product_name' => $val->attribute?->product?->product_name,
            'attribute_type_name' => $val->attribute?->type?->name,
        ];

        $val->update(['value' => $data['value']]);
        $val->refresh()->loadMissing(['attribute.product:id,product_name', 'attribute.type:id,name']);

        $after = [
            'id' => $val->id,
            'value' => $val->value,
            'product_attribute_id' => $val->product_attribute_id,
            'product_name' => $val->attribute?->product?->product_name,
            'attribute_type_name' => $val->attribute?->type?->name,
        ];

        $this->audit(
            action: 'attribute_values.updated',
            description: 'Updated attribute value (#' . $val->id . ')',
            subject: $val,
            meta: [
                'before' => $before,
                'after' => $after,
                'changes' => $this->diffAssoc($before, $after),
            ]
        );

        return response()->json(['success' => true, 'message' => 'Attribute value updated successfully.']);
    }

    public function destroyProductAttributeValue($id)
    {
        $val = ProductAttributeValue::with(['attribute.product:id,product_name', 'attribute.type:id,name'])->findOrFail($id);

        $meta = [
            'id' => $val->id,
            'value' => $val->value,
            'product_attribute_id' => $val->product_attribute_id,
            'product_name' => $val->attribute?->product?->product_name,
            'attribute_type_name' => $val->attribute?->type?->name,
        ];

        $val->delete();

        $this->audit(
            action: 'attribute_values.deleted',
            description: 'Deleted attribute value "' . $meta['value'] . '" (#' . $meta['id'] . ')',
            subject: null,
            meta: $meta
        );

        return response()->json(['success' => true, 'message' => 'Attribute value deleted.']);
    }

    public function bulkDeleteProductAttributeValues(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:product_attribute_values,id'],
        ]);

        $items = ProductAttributeValue::with(['attribute.product:id,product_name', 'attribute.type:id,name'])
            ->whereIn('id', $data['ids'])
            ->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'value' => $v->value,
                'product_attribute_id' => $v->product_attribute_id,
                'product_name' => $v->attribute?->product?->product_name,
                'attribute_type_name' => $v->attribute?->type?->name,
            ])->values()->toArray();

        ProductAttributeValue::whereIn('id', $data['ids'])->delete();

        $this->audit(
            action: 'attribute_values.bulk.deleted',
            description: 'Bulk deleted attribute values (count: ' . count($data['ids']) . ')',
            subject: null,
            meta: ['count' => count($data['ids']), 'ids' => $data['ids'], 'items' => $items]
        );

        return response()->json(['success' => true, 'message' => 'Selected attribute values deleted successfully.']);
    }

    public function productVariantsIndex()
    {
        $products = Product::orderBy('product_name')->get(['id', 'product_name']);
        return view('inventory.products.variants.index', compact('products'));
    }

    public function productVariantsDatatable(): JsonResponse
    {
        $query = ProductVariant::query()
            ->with([
                'product:id,product_name',
                'attributeValues.attribute.type:id,name'
            ])
            ->leftJoinSub($this->variantStockSubquery(), 'vs', function ($join) {
                $join->on('vs.product_variant_id', '=', 'product_variants.id');
            })
            ->select([
                'product_variants.id',
                'product_variants.product_id',
                'product_variants.sku',
                'product_variants.item_type',
                'product_variants.price',
                'product_variants.reorder_point',
                DB::raw('COALESCE(vs.qty_on_hand, 0) as qty_on_hand'),
                DB::raw('COALESCE(vs.value_on_hand, 0) as value_on_hand'),
            ]);
    
        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('checkbox', fn ($r) => '<input type="checkbox" class="row-checkbox" value="' . $r->id . '">')
            ->addColumn('product_name', fn ($r) => $r->product?->product_name ?? '—')
            ->addColumn('type', function ($r) {
                return match ($r->item_type) {
                    'raw'     => 'Raw Material',
                    'wip'     => 'Work In Progress',
                    'fg'      => 'Finished Goods',
                    'tool'    => 'Tool',
                    'service' => 'Service',
                    default   => 'Unknown',
                };
            })
            ->addColumn('stock_qty', fn ($r) => number_format((float) ($r->qty_on_hand ?? 0), 2))
            ->addColumn('stock_value', fn ($r) => number_format((float) ($r->value_on_hand ?? 0), 2))
            ->editColumn('price', function ($r) {
                return is_null($r->price) ? '—' : 'NGN ' . number_format((float) $r->price, 2);
            })
            ->addColumn('stock_status', function ($r) {
                $qty = (float) ($r->qty_on_hand ?? 0);
                $reorder = (float) ($r->reorder_point ?? 0);
    
                if ($qty <= 0) {
                    return '<span class="badge bg-danger">Out of Stock</span>';
                }
    
                if ($reorder > 0 && $qty <= $reorder) {
                    return '<span class="badge bg-warning text-dark">Low Stock</span>';
                }
    
                return '<span class="badge bg-success">In Stock</span>';
            })
            ->addColumn('attributes', function ($r) {
                if (!$r->relationLoaded('attributeValues')) {
                    return '';
                }
    
                $parts = [];
                foreach ($r->attributeValues as $val) {
                    $typeName = $val->attribute->type->name ?? null;
                    $label = $typeName ? "{$typeName} : {$val->value}" : $val->value;
                    if ($label !== null && $label !== '') {
                        $parts[] = $label;
                    }
                }
    
                return implode(' | ', $parts);
            })
            ->addColumn('action', fn ($r) => '
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-warning edit-variant" data-id="' . $r->id . '">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger delete-variant" data-id="' . $r->id . '">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            ')
            ->rawColumns(['checkbox', 'action', 'stock_status'])
            ->toJson();
    }

    public function editProductVariant($id)
    {
        $pv = ProductVariant::findOrFail($id);
        return response()->json($pv);
    }

    public function storeProductVariant(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'sku' => 'required|unique:product_variants,sku',
            'price' => 'nullable|numeric|min:0',
            'item_type' => 'required|in:raw,wip,fg,tool,service',
            'stock_quantity' => 'required|integer|min:0',
            'reorder_point' => 'nullable|integer|min:0',
            'attribute_values'   => 'nullable|array',
            'attribute_values.*' => 'exists:product_attribute_values,id',
        ]);

        DB::beginTransaction();
        try {
            $variant = ProductVariant::create([
                'product_id' => $data['product_id'],
                'sku' => $data['sku'],
                'price' => $data['price'] ?? null,
                'item_type' => $data['item_type'],
                'stock_quantity' => $data['stock_quantity'],
                'reorder_point' => $data['reorder_point'] ?? 0,
            ]);

            $attrIds = array_values(array_filter($data['attribute_values'] ?? []));
            if (!empty($attrIds)) {
                $variant->attributeValues()->sync($attrIds);
            }

            DB::commit();

            $after = $this->variantSnapshot($variant->fresh());

            $this->audit(
                action: 'variants.created',
                description: 'Created product variant ' . $after['sku'] . ' for ' . $after['product_name'],
                subject: $variant,
                meta: ['variant' => $after]
            );

            return response()->json([
                'success' => true,
                'message' => 'Product variant created successfully.',
                'variant' => ['id' => $variant->id],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function showVariant(ProductVariant $product_variant): JsonResponse
    {
        $product_variant->load([
            'product:id,product_name',
            'attributeValues.attribute:id,attribute_type_id',
            'attributeValues.attribute.type:id,name',
            'images:id,product_variant_id,file_path,is_primary,sort_order',
        ]);
    
        $stockRow = $this->hasStockView()
            ? DB::table('v_stock_levels')
                ->where('product_variant_id', $product_variant->id)
                ->selectRaw('
                    SUM(COALESCE(qty_on_hand, 0)) as qty_on_hand,
                    SUM(COALESCE(value_on_hand, 0)) as value_on_hand
                ')
                ->first()
            : null;
    
        $selected = $product_variant->attributeValues->map(function ($v) {
            return [
                'type_id'   => (int) ($v->attribute->attribute_type_id ?? $v->attribute->type->id),
                'type_name' => $v->attribute->type->name ?? null,
                'value_id'  => (int) $v->id,
                'value'     => $v->value,
            ];
        })->values();
    
        return response()->json([
            'id'              => $product_variant->id,
            'product_id'      => $product_variant->product_id,
            'sku'             => $product_variant->sku,
            'item_type'       => $product_variant->item_type,
            'price'           => $product_variant->price,
            'stock_quantity'  => (float) ($stockRow->qty_on_hand ?? 0),
            'stock_value'     => (float) ($stockRow->value_on_hand ?? 0),
            'reorder_point'   => $product_variant->reorder_point,
            'selected_attrs'  => $selected,
            'image_count'     => $product_variant->images->count(),
        ]);
    }

    public function attributeMatrix(Product $product): JsonResponse
    {
        $rows = DB::table('product_attributes as pa')
            ->join('product_attribute_types as at', 'at.id', '=', 'pa.attribute_type_id')
            ->leftJoin('product_attribute_values as pav', 'pav.product_attribute_id', '=', 'pa.id')
            ->where('pa.product_id', $product->id)
            ->orderBy('at.name')
            ->orderBy('pav.value')
            ->get([
                'at.id as type_id',
                'at.name as type_name',
                'pa.id as product_attribute_id',
                'pav.id as value_id',
                'pav.value',
            ]);

        $byType = [];
        foreach ($rows as $r) {
            if (!isset($byType[$r->type_id])) {
                $byType[$r->type_id] = [
                    'type_id'   => (int) $r->type_id,
                    'type_name' => $r->type_name,
                    'values'    => [],
                ];
            }

            if ($r->value_id) {
                $byType[$r->type_id]['values'][] = [
                    'id'    => (int) $r->value_id,
                    'value' => $r->value,
                ];
            }
        }

        return response()->json(array_values($byType));
    }

    private function syncVariantAttributes(int $variantId, array $mapTypeToValueId, int $productId): void
    {
        DB::table($this->variantAttrValues)->where('product_variant_id', $variantId)->delete();

        $rows = [];
        foreach ($mapTypeToValueId as $typeId => $valueId) {
            if (!$valueId) {
                continue;
            }

            $rows[] = [
                'product_variant_id' => $variantId,
                'product_attribute_value_id' => (int) $valueId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows) {
            DB::table($this->variantAttrValues)->insert($rows);
        }
    }

    public function updateProductVariant(Request $request, $id)
    {
        $variant = ProductVariant::findOrFail($id);

        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'sku' => 'required|unique:product_variants,sku,' . $id,
            'price' => 'nullable|numeric|min:0',
            'item_type' => 'required|in:raw,wip,fg,tool,service',
            'stock_quantity' => 'required|integer|min:0',
            'reorder_point' => 'nullable|integer|min:0',
            'attribute_values'   => 'nullable|array',
            'attribute_values.*' => 'exists:product_attribute_values,id',
        ]);

        $before = $this->variantSnapshot($variant);

        DB::beginTransaction();
        try {
            $variant->update([
                'product_id' => $data['product_id'],
                'sku' => $data['sku'],
                'price' => $data['price'] ?? null,
                'item_type' => $data['item_type'],
                'stock_quantity' => $data['stock_quantity'],
                'reorder_point' => $data['reorder_point'] ?? 0,
            ]);

            $newAttrIds = array_values(array_filter($data['attribute_values'] ?? []));
            $variant->attributeValues()->sync($newAttrIds);

            DB::commit();

            $after = $this->variantSnapshot($variant->fresh());
            $attrDiff = $this->idsDiff($before['attribute_value_ids'] ?? [], $after['attribute_value_ids'] ?? []);

            $this->audit(
                action: 'variants.updated',
                description: 'Updated product variant ' . $after['sku'] . ' for ' . $after['product_name'],
                subject: $variant,
                meta: [
                    'before' => $before,
                    'after' => $after,
                    'changes' => $this->diffAssoc($before, $after),
                    'attribute_sync' => $attrDiff,
                ]
            );

            return response()->json(['success' => true, 'message' => 'Product variant updated successfully.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroyProductVariant($id)
    {
        $variant = ProductVariant::with('images')->findOrFail($id);
        $meta = $this->variantSnapshot($variant);

        foreach ($variant->images as $image) {
            Storage::disk($image->file_disk ?: 'public')->delete($image->file_path);
            $image->delete();
        }

        $variant->attributeValues()->detach();
        $variant->delete();

        $this->audit(
            action: 'variants.deleted',
            description: 'Deleted product variant ' . $meta['sku'] . ' (' . $meta['product_name'] . ')',
            subject: null,
            meta: ['variant' => $meta]
        );

        return response()->json(['success' => true, 'message' => 'Product variant deleted successfully.']);
    }

    public function bulkDeleteProductVariants(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:product_variants,id'],
        ]);

        $variants = ProductVariant::with(['product:id,product_name', 'attributeValues', 'images'])
            ->whereIn('id', $data['ids'])
            ->get();

        $items = $variants->map(fn ($v) => $this->variantSnapshot($v))->values()->toArray();

        foreach ($variants as $v) {
            foreach ($v->images as $image) {
                Storage::disk($image->file_disk ?: 'public')->delete($image->file_path);
                $image->delete();
            }

            $v->attributeValues()->detach();
        }

        ProductVariant::whereIn('id', $data['ids'])->delete();

        $this->audit(
            action: 'variants.bulk.deleted',
            description: 'Bulk deleted product variants (count: ' . count($data['ids']) . ')',
            subject: null,
            meta: [
                'count' => count($data['ids']),
                'ids' => $data['ids'],
                'items' => $items,
            ]
        );

        return response()->json(['success' => true, 'message' => 'Selected product variants deleted successfully.']);
    }

    public function variantImages($variantId)
    {
        $variant = ProductVariant::findOrFail($variantId);

        $images = ProductVariantImage::where('product_variant_id', $variant->id)
            ->ordered()
            ->get();

        return response()->json($images);
    }

    public function uploadVariantImages(Request $request, $variantId)
    {
        $request->validate([
            'images'   => ['required', 'array', 'min:1'],
            'images.*' => ['required', 'image', 'max:4096'],
        ]);

        $variant = ProductVariant::findOrFail($variantId);

        DB::beginTransaction();
        try {
            foreach ($request->file('images', []) as $file) {
                $path = $file->store('variants', 'public');

                ProductVariantImage::create([
                    'product_variant_id'  => $variant->id,
                    'title'               => $file->getClientOriginalName(),
                    'original_file_name'  => $file->getClientOriginalName(),
                    'file_name'           => basename($path),
                    'file_path'           => $path,
                    'file_disk'           => 'public',
                    'mime_type'           => $file->getMimeType(),
                    'file_extension'      => $file->getClientOriginalExtension(),
                    'file_size'           => $file->getSize(),
                    'uploaded_by'         => auth()->id(),
                    'is_active'           => 1,
                    'is_primary'          => !$variant->images()->exists(),
                ]);
            }

            DB::commit();

            $this->audit(
                action: 'variants.images.uploaded',
                description: 'Uploaded images for variant ' . $variant->sku,
                subject: $variant,
                meta: ['variant_id' => $variant->id]
            );

            return response()->json(['success' => true, 'message' => 'Images uploaded successfully']);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function setPrimaryVariantImage($id)
    {
        $image = ProductVariantImage::findOrFail($id);
        $image->markAsPrimary();

        return response()->json(['success' => true]);
    }

    public function deleteVariantImage($id)
    {
        $image = ProductVariantImage::findOrFail($id);

        Storage::disk($image->file_disk ?: 'public')->delete($image->file_path);
        $image->delete();

        return response()->json(['success' => true]);
    }

    public function details($id)
{
    $product = Product::with([
        'brand.manufacturer',
        'unit',
        'categories',
        'attributes.type',
        'attributes.values',
        'variants.attributeValues.attribute.type',
        'variants.images',
        'images',
        'primaryImage',
        'documentLinks.document',
        'documentLinks.document.category',
        'documentLinks.document.type',
        'documentLinks.document.uploader',
    ])->findOrFail($id);

    $variantIds = $product->variants->pluck('id')->filter()->values()->all();
    $variantCount = count($variantIds);

    $storeStock = collect();
    $stockTotals = (object) [
        'total_qty' => 0,
        'total_value' => 0,
    ];

    if ($this->tableExists('v_stock_levels') && !empty($variantIds)) {
        $storeStock = DB::table('v_stock_levels as vsl')
            ->leftJoin('location_stores as ls', 'ls.id', '=', 'vsl.location_store_id')
            ->whereIn('vsl.product_variant_id', $variantIds)
            ->selectRaw('
                vsl.location_store_id,
                COALESCE(ls.name, CONCAT("Store #", vsl.location_store_id)) as store_name,
                SUM(COALESCE(vsl.qty_on_hand, 0)) as qty_on_hand,
                SUM(COALESCE(vsl.value_on_hand, 0)) as value_on_hand
            ')
            ->groupBy('vsl.location_store_id', 'ls.name')
            ->orderBy('store_name')
            ->get();

        $stockTotals = DB::table('v_stock_levels as vsl')
            ->whereIn('vsl.product_variant_id', $variantIds)
            ->selectRaw('
                SUM(COALESCE(vsl.qty_on_hand, 0)) as total_qty,
                SUM(COALESCE(vsl.value_on_hand, 0)) as total_value
            ')
            ->first() ?: (object) [
                'total_qty' => 0,
                'total_value' => 0,
            ];
    }

    return view('inventory.products.show', compact(
        'product',
        'variantCount',
        'storeStock',
        'stockTotals'
    ));
}

    public function variantsByProductPage($productId)
    {
        $product = Product::with([
            'brand',
            'unit',
            'categories',
            'attributes.type',
            'attributes.values',
            'documentLinks.document',
            'images',
            'primaryImage',
        ])->findOrFail($productId);

        return view('inventory.products.variants.by_product', compact('product'));
    }

    public function productVariantsByProductDatatable(Request $request, $productId): JsonResponse
    {
        $query = ProductVariant::query()
            ->with([
                'product:id,product_name',
                'attributeValues.attribute.type:id,name',
                'images:id,product_variant_id,file_path,is_primary,sort_order',
            ])
            ->leftJoinSub($this->variantStockSubquery(), 'vs', function ($join) {
                $join->on('vs.product_variant_id', '=', 'product_variants.id');
            })
            ->where('product_variants.product_id', $productId)
            ->select([
                'product_variants.id',
                'product_variants.product_id',
                'product_variants.sku',
                'product_variants.item_type',
                'product_variants.price',
                'product_variants.reorder_point',
                'product_variants.created_at',
                DB::raw('COALESCE(vs.qty_on_hand, 0) as qty_on_hand'),
                DB::raw('COALESCE(vs.value_on_hand, 0) as value_on_hand'),
            ]);
    
        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('checkbox', fn ($r) => '<input type="checkbox" class="row-checkbox" value="' . $r->id . '">')
            ->addColumn('attributes', function ($r) {
                $parts = [];
                foreach ($r->attributeValues as $val) {
                    $typeName = $val->attribute?->type?->name;
                    $parts[] = $typeName ? ($typeName . ': ' . $val->value) : $val->value;
                }
                return implode(' | ', $parts);
            })
            ->addColumn('stock_qty', fn ($r) => number_format((float) ($r->qty_on_hand ?? 0), 2))
            ->addColumn('stock_value', fn ($r) => number_format((float) ($r->value_on_hand ?? 0), 2))
            ->addColumn('stock_status', function ($r) {
                $qty = (float) ($r->qty_on_hand ?? 0);
                $reorder = (float) ($r->reorder_point ?? 0);
    
                if ($qty <= 0) {
                    return '<span class="badge bg-danger">Out of Stock</span>';
                }
    
                if ($reorder > 0 && $qty <= $reorder) {
                    return '<span class="badge bg-warning text-dark">Low Stock</span>';
                }
    
                return '<span class="badge bg-success">In Stock</span>';
            })
            ->editColumn('item_type', function ($r) {
                return match ($r->item_type) {
                    'raw'     => 'Raw Material',
                    'wip'     => 'Work In Progress',
                    'fg'      => 'Finished Goods',
                    'tool'    => 'Tool',
                    'service' => 'Service',
                    default   => ucfirst((string) $r->item_type),
                };
            })
            ->editColumn('price', fn ($r) => is_null($r->price) ? '—' : number_format((float) $r->price, 2))
            ->addColumn('action', function ($r) {
                return '
                    <div class="btn-group btn-group-sm" role="group">
                        <button class="btn btn-warning edit-variant" data-id="' . $r->id . '" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger delete-variant" data-id="' . $r->id . '" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['checkbox', 'stock_status', 'action'])
            ->toJson();
    }

    public function quickProductSummary($id): JsonResponse
    {
        $product = Product::with(['brand', 'unit', 'categories'])->findOrFail($id);
    
        $variantCount = ProductVariant::where('product_id', $product->id)->count();
        $stockTotals = $this->productStockTotals($product->id);
    
        return response()->json([
            'product' => [
                'id' => $product->id,
                'product_code' => $product->product_code,
                'product_name' => $product->product_name,
                'brand_name' => $product->brand?->brand_name,
                'unit_name' => $product->unit?->name,
                'categories' => $product->categories->pluck('name')->values(),
                'product_price' => $product->product_price,
                'average_cost' => $product->average_cost,
                'is_active' => (int) $product->is_active,
            ],
            'stats' => [
                'variant_count' => $variantCount,
                'store_qty_total' => (float) ($stockTotals->total_qty ?? 0),
                'store_value_total' => (float) ($stockTotals->total_value ?? 0),
            ]
        ]);
    }
    
    private function hasStockView(): bool
    {
        return $this->tableExists('v_stock_levels');
    }
    
    private function variantStockSubquery()
    {
        return DB::table('v_stock_levels')
            ->selectRaw('
                product_variant_id,
                SUM(COALESCE(qty_on_hand, 0)) as qty_on_hand,
                SUM(COALESCE(value_on_hand, 0)) as value_on_hand
            ')
            ->groupBy('product_variant_id');
    }
    
    private function productStockTotals(int $productId): object
    {
        if (!$this->hasStockView()) {
            return (object) [
                'total_qty' => 0,
                'total_value' => 0,
            ];
        }
    
        return DB::table('v_stock_levels as vsl')
            ->join('product_variants as pv', 'pv.id', '=', 'vsl.product_variant_id')
            ->where('pv.product_id', $productId)
            ->selectRaw('
                SUM(COALESCE(vsl.qty_on_hand, 0)) as total_qty,
                SUM(COALESCE(vsl.value_on_hand, 0)) as total_value
            ')
            ->first() ?: (object) [
                'total_qty' => 0,
                'total_value' => 0,
            ];
    }
    
    private function productStoreStockRows(int $productId)
    {
        if (!$this->hasStockView()) {
            return collect();
        }
    
        return DB::table('v_stock_levels as vsl')
            ->leftJoin('location_stores as ls', 'ls.id', '=', 'vsl.location_store_id')
            ->leftJoin('product_variants as pv', 'pv.id', '=', 'vsl.product_variant_id')
            ->where('pv.product_id', $productId)
            ->selectRaw('
                vsl.location_store_id,
                COALESCE(ls.name, CONCAT("Store #", vsl.location_store_id)) as store_name,
                SUM(COALESCE(vsl.qty_on_hand, 0)) as qty_on_hand,
                SUM(COALESCE(vsl.value_on_hand, 0)) as value_on_hand
            ')
            ->groupBy('vsl.location_store_id', 'ls.name')
            ->orderBy('store_name')
            ->get();
    }
}