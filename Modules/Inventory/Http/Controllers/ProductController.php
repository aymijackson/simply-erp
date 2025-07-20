<?php

namespace Modules\Inventory\Http\Controllers;   

use App\Http\Controllers\Controller;
use Modules\Inventory\Models\Product\Product;
use Modules\Inventory\Models\Product\Brand;
use Modules\Inventory\Models\Product\BrandManufacturer as Manufacturer;
use Modules\Inventory\Models\Product\Category;
use Modules\Inventory\Models\Product\ProductAttribute;
use Modules\Inventory\Models\Product\ProductAttributeType;
use Modules\Inventory\Models\Product\ProductAttributeValue;
use Modules\Inventory\Models\Product\ProductVariant;
use Modules\Inventory\Models\Product\Unit;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;


class ProductController extends Controller
{
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
            $products = Product::with(['brand', 'category', 'unit'])->select('products.*');

            return DataTables::of($products)
                ->addIndexColumn()
                ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
                ->addColumn('image', function ($row) {
                    if ($row->image_path) {
                        return '<img src="' . asset('storage/' . $row->image_path) . '" width="50" height="50">';
                    }
                    return 'N/A';
                })
                ->addColumn('brand_name', fn($row) => $row->brand->brand_name ?? '')
                ->addColumn('category_name', fn($row) => $row->category->name ?? '')
                ->addColumn('unit_symbol', fn($row) => $row->unit->symbol ?? '')
                ->addColumn('action', function ($row) {
                    return '<button class="btn btn-sm btn-warning edit" data-id="'.$row->id.'">Edit</button>
                            <button class="btn btn-sm btn-danger delete" data-id="'.$row->id.'">Delete</button>';
                })
                ->rawColumns(['checkbox', 'action', 'image'])
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_name' => 'required|string|max:255',
            'product_code' => 'required|string|max:255|unique:products,product_code',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'unit_id' => 'required|exists:units,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $product = Product::create($request->all());
        if ($request->hasFile('product_image') && $request->file('product_image')->isValid() && $product) {
            $image = $request->file('product_image');
            $filename = time() . '_' . $image->getClientOriginalName();
            $path = $image->storeAs('public/products', $filename);
            $product->image_path = 'products/' . $filename;
        }
        return response()->json(['success' => true, 'message' => 'Product created successfully.', 'product' => $product]);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'product_name' => 'required|string|max:255',
            'product_code' => 'required|string|max:255|unique:products,product_code,'.$id,
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'unit_id' => 'required|exists:units,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $product->update($request->all());

        return response()->json(['success' => true, 'message' => 'Product updated successfully.']);
    }

    public function destroy($id)
    {
        Product::destroy($id);
        return response()->json(['success' => true, 'message' => 'Product deleted successfully.']);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        Product::whereIn('id', $ids)->delete();

        return response()->json(['success' => true, 'message' => 'Selected products deleted successfully.']);
    }

    public function productAttributeTypesIndex() {
        return view('inventory.products.attributes.types.index');
    }
    
    public function productAttributeTypesDatatable() {
        return DataTables::of(ProductAttributeType::query())
            ->addColumn('action', function ($row) {
                return '
                    <button class="btn btn-warning btn-sm edit-btn" data-id="'.$row->id.'" data-name="'.$row->name.'">Edit</button>
                    <button class="btn btn-danger btn-sm delete-btn" data-id="'.$row->id.'">Delete</button>
                ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }
    
    public function storeProductAttributeType(Request $request) {
        $request->validate(['name' => 'required|string|max:255|unique:product_attribute_types,name']);
        ProductAttributeType::create($request->only('name'));
        return response()->json(['message' => 'Attribute Type created successfully.']);
    }
    
    public function updateProductAttributeType(Request $request, $id) {
        $type = ProductAttributeType::findOrFail($id);
        $request->validate(['name' => 'required|string|max:255|unique:product_attribute_types,name,' . $id]);
        $type->update($request->only('name'));
        return response()->json(['message' => 'Attribute Type updated successfully.']);
    }
    
    public function destroyProductAttributeTypes($id) {
        ProductAttributeType::findOrFail($id)->delete();
        return response()->json(['message' => 'Attribute Type deleted.']);
    }

    public function productAttributesIndex() {
        $products = Product::all();
        $attributeTypes = ProductAttributeType::all();
        return view('inventory.products.attributes.index', compact('products', 'attributeTypes'));
    }
    
    public function productAttributesDatatable() {
        $attributes = ProductAttribute::with(['product', 'type'])->select('product_attributes.*');

        return DataTables::of($attributes)
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="attr-checkbox" value="' . $row->id . '">';
            })
            ->addColumn('product_name', fn($row) => $row->product->product_name ?? '-')
            ->addColumn('attribute_type_name', fn($row) => $row->type->name ?? '-')
            ->addColumn('action', function ($row) {
                return '<button class="btn btn-warning btn-sm edit-btn" data-id="'.$row->id.'" data-product-id="'.$row->product->id.'"'. ' data-attribute-type-id="'.$row->type->id.'">Edit</button>
                    <button class="btn btn-danger btn-sm delete-btn" data-id="'.$row->id.'">Delete</button>';
            })
            ->rawColumns(['checkbox', 'action'])
            ->make(true);
    }
    
    public function storeProductAttribute(Request $request) {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'attribute_type_id' => 'required|exists:product_attribute_types,id',
        ]);

        // Check for duplicate
        $exists = ProductAttribute::where('product_id', $validated['product_id'])
            ->where('attribute_type_id', $validated['attribute_type_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This product already has this attribute type.'
            ], 409);
        }

        ProductAttribute::create($validated);

        return response()->json(['success' => true, 'message' => 'Product attribute created successfully.']);

    }
    
    public function updateProductAttribute(Request $request, $id) {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'attribute_type_id' => 'required|exists:product_attribute_types,id',
        ]);
    
        $exists = ProductAttribute::where('product_id', $validated['product_id'])
            ->where('attribute_type_id', $validated['attribute_type_id'])
            ->where('id', '!=', $id)
            ->exists();
    
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This product already has this attribute type.'
            ], 409);
        }
    
        $attr = ProductAttribute::findOrFail($id);
        $attr->update($validated);
    
        return response()->json(['success' => true, 'message' => 'Product attribute updated successfully.']);
        
    }
    
    public function destroyProductAttribute($id) {
        ProductAttribute::findOrFail($id)->delete();
        return response()->json(['message' => 'Attribute Type deleted.']);
    }
    
    public function bulkDeleteProductAttribute(Request $request)
    {
        $ids = $request->ids;

        if (!is_array($ids) || empty($ids)) {
            return response()->json(['message' => 'No attributes selected.'], 400);
        }

        ProductAttribute::whereIn('id', $ids)->delete();

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

    return datatables()->eloquent($query)     // ← helper, always available
        ->addColumn('checkbox',  fn($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
        ->addColumn('product_name',       fn($row) => $row->attribute?->product?->product_name ?? '—')
        ->addColumn('attribute_type_name',fn($row) => $row->attribute?->type?->name ?? '—')
        ->addColumn('value',              fn($row) => e($row->value))
        ->addColumn('created_at',         fn($row) => optional($row->created_at)->format('d-m-Y, h:i a'))
        ->addColumn('actions', function ($row) {
            return '<button class="btn btn-sm btn-primary edit-value" data-id="'.$row->id.'" data-value="'.e($row->value).'">Edit</button>
                    <button class="btn btn-sm btn-danger delete-value" data-id="'.$row->id.'">Delete</button>';
        })
        ->rawColumns(['checkbox','actions'])
        ->make(true);
}


    public function storeProductAttributeValue(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_attribute_id' => 'required|exists:product_attributes,id',
            'value' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        ProductAttributeValue::create($request->all());
        return response()->json(['success' => true, 'message' => 'Attribute value created successfully.']);
    }

    public function editProductAttributeValue($id)
    {
        $pv = ProductAttributeValue::findOrFail($id);
        return response()->json($pv);
    }

    public function updateProductAttributeValue(Request $request, $id)
    {
        $value = ProductAttributeValue::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'value' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $value->update($request->only('value'));
        return response()->json(['success' => true, 'message' => 'Attribute value updated successfully.']);
    }

    public function destroyProductAttributeValue($id)
    {
        $value = ProductAttributeValue::findOrFail($id);
        $value->delete();

        return response()->json(['success' => true, 'message' => 'Attribute value deleted.']);
    }

    public function bulkDeleteProductAttributeValues(Request $request)
    {
        ProductAttributeValue::whereIn('id', $request->ids)->delete();
        return response()->json(['success' => true, 'message' => 'Selected attribute values deleted.']);
    }

    public function productVariantsIndex()
    {
        $products = Product::all();
        $attributeValues = ProductAttributeValue::with('attribute.type')->get();
        return view('inventory.products.variants.index', compact('products', 'attributeValues'));
    }

    public function productVariantsDatatable()
    {
        $query = ProductVariant::query()
        ->with([
            'product:id,product_name',
            'attributeValues.attribute.type:id,name'  // eager‑load everything
        ])
        ->select('product_variants.*');

    return datatables()->eloquent($query)
        ->addColumn('checkbox', fn($row) =>
            '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')

        ->addColumn('product_name', fn($row) =>
            $row->product?->product_name ?? '—')

        ->addColumn('price', fn($row) =>
            'NGN '. number_format($row->price, 2) ?? '—')

        // 👇 new column: Colour : Red | Size : Large
        ->addColumn('attributes', function ($row) {
            return $row->attributeValues->map(function ($val) {
                $type  = $val->attribute->type->name ?? '';
                return $type.' : '.$val->value;
            })->implode(' | ');
        })

            ->addColumn('action', function ($row) {
                return '<button class="btn btn-sm btn-warning edit-btn" data-id="'.$row->id.'">Edit</button>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="'.$row->id.'">Delete</button>';
            })
            ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->rawColumns(['action', 'checkbox'])
            ->make(true);
    }
    
    public function editProductVariant($id)
    {
        $pv = ProductVariant::findOrFail($id);
        return response()->json($pv);
    }

    public function storeProductVariant(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'sku' => 'required|unique:product_variants,sku',
            'price' => 'nullable|numeric',
            'stock_quantity' => 'required|integer|min:0',
            'reorder_point' => 'integer|min:0',
            'attribute_values'   => 'array',
            'attribute_values.*' => 'exists:product_attribute_values,id',
        ]);

        $variant = ProductVariant::create($request->only('product_id', 'sku', 'price', 'stock_quantity'));
        if ($request->has('attribute_values')) {
            $variant->attributeValues()->sync(array_filter($request->input('attribute_values')));

        }

        return response()->json(['success' => true, 'message' => 'Product variant created successfully.']);
    }

    /**
     * Return attribute types + their values available for a given product
     * URL: /admin/inventory/products/{product}/attributes
     */
    // app/Http/Controllers/Admin/Inventory/ProductController.php
    public function attributeMatrix(Product $product): JsonResponse
    {
        // eager‑load each attribute row with its type & values
        $attrs = $product->attributes()
            ->with(['type:id,name', 'values:id,product_attribute_id,value'])
            ->get();
    
        // reshape into the expected JSON
        $payload = $attrs->map(fn ($attr) => [
            'type_id'   => $attr->type->id,
            'type_name' => $attr->type->name,
            'values'    => $attr->values->map(fn ($v) => [
                'id'    => $v->id,
                'value' => $v->value,
            ]),
        ]);
    
        return response()->json($payload);
    }
    
    public function updateProductVariant(Request $request, $id)
    {
        $variant = ProductVariant::findOrFail($id);

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'sku' => 'required|unique:product_variants,sku,'.$id,
            'price' => 'nullable|numeric',
            'stock_quantity' => 'required|integer|min:0',
            'reorder_point' => 'integer|min:0',
            'attribute_values' => 'array'
        ]);

        $variant->update($request->only('product_id', 'sku', 'price', 'stock_quantity', 'reorder_point'));
        $variant->attributeValues()->sync($request->attribute_values);

        return response()->json(['success' => true, 'message' => 'Product variant updated successfully.']);
    }

    public function destroyProductVariant($id)
    {
        $variant = ProductVariant::findOrFail($id);
        $variant->delete();

        return response()->json(['success' => true, 'message' => 'Product variant deleted successfully.']);
    }

    public function bulkDeleteProductVariants(Request $request)
    {
        $ids = $request->ids;
        ProductVariant::whereIn('id', $ids)->delete();

        return response()->json(['success' => true, 'message' => 'Selected product variants deleted successfully.']);
    }


}
