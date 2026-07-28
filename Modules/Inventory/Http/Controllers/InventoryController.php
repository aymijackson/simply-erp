<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Inventory\Models\Brand;
use Modules\Inventory\Models\Product\BrandManufacturer;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\RawMaterial;
use Modules\Inventory\Models\ProductInstance;
use Modules\Inventory\Models\StockMovement;
use Modules\Inventory\Models\Supplier;
use Modules\Inventory\Models\Invoice;
use Modules\Inventory\Models\ItemCategory;
use Modules\Inventory\Models\ManufacturingProcess;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;

class InventoryController extends Controller
{
    public function index()
    {
        // Fetch inventory summary data
        $itemsInStock = Product::count();
        $lowStockCount = Product::where('product_stock_quantity', '<', 10)->count(); // Example threshold
        $outOfStockCount = Product::where('product_stock_quantity', 0)->count();
        $newOrders = StockMovement::where('movement_type', 'inbound')->count(); // Adjust based on your logic

        // Fetch recent stock movements
        $recentTransactions = StockMovement::latest()->take(5)->get();

        // Fetch total product instances
        $totalProductInstances = ProductInstance::count();
        $productInstances = ProductInstance::with('product')->latest()->take(5)->get();

        return view('inventory::dashboard', compact(
            'itemsInStock', 'lowStockCount', 'outOfStockCount', 'newOrders', 'recentTransactions', 'totalProductInstances', 'productInstances'
        ));
    }

    /**
     * List all brands.
     */
    
    public function logout(Request $request)
    {
        auth()->logout();
    
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    
        return redirect()->route('login');
    }
    
     public function brands()
     {
        $data['manufacturers'] = BrandManufacturer::all();
         return view('inventory::brands.index', $data);
     }

     
    public function brands_datatable(Request $request)
    {
        $brands = Brand::with('manufacturer')->select(['id', 'manufacturer_id', 'brand_name', 'brand_code']);
    
    return DataTables::of($brands)
        ->addColumn('manufacturer_name', function ($brand) {
            return $brand->manufacturer ? $brand->manufacturer->manufacturer_name : 'N/A';
        })
        ->addColumn('action', function ($brand) {
            return '
                <button class="btn btn-sm btn-warning edit" data-id="' . $brand->id . '">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger delete" data-id="' . $brand->id . '">
                    <i class="fas fa-trash"></i>
                </button>';
        })
        ->rawColumns(['action'])
        ->make(true);
    }
    
    public function store_brand(Request $request)
    {
        // Validate the request data
        $request->validate([
            'manufacturer_id' => 'required|exists:brand_manufacturers,id',
            'brand_name' => 'required|string|max:255|unique:brands,brand_name',
            'brand_code' => 'required|string|max:50|unique:brands,brand_code',
        ]);

        try {
            // Create new brand
            $brand = Brand::create([
                'manufacturer_id' => $request->manufacturer_id,
                'brand_name' => $request->brand_name,
                'brand_code' => $request->brand_code,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Brand added successfully!',
                'brand' => $brand
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving brand: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing brand.
     */
    public function update(Request $request, Brand $brand)
    {
        $validator = Validator::make($request->all(), [
            'manufacturer_id' => 'required|exists:manufacturers,id',
            'brand_name' => 'required|string|max:255|unique:brands,brand_name,' . $brand->id,
            'brand_code' => 'required|string|max:50|unique:brands,brand_code,' . $brand->id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $brand->update([
            'manufacturer_id' => $request->manufacturer_id,
            'brand_name' => $request->brand_name,
            'brand_code' => $request->brand_code,
        ]);

        return response()->json(['success' => true, 'message' => 'Brand updated successfully!', 'brand' => $brand]);
    }

    /**
     * Delete a brand.
     */
    public function destroy_brand(Brand $brand)
    {
        $brand->delete();
        return response()->json(['success' => true, 'message' => 'Brand deleted successfully!']);
    }

     public function manufacturers()
     {
         return view('inventory::manufacturers.index');
     }

     
    public function manufacturers_datatable(Request $request)
    {
        // Fetch all manufacturers (adjust columns as needed)
        $manufacturers = BrandManufacturer::select(['id', 'manufacturer_name'])
                    ->whereNotNull('manufacturer_name')
                    ->where('manufacturer_name', '!=', '');
        return DataTables::of($manufacturers)
            // Map the 'name' attribute to 'manufacturer_name' for the table
            ->addColumn('manufacturer_name', function ($row) {
                return $row->manufacturer_name;
            })
            // Add an 'action' column with edit and delete buttons
            ->addColumn('action', function ($row) {
                $editBtn = '<button class="btn btn-sm btn-primary edit" data-id="' . $row->id . '" data-name="'. $row->manufacturer_name .'">Edit</button>';
                $deleteBtn = '<button class="btn btn-sm btn-danger delete" data-id="' . $row->id . '">Delete</button>';
                return $editBtn . ' ' . $deleteBtn;
            })
            // Ensure that HTML in the action column is rendered
            ->rawColumns(['action'])
            ->make(true);
    }

    
    public function get_manufacturers_metrics()
    {
        $totalManufacturers = BrandManufacturer::count();

        return response()->json([
            'total' => $totalManufacturers,
        ]);
    }

    public function destroy_manufacturer($id)
    {
        try {
            $manufacturer = BrandManufacturer::findOrFail($id);
            $manufacturer->delete();

            return response()->json(['success' => true, 'message' => 'Manufacturer deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error deleting manufacturer.'], 500);
        }
    }

    public function store_manufacturer(Request $request)
    { 
        $validator = Validator::make($request->all(), [
            'manufacturer_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $manufacturer = BrandManufacturer::updateOrCreate(
            ['id' => $request->id], // If `id` exists, update. Otherwise, create.
            ['manufacturer_name' => $request->manufacturer_name]
        );

        return response()->json(['success' => true, 'message' => 'Manufacturer saved successfully.', 'manufacturer' => $manufacturer]);
    }

    /**
     * List all products.
     */
    public function products()
    {
        $products = Product::all();
        return view('inventory::products.index', compact('products'));
    }

    public function raw_materials_categories()
    {
        return view('inventory::raw_materials.categories.index');
    }

    
    public function products_categories_datatable(Request $request)
    {
        // Fetch all categories (adjust columns as needed)
        $categories = ItemCategory::select(['id', 'category_name', 'category_code', 'item_type'])->where('item_type', 'product');

        return DataTables::of($categories)
            // Map the 'name' attribute to 'category_name' for the table
            ->addColumn('category_name', function ($row) {
                return $row->category_name;
            })
            // Map the 'code' attribute to 'category_code'
            ->addColumn('category_code', function ($row) {
                return $row->category_code;
            })
            // Map the 'type' attribute to 'item_type'
            ->addColumn('item_type', function ($row) {
                return $row->item_type;
            })
            // Add an 'action' column with edit and delete buttons
            ->addColumn('action', function ($row) {
                $editBtn = '<button class="btn btn-sm btn-primary edit" data-id="' . $row->id . '">Edit</button>';
                $deleteBtn = '<button class="btn btn-sm btn-danger delete" data-id="' . $row->id . '">Delete</button>';
                return $editBtn . ' ' . $deleteBtn;
            })
            // Ensure that HTML in the action column is rendered
            ->rawColumns(['action'])
            ->make(true);
    }

    
    public function update_products_categories(Request $request)
    {
        // Example: fetch categories from the database
        $categories = ItemCategory::all();

        // Return a view or JSON
        return view('inventory::products.categories.index', compact('categories'));
    }

    /**
     * GET /inventory/raw-materials/categories/metrics
     * Name: categories.metrics
     *
     * Displays metrics for raw materials categories.
     */
    public function update_products_metrics(Request $request)
    {
        // Example: fetch metrics from the database
        $metrics = Metric::all();

        // Return a view or JSON
        return view('inventory.categories.metrics', compact('metrics'));
    }

    public function update_raw_materials_categories(Request $request)
    {
        // Example: fetch categories from the database
        $categories = ItemCategory::all();

        // Return a view or JSON
        return view('inventory::raw_materials.categories.index', compact('categories'));
    }

    /**
     * GET /inventory/raw-materials/categories/metrics
     * Name: categories.metrics
     *
     * Displays metrics for raw materials categories.
     */
    public function update_raw_materials_metrics(Request $request)
    {
        // Example: fetch metrics from the database
        $metrics = Metric::all();

        // Return a view or JSON
        return view('inventory.categories.metrics', compact('metrics'));
    }

    /**
     * POST /inventory/raw-materials/categories
     * Name: categories.update
     *
     * Updates existing category data via POST request.
     * (Typically, this might be a PUT/PATCH route instead.)
     */


    /**
     * POST /inventory/raw-materials/categories
     * Name: categories.store
     *
     * Stores a new category via POST request.
     */
    public function store_raw_materials_categories(Request $request)
    { 
        try{
        // Validate the request data
        $request->validate([
            'name' => 'required|string|max:255|unique:item_categories,category_name',
            'code' => 'required|string|max:255|unique:item_categories,category_code',
            // Add other validation rules as needed
        ]);
    }catch(\Exception $e){ return $e;}
        // Create a new category
        $category = new ItemCategory;
        $category->category_name = $request->name;
        $category->category_code = $request->code;
        $category->item_type = 'raw_material';
        // ... set other fields
        $category->save();

        // Redirect or return response
        return redirect()
            ->back()
            ->with('success', 'New category created successfully.');
    }

    public function raw_materials_metrics()
    {
        return view('inventory::raw_materials.categories.index');
    }

    /**
     * List all raw materials.
     */
    public function raw_materials()
    {

        return view('inventory::raw_materials.index');
    }

    /**
     * Fetch raw materials data for DataTable via AJAX.
     */
    public function raw_materials_datatable(Request $request)
    {
        if ($request->ajax()) {
            $rawMaterials = RawMaterial::with('category')->select('id', 'raw_material_name', 'category_id', 'raw_material_stock_quantity', 'default_uom', 'raw_material_price');

            return DataTables::of($rawMaterials)
                ->addColumn('category', function ($material) {
                    return $material->category->name ?? 'N/A';
                })
                ->editColumn('raw_material_stock_quantity', function ($material) {
                    $stockClass = $material->raw_material_stock_quantity === 0 
                        ? 'bg-danger text-white' 
                        : ($material->raw_material_stock_quantity < 10 ? 'bg-warning' : 'bg-success');
                    
                    return "<span class='badge $stockClass'>{$material->raw_material_stock_quantity}</span>";
                })
                ->editColumn('raw_material_price', function ($material) {
                    return number_format($material->raw_material_price, 2);
                })
                ->addColumn('action', function ($material) {
                    return '
                        <button class="btn btn-sm btn-info edit-btn" data-id="'.$material->id.'">Edit</button>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="'.$material->id.'">Delete</button>
                    ';
                })
                ->rawColumns(['raw_material_stock_quantity', 'action'])
                ->make(true);
        }
    }

    public function raw_materials_categories_datatable(Request $request)
    {
        // Fetch all categories (adjust columns as needed)
        $categories = ItemCategory::select(['id', 'category_name', 'category_code', 'item_type'])->where('item_type', 'raw_material');

        return DataTables::of($categories)
            // Map the 'name' attribute to 'category_name' for the table
            ->addColumn('category_name', function ($row) {
                return $row->category_name;
            })
            // Map the 'code' attribute to 'category_code'
            ->addColumn('category_code', function ($row) {
                return $row->category_code;
            })
            // Map the 'type' attribute to 'item_type'
            ->addColumn('item_type', function ($row) {
                return $row->item_type;
            })
            // Add an 'action' column with edit and delete buttons
            ->addColumn('action', function ($row) {
                $editBtn = '<button class="btn btn-sm btn-primary edit" data-id="' . $row->id . '">Edit</button>';
                $deleteBtn = '<button class="btn btn-sm btn-danger delete" data-id="' . $row->id . '">Delete</button>';
                return $editBtn . ' ' . $deleteBtn;
            })
            // Ensure that HTML in the action column is rendered
            ->rawColumns(['action'])
            ->make(true);
    }

    public function get_raw_materials_metrics()
    {
        $totalRawMaterials = RawMaterial::count();
        $lowStock = RawMaterial::where('raw_material_stock_quantity', '<', 10)->where('raw_material_stock_quantity', '>', 0)->count();
        $outOfStock = RawMaterial::where('raw_material_stock_quantity', '=', 0)->count();

        return response()->json([
            'total' => $totalRawMaterials,
            'lowStock' => $lowStock,
            'outOfStock' => $outOfStock,
        ]);
    }


    public function store_raw_material(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'raw_material_code' => 'required|string|unique:raw_materials,raw_material_code',
            'category_id' => 'required|exists:item_categories,id',
            'group_id' => 'required|exists:item_groups,id',
            'brand_id' => 'nullable|exists:brands,id',
            'generic_id' => 'nullable|exists:generic_raw_materials,id',
            'raw_material_name' => 'required|string|max:255',
            'raw_material_description' => 'nullable|string',
            'raw_material_price' => 'required|numeric|min:0',
            'default_uom' => 'nullable|exists:item_uoms,id',
            'pack_size' => 'nullable|string|max:100',
            'average_cost' => 'nullable|numeric|min:0',
            'single_unit_raw_material_code' => 'nullable|string|max:100',
            'dimension_group' => 'nullable|string|max:100',
            'lot_information' => 'nullable|string|max:255',
            'warranty_terms' => 'nullable|string|max:255',
            'raw_material_stock_quantity' => 'required|integer|min:0',
            'has_instances' => 'boolean',
            'has_lots' => 'boolean',
            'has_attributes' => 'boolean',
        ]);

        // Store raw material
        $rawMaterial = RawMaterial::create([
            'raw_material_code' => $request->raw_material_code,
            'category_id' => $request->category_id,
            'group_id' => $request->group_id,
            'brand_id' => $request->brand_id,
            'generic_id' => $request->generic_id,
            'raw_material_name' => $request->raw_material_name,
            'raw_material_description' => $request->raw_material_description,
            'raw_material_price' => $request->raw_material_price,
            'default_uom' => $request->default_uom,
            'pack_size' => $request->pack_size,
            'average_cost' => $request->average_cost,
            'single_unit_raw_material_code' => $request->single_unit_raw_material_code,
            'dimension_group' => $request->dimension_group,
            'lot_information' => $request->lot_information,
            'warranty_terms' => $request->warranty_terms,
            'raw_material_stock_quantity' => $request->raw_material_stock_quantity,
            'has_instances' => $request->has_instances ?? false,
            'has_lots' => $request->has_lots ?? false,
            'has_attributes' => $request->has_attributes ?? false,
        ]);

        return response()->json([
            'message' => 'Raw Material Added Successfully!',
            'rawMaterial' => $rawMaterial,
        ], 201);
    }


    /**
     * Create a new product.
     */
    public function storeProduct(Request $request)
    {
        $request->validate([
            'product_code' => 'required|unique:products',
            'product_name' => 'required|string',
            'product_price' => 'required|numeric',
            'category_id' => 'required|exists:item_categories,id',
            'group_id' => 'required|exists:item_groups,id',
        ]);

        Product::create($request->all());

        return redirect()->route('inventory.products')->with('success', 'Product added successfully.');
    }

    /**
     * Manage product instances.
     */
    public function productInstances($productId)
    {
        $instances = ProductInstance::where('product_id', $productId)->get();
        return view('inventory::instances.index', compact('instances'));
    }

    /**
     * Add a new product instance.
     */
    public function storeProductInstance(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'serial_number' => 'required|unique:product_instances',
        ]);

        ProductInstance::create($request->all());

        return back()->with('success', 'Product instance added successfully.');
    }

    /**
     * POST /inventory/raw-materials/categories
     * Name: categories.store
     *
     * Stores a new category via POST request.
     */
    public function store_products_categories(Request $request)
    { 
        try{
        // Validate the request data
        $request->validate([
            'name' => 'required|string|max:255|unique:item_categories,category_name',
            'code' => 'required|string|max:255|unique:item_categories,category_code',
            // Add other validation rules as needed
        ]);
    }catch(\Exception $e){ return $e;}
        // Create a new category
        $category = new ItemCategory;
        $category->category_name = $request->name;
        $category->category_code = $request->code;
        $category->item_type = 'product';
        // ... set other fields
        $category->save();

        // Redirect or return response
        return redirect()
            ->back()
            ->with('success', 'New category created successfully.');
    }

    public function get_products_metrics()
    {
        $totalRawMaterials = Product::count();
        $lowStock = Product::where('product_stock_quantity', '<', 10)->where('product_stock_quantity', '>', 0)->count();
        $outOfStock = Product::where('product_stock_quantity', '=', 0)->count();

        return response()->json([
            'total' => $totalRawMaterials,
            'lowStock' => $lowStock,
            'outOfStock' => $outOfStock,
        ]);
    }

    public function get_products_categories_metrics()
    {
        $totalProductsCategories = ItemCategory::where('item_type', 'product')->count();

        return response()->json([
            'total' => $totalProductsCategories,
        ]);
    }


    /**
     * Track stock movements.
     */
    public function stockMovements()
    {
        $movements = StockMovement::latest()->get();
        return view('inventory::stock.movements', compact('movements'));
    }

    /**
     * Record stock movement.
     */
    public function recordStockMovement(Request $request)
    {
        $request->validate([
            'product_instance_id' => 'required|exists:product_instances,id',
            'movement_type' => 'required|in:inbound,outbound',
            'quantity' => 'required|numeric|min:1',
        ]);

        StockMovement::create($request->all());

        return back()->with('success', 'Stock movement recorded.');
    }

    /**
     * Manage suppliers.
     */
    public function suppliers()
    {
        $suppliers = Supplier::all();
        return view('inventory::suppliers.index', compact('suppliers'));
    }

    /**
     * Add a new supplier.
     */
    public function storeSupplier(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:suppliers',
            'contact' => 'required|string',
        ]);

        Supplier::create($request->all());

        return back()->with('success', 'Supplier added successfully.');
    }

    /**
     * Track manufacturing processes.
     */
    public function manufacturingProcesses()
    {
        $processes = ManufacturingProcess::all();
        return view('inventory::manufacturing.index', compact('processes'));
    }

    /**
     * Record a manufacturing process.
     */
    public function storeManufacturingProcess(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'worker_id' => 'required|exists:workers,id',
            'process_details' => 'required|string',
        ]);

        ManufacturingProcess::create($request->all());

        return back()->with('success', 'Manufacturing process recorded.');
    }

    /**
     * Manage invoices.
     */
    public function invoices()
    {
        $invoices = Invoice::all();
        return view('inventory::invoices.index', compact('invoices'));
    }

    /**
     * Generate an invoice.
     */
    public function storeInvoice(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'total_amount' => 'required|numeric',
        ]);

        Invoice::create($request->all());

        return back()->with('success', 'Invoice generated successfully.');
    }
}
