<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\Supplier;
use App\Models\SupplierAddress;
use App\Models\SupplierContact;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    /**
     * Display the supplier management view.
     */
    public function index()
    {
        $suppliers_count = Supplier::count();
        return view('inventory.suppliers.index', compact('suppliers_count'));
    }

    /**
     * Return supplier data for DataTables.
     */
    public function datatable(Request $request)
    {
        $suppliers = Supplier::query();

        return DataTables::of($suppliers)
            ->addColumn('checkbox', fn ($supplier) => '<input type="checkbox" class="supplier_checkbox" value="' . $supplier->id . '">')
            ->addColumn('action', function ($supplier) {
                return '<button class="btn btn-sm btn-info edit" data-id="' . $supplier->id . '" data-name="' . $supplier->name . '" data-status="' . $supplier->status . '" data-currency="' . $supplier->default_currency . '" data-payment_terms="' . $supplier->payment_terms . '" data-lead_time_days="' . $supplier->lead_time_days . '" data-rating="' . $supplier->rating . '"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger delete" data-id="' . $supplier->id . '"><i class="fas fa-trash"></i></button>';
            })
            ->rawColumns(['checkbox', 'action'])
            ->make(true);
    }

    /**
     * Store or update a supplier.
     */
        public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255|unique:suppliers,name',
            'status' => 'required|in:active,inactive,pending',
            'default_currency' => 'required|string|max:3',
            'payment_terms' => 'nullable|string|max:255',
            'lead_time_days' => 'nullable|integer|min:0',
            'rating' => 'nullable|numeric|min:0|max:5',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $supplier = Supplier::create([
            'name' => $request->name,
            'status' => $request->status,
            'default_currency' => $request->default_currency,
            'payment_terms' => $request->payment_terms,
            'lead_time_days' => $request->lead_time_days,
            'rating' => $request->rating,
        ]);

        return response()->json(['message' => 'Supplier created successfully!', 'supplier' => $supplier]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $rules = [
            'status' => 'required|in:active,inactive,pending',
            'default_currency' => 'required|string|max:3',
            'payment_terms' => 'nullable|string|max:255',
            'lead_time_days' => 'nullable|integer|min:0',
            'rating' => 'nullable|numeric|min:0|max:5',
        ];
    
        // Only add unique validation if name has changed
        if ($request->name !== $supplier->name) {
            $rules['name'] = 'required|string|max:255|unique:suppliers,name';
        } else {
            $rules['name'] = 'required|string|max:255';
        }
    
        $validator = Validator::make($request->all(), $rules);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $supplier->update([
            'name' => $request->name,
            'status' => $request->status,
            'default_currency' => $request->default_currency,
            'payment_terms' => $request->payment_terms,
            'lead_time_days' => $request->lead_time_days,
            'rating' => $request->rating,
        ]);
    
        return response()->json(['message' => 'Supplier updated successfully!', 'supplier' => $supplier]);
    }
    


    /**
     * Delete a supplier.
     */
    public function destroy($id)
    {
        Supplier::destroy($id);
        return response()->json(['message' => 'Supplier deleted successfully!']);
    }

    /**
     * Bulk delete suppliers.
     */
    public function bulkDelete(Request $request)
    {
        Supplier::whereIn('id', $request->ids)->delete();
        return response()->json(['message' => 'Selected suppliers deleted successfully!']);
    }

    /**
     * Metrics for supplier count.
     */
    public function metrics()
    {
        $total = Supplier::count();
        return response()->json(['total' => $total]);
    }

    /**
     * List addresses for a specific supplier.
     */
    public function supplierAddressesIndex(Request $request, $supplier_id)
    {
        $addresses = SupplierAddress::where('supplier_id', $supplier_id);

        return DataTables::of($addresses)
            ->addColumn('action', function ($address) {
                return '<button class="btn btn-sm btn-info edit-address" data-id="' . $address->id . '"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger delete-address" data-id="' . $address->id . '"><i class="fas fa-trash"></i></button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Store or update supplier address.
     */
    public function storeSupplierAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'type' => 'required|in:billing,shipping,headquarters,other',
            'line1' => 'required|string|max:255',
            'line2' => 'nullable|string|max:255',
            'city_id' => 'required|string|max:255',
            'state_id' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
            'country_id' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $address = SupplierAddress::updateOrCreate(
            ['id' => $request->id],
            $request->only([
                'supplier_id', 'type', 'line1', 'line2', 'city', 'state_province', 'postal_code', 'country'
            ])
        );

        return response()->json(['message' => 'Address saved successfully!', 'address' => $address]);
    }

    /**
     * Delete a supplier address.
     */
    public function deleteSupplierAddress($id)
    {
        SupplierAddress::where('id', $id)->delete();
        return response()->json(['message' => 'Address deleted successfully!']);
    }

    /**
     * List addresses for a specific supplier.
     */
    public function suppliersAddressesIndex(Request $request)
    {
        $supplier_addresses_count = Supplier::count();
        $countries = Country::all();
        $suppliers = Supplier::all();
        return view('inventory.suppliers.addresses.index', compact('supplier_addresses_count', 'suppliers', 'countries'));
    }

    public function suppliersAddressesDatatable(Request $request)
    {
        if ($request->ajax()) {
            $addresses = SupplierAddress::with(['supplier', 'country', 'state', 'city'])->latest();

            return DataTables::of($addresses)
                ->addIndexColumn()
                ->addColumn('full_address', fn($row) => $row->line1 . ($row->line2 ? ', ' . $row->line2 : ''))
                ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
                ->addColumn('action', function ($row) {
                    return '
                        <button class="btn btn-sm btn-info edit-btn"
                            data-id="' . $row->id . '"
                            data-supplier_id="' . $row->supplier_id . '"
                            data-type="' . $row->type . '"
                            data-line1="' . e($row->line1) . '"
                            data-line2="' . e($row->line2) . '"
                            data-country_id="' . $row->country_id . '"
                            data-state_id="' . $row->state_id . '"
                            data-city_id="' . $row->city_id . '"
                            data-postal_code="' . e($row->postal_code) . '">
                            Edit</button>
                        <button class="btn btn-sm btn-danger delete-btn"
                            data-id="' . $row->id . '">Delete</button>';
                })
                ->rawColumns(['checkbox', 'action'])
                ->make(true);
        } 
        return 'OOPS! invalid request';
    }


    /**
     * Store or update supplier address.
     */
    public function storeSuppliersAddress(Request $request)
    {
        SupplierAddress::create($request->only([
            'supplier_id',
            'type',         // Add this if you're submitting it
            'line1',
            'line2',
            'country_id',
            'state_id',
            'city_id',
            'postal_code',
        ]));

        return response()->json(['success' => true]);
    }

    public function updateSuppliersAddress(Request $request, $id)
    {
        $address = SupplierAddress::findOrFail($id);

        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'type' => 'required',
            'line1' => 'required|string|max:255',
            'line2' => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'postal_code' => 'nullable|string|max:20',
        ]);

        $address->update($request->all());

        return response()->json(['success' => true]);
    }

    /**
     * Delete a supplier address.
     */
    public function deleteSuppliersAddress($id)
    {
        SupplierAddress::where('id', $id)->delete();
        return response()->json(['message' => 'Address deleted successfully!']);
    }

    public function bulkDeleteSuppliersAddresses(Request $request)
    {
        $ids = $request->input('ids', []);
        SupplierAddress::whereIn('id', $ids)->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Display the contacts page.
     */
    public function suppliersContactsIndex(Request $request)
    {
        $supplier_contacts_count = SupplierContact::count();
        $suppliers = Supplier::all();
        return view('inventory.suppliers.contacts.index', compact('supplier_contacts_count', 'suppliers'));
    }

    /**
     * Load contacts for DataTables.
     */
    public function suppliersContactsDatatable(Request $request)
    {
        if ($request->ajax()) {
            $contacts = SupplierContact::with('supplier')->latest();

            return DataTables::of($contacts)
                ->addIndexColumn()
                ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
                ->addColumn('action', function ($row) {
                    return '
                        <button class="btn btn-sm btn-info edit-btn"
                            data-id="' . $row->id . '"
                            data-supplier_id="' . $row->supplier_id . '"
                            data-name="' . e($row->name) . '"
                            data-role="' . e($row->role) . '"
                            data-email="' . e($row->email) . '"
                            data-phone="' . e($row->phone) . '"
                            data-notes="' . e($row->notes) . '">
                            Edit</button>
                        <button class="btn btn-sm btn-danger delete-btn"
                            data-id="' . $row->id . '">Delete</button>';
                })
                ->rawColumns(['checkbox', 'action'])
                ->make(true);
        }

        return 'OOPS! invalid request';
    }

    /**
     * Store a new supplier contact.
     */
    public function storeSuppliersContact(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'name' => 'required|string|max:255',
            'role' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
        ]);

        SupplierContact::create($request->only([
            'supplier_id', 'name', 'role', 'email', 'phone', 'notes'
        ]));

        return response()->json(['message' => 'Supplier contact created successfully.']);
    }

    /**
     * Update an existing contact.
     */
    public function updateSuppliersContact(Request $request, $id)
    {
        $contact = SupplierContact::findOrFail($id);

        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'name' => 'required|string|max:255',
            'role' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
        ]);

        $contact->update($request->only([
            'supplier_id', 'name', 'role', 'email', 'phone', 'notes'
        ]));

        return response()->json(['success' => true]);
    }

    /**
     * Delete a supplier contact.
     */
    public function deleteSuppliersContact($id)
    {
        SupplierContact::where('id', $id)->delete();
        return response()->json(['message' => 'Contact deleted successfully!']);
    }

    /**
     * Bulk delete contacts.
     */
    public function bulkDeleteSuppliersContacts(Request $request)
    {
        $ids = $request->input('ids', []);
        SupplierContact::whereIn('id', $ids)->delete();

        return response()->json(['success' => true]);
    }
}
