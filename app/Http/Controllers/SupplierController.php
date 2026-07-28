<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierAddress;
use App\Models\SupplierContact;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class SupplierController extends Controller
{
    /* ===========================
     * Helpers (Filters + Audit)
     * =========================== */

    private function suppliersFilteredQuery(Request $request)
    {
        $q = Supplier::query();

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        if ($request->filled('currency')) {
            $q->where('default_currency', strtoupper(trim($request->currency)));
        }

        if ($request->filled('rating_min')) {
            $q->where('rating', '>=', (float) $request->rating_min);
        }
        if ($request->filled('rating_max')) {
            $q->where('rating', '<=', (float) $request->rating_max);
        }

        if ($request->filled('lead_min')) {
            $q->where('lead_time_days', '>=', (int) $request->lead_min);
        }
        if ($request->filled('lead_max')) {
            $q->where('lead_time_days', '<=', (int) $request->lead_max);
        }

        if ($request->filled('date_from')) {
            $q->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('quick_search')) {
            $term = trim($request->quick_search);
            $q->where(function ($qq) use ($term) {
                $qq->where('name', 'like', "%{$term}%")
                    ->orWhere('payment_terms', 'like', "%{$term}%")
                    ->orWhere('default_currency', 'like', "%{$term}%");
            });
        }

        return $q;
    }

    /**
     * AUDIT HELPER (same signature you requested)
     * audit(action, description, subject, meta)
     */
    private function audit(string $action, ?string $description = null, $subject = null, array $meta = []): void
    {
        // ✅ set to suppliers module (adjust if your taxonomy differs)
        $module = 'inventory.suppliers';

        auth()->user()?->audit(
            module: $module,
            action: $action,
            description: $description,
            subject: $subject,
            meta: $meta
        );
    }

    /* ===========================
     * Select2: Suppliers
     * =========================== */

    public function select2(Request $r)
    {
        $q = Supplier::query()
            ->when($r->filled('q'), fn ($qq) => $qq->where('name', 'like', "%{$r->q}%"))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name as text']);

        return response()->json($q);
    }

    /* ===========================
     * Select2: Countries / States / Cities (Dependent)
     * =========================== */

    public function countriesSelect2(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        $rows = Country::query()
            ->select('id', 'name')
            ->when($term, fn ($q) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'text' => $c->name])
            ->values();

        return response()->json($rows);
    }

    public function statesSelect2(Request $request)
    {
        $term = trim((string) $request->get('q', ''));
        $countryId = $request->get('country_id');

        $rows = State::query()
            ->select('id', 'name', 'country_id')
            ->when($countryId, fn ($q) => $q->where('country_id', $countryId))
            ->when($term, fn ($q) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn ($s) => ['id' => $s->id, 'text' => $s->name])
            ->values();

        return response()->json($rows);
    }

    public function citiesSelect2(Request $request)
    {
        $term = trim((string) $request->get('q', ''));
        $stateId = $request->get('state_id');

        $rows = City::query()
            ->select('id', 'name', 'state_id')
            ->when($stateId, fn ($q) => $q->where('state_id', $stateId))
            ->when($term, fn ($q) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'text' => $c->name])
            ->values();

        return response()->json($rows);
    }

    /* ===========================
     * Suppliers List Pages
     * =========================== */

    public function index()
    {
        $suppliers_count = Supplier::count();
        return view('suppliers.index', compact('suppliers_count'));
    }

    public function datatable(Request $request)
    {
        $suppliers = $this->suppliersFilteredQuery($request);

        return DataTables::of($suppliers)
            ->addColumn('checkbox', fn ($s) => '<input type="checkbox" class="supplier_checkbox" value="' . $s->id . '">')
            ->addColumn('created_at', fn ($s) => optional($s->created_at)->format('d-m-Y H:i'))
            ->addColumn('action', function ($s) {
                return '
                    <a class="btn btn-sm btn-secondary me-1" href="' . route('admin.suppliers.show', $s->id) . '">
                        <i class="fas fa-eye"></i>
                    </a>
                    <button class="btn btn-sm btn-info edit"
                        data-id="' . $s->id . '"
                        data-name="' . e($s->name) . '"
                        data-status="' . e($s->status) . '"
                        data-currency="' . e($s->default_currency) . '"
                        data-payment_terms="' . e($s->payment_terms) . '"
                        data-lead_time_days="' . e($s->lead_time_days) . '"
                        data-rating="' . e($s->rating) . '">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete" data-id="' . $s->id . '">
                        <i class="fas fa-trash"></i>
                    </button>
                ';
            })
            ->rawColumns(['checkbox', 'action'])
            ->make(true);
    }

    public function metrics(Request $request)
    {
        $total = $this->suppliersFilteredQuery($request)->count();
        return response()->json(['total' => number_format($total)]);
    }

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
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $supplier = Supplier::create([
            'name' => $request->name,
            'status' => $request->status,
            'default_currency' => strtoupper($request->default_currency),
            'payment_terms' => $request->payment_terms,
            'lead_time_days' => $request->lead_time_days ?? 0,
            'rating' => $request->rating,
        ]);

        $this->audit(
            'supplier.created',
            'Created supplier: ' . $supplier->name,
            $supplier,
            ['input' => $request->only(array_keys($rules))]
        );

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

        $rules['name'] = ($request->name !== $supplier->name)
            ? 'required|string|max:255|unique:suppliers,name'
            : 'required|string|max:255';

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $before = $supplier->only(['name', 'status', 'default_currency', 'payment_terms', 'lead_time_days', 'rating']);

        $supplier->update([
            'name' => $request->name,
            'status' => $request->status,
            'default_currency' => strtoupper($request->default_currency),
            'payment_terms' => $request->payment_terms,
            'lead_time_days' => $request->lead_time_days ?? 0,
            'rating' => $request->rating,
        ]);

        $after = $supplier->only(['name', 'status', 'default_currency', 'payment_terms', 'lead_time_days', 'rating']);

        $this->audit(
            'supplier.updated',
            'Updated supplier: ' . $supplier->name,
            $supplier,
            ['before' => $before, 'after' => $after]
        );

        return response()->json(['message' => 'Supplier updated successfully!', 'supplier' => $supplier]);
    }

    public function destroy($id)
    {
        $supplier = Supplier::find($id);
        Supplier::destroy($id);

        $this->audit(
            'supplier.deleted',
            $supplier ? 'Deleted supplier: ' . $supplier->name : 'Deleted supplier ID: ' . $id,
            $supplier,
            ['supplier_id' => $id]
        );

        return response()->json(['message' => 'Supplier deleted successfully!']);
    }

    public function bulkDelete(Request $request)
    {
        $ids = (array) $request->ids;
        Supplier::whereIn('id', $ids)->delete();

        $this->audit(
            'supplier.bulk_deleted',
            'Bulk deleted suppliers',
            null,
            ['ids' => $ids]
        );

        return response()->json(['message' => 'Selected suppliers deleted successfully!']);
    }

    /* ===========================
     * Supplier SHOW (Contacts + Addresses in 1 page)
     * =========================== */

    public function show($id)
    {
        $supplier = Supplier::findOrFail($id);
        return view('suppliers.show', compact('supplier'));
    }

    /* ===========================
     * Per-supplier Contacts: datatable + CRUD
     * =========================== */

    public function showContactsDatatable(Request $request, $supplierId)
    {
        $q = SupplierContact::query()
            ->where('supplier_id', $supplierId)
            ->latest();

        return DataTables::of($q)
            ->addColumn('action', function ($row) {
                return '
                    <button type="button" class="btn btn-sm btn-info edit-contact"
                        data-id="' . $row->id . '"
                        data-name="' . e($row->name) . '"
                        data-role="' . e($row->role) . '"
                        data-email="' . e($row->email) . '"
                        data-phone="' . e($row->phone) . '"
                        data-notes="' . e($row->notes) . '">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger delete-contact" data-id="' . $row->id . '">
                        <i class="fas fa-trash"></i>
                    </button>
                ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function showContactsStore(Request $request, $supplierId)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $contact = SupplierContact::create(array_merge($data, ['supplier_id' => $supplierId]));

        $this->audit(
            'supplier.contact.created',
            'Created supplier contact: ' . $contact->name,
            $contact,
            ['supplier_id' => $supplierId, 'id' => $contact->id]
        );

        return response()->json(['message' => 'Contact saved successfully!']);
    }

    public function updateSupplierContactForSupplier(Request $request, $supplierId, $id)
    {
        $contact = SupplierContact::where('supplier_id', $supplierId)->findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $before = $contact->toArray();
        $contact->update($data);

        $this->audit(
            'supplier.contact.updated',
            'Updated supplier contact: ' . $contact->name,
            $contact,
            ['before' => $before, 'after' => $contact->fresh()->toArray()]
        );

        return response()->json(['message' => 'Contact updated successfully!']);
    }

    public function deleteSupplierContactForSupplier($supplierId, $id)
    {
        $contact = SupplierContact::where('supplier_id', $supplierId)->findOrFail($id);
        $contact->delete();

        $this->audit(
            'supplier.contact.deleted',
            'Deleted supplier contact ID: ' . $id,
            $contact,
            ['supplier_id' => $supplierId, 'id' => $id]
        );

        return response()->json(['message' => 'Contact deleted successfully!']);
    }

    /* ===========================
     * Per-supplier Addresses: datatable + CRUD (with country/state/city text)
     * =========================== */

    public function showAddressesDatatable(Request $request, $supplierId)
    {
        $q = SupplierAddress::query()
            ->where('supplier_id', $supplierId)
            ->with(['country:id,name', 'state:id,name', 'city:id,name'])
            ->latest();

        return DataTables::of($q)
            ->addColumn('country_text', fn ($row) => $row->country?->name ?? '—')
            ->addColumn('state_text', fn ($row) => $row->state?->name ?? '—')
            ->addColumn('city_text', fn ($row) => $row->city?->name ?? '—')
            ->addColumn('action', function ($row) {
                return '
                    <button type="button" class="btn btn-sm btn-info edit-address"
                        data-id="' . $row->id . '"
                        data-type="' . e($row->type) . '"
                        data-line1="' . e($row->line1) . '"
                        data-line2="' . e($row->line2) . '"
                        data-postal_code="' . e($row->postal_code) . '"
                        data-country_id="' . e($row->country_id) . '"
                        data-state_id="' . e($row->state_id) . '"
                        data-city_id="' . e($row->city_id) . '"
                        data-country_text="' . e($row->country?->name ?? '') . '"
                        data-state_text="' . e($row->state?->name ?? '') . '"
                        data-city_text="' . e($row->city?->name ?? '') . '">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger delete-address" data-id="' . $row->id . '">
                        <i class="fas fa-trash"></i>
                    </button>
                ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function storeSupplierAddressForSupplier(Request $request, $supplierId)
    {
        $data = $request->validate([
            'type' => 'required|in:billing,shipping,headquarters,other',
            'line1' => 'required|string|max:255',
            'line2' => 'nullable|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'postal_code' => 'nullable|string|max:255',
        ]);

        $address = SupplierAddress::create(array_merge($data, ['supplier_id' => $supplierId]));

        $this->audit(
            'supplier.address.created',
            'Created supplier address ID: ' . $address->id,
            $address,
            ['supplier_id' => $supplierId, 'id' => $address->id]
        );

        return response()->json(['message' => 'Address saved successfully!']);
    }

    public function updateSupplierAddressForSupplier(Request $request, $supplierId, $id)
    {
        $address = SupplierAddress::where('supplier_id', $supplierId)->findOrFail($id);

        $data = $request->validate([
            'type' => 'required|in:billing,shipping,headquarters,other',
            'line1' => 'required|string|max:255',
            'line2' => 'nullable|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'postal_code' => 'nullable|string|max:255',
        ]);

        $before = $address->toArray();
        $address->update($data);

        $this->audit(
            'supplier.address.updated',
            'Updated supplier address ID: ' . $address->id,
            $address,
            ['before' => $before, 'after' => $address->fresh()->toArray()]
        );

        return response()->json(['message' => 'Address updated successfully!']);
    }

    public function deleteSupplierAddressForSupplier($supplierId, $id)
    {
        $address = SupplierAddress::where('supplier_id', $supplierId)->findOrFail($id);
        $address->delete();

        $this->audit(
            'supplier.address.deleted',
            'Deleted supplier address ID: ' . $id,
            $address,
            ['supplier_id' => $supplierId, 'id' => $id]
        );

        return response()->json(['message' => 'Address deleted successfully!']);
    }

    /* ===========================
     * Global (Wholistic) Addresses Page + Datatable
     * =========================== */

    public function suppliersAddressesIndex()
    {
        // if you also need suppliers list here, you can add it
        return view('suppliers.addresses.index', [
            'countries' => Country::all(),
        ]);
    }

    public function suppliersAddressesDatatable(Request $request)
    {
        if (!$request->ajax()) {
            return response('OOPS! invalid request', 400);
        }

        $addresses = SupplierAddress::query()
            ->select('supplier_addresses.*')
            ->with([
                'supplier:id,name',
                'country:id,name',
                'state:id,name',
                'city:id,name',
            ])
            ->orderBy('supplier_addresses.created_at', 'desc');

        return DataTables::of($addresses)
            ->addIndexColumn()
            ->addColumn('full_address', fn ($row) => $row->line1 . ($row->line2 ? ', ' . $row->line2 : ''))
            ->addColumn('created_at', fn ($row) => optional($row->created_at)->format('d-m-Y H:i'))
            ->addColumn('country_text', fn ($row) => $row->country?->name ?? '—')
            ->addColumn('state_text', fn ($row) => $row->state?->name ?? '—')
            ->addColumn('city_text', fn ($row) => $row->city?->name ?? '—')
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
            ->addColumn('action', function ($row) {
                return '
                    <button class="btn btn-sm btn-info edit-btn"
                        data-id="' . $row->id . '"
                        data-supplier_id="' . $row->supplier_id . '"
                        data-supplier_name="' . e($row->supplier?->name) . '"
                        data-type="' . e($row->type) . '"
                        data-line1="' . e($row->line1) . '"
                        data-line2="' . e($row->line2) . '"
                        data-country_id="' . e($row->country_id) . '"
                        data-state_id="' . e($row->state_id) . '"
                        data-city_id="' . e($row->city_id) . '"
                        data-country_text="' . e($row->country?->name ?? '') . '"
                        data-state_text="' . e($row->state?->name ?? '') . '"
                        data-city_text="' . e($row->city?->name ?? '') . '"
                        data-postal_code="' . e($row->postal_code) . '">
                        Edit</button>
                    <button class="btn btn-sm btn-danger delete-btn" data-id="' . $row->id . '">
                        Delete</button>
                ';
            })
            ->rawColumns(['checkbox', 'action'])
            ->make(true);
    }

    public function storeSuppliersAddress(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'type' => 'required|in:billing,shipping,headquarters,other',
            'line1' => 'required|string|max:255',
            'line2' => 'nullable|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'postal_code' => 'nullable|string|max:255',
        ]);

        $address = SupplierAddress::create($data);

        $this->audit(
            'supplier.address.created',
            'Created supplier address ID: ' . $address->id,
            $address,
            ['supplier_id' => $address->supplier_id, 'id' => $address->id]
        );

        return response()->json(['message' => 'Address created successfully!']);
    }

    public function updateSuppliersAddress(Request $request, $id)
    {
        $address = SupplierAddress::findOrFail($id);

        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'type' => 'required|in:billing,shipping,headquarters,other',
            'line1' => 'required|string|max:255',
            'line2' => 'nullable|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'postal_code' => 'nullable|string|max:255',
        ]);

        $before = $address->toArray();
        $address->update($data);

        $this->audit(
            'supplier.address.updated',
            'Updated supplier address ID: ' . $address->id,
            $address,
            ['before' => $before, 'after' => $address->fresh()->toArray()]
        );

        return response()->json(['message' => 'Address updated successfully!']);
    }

    public function deleteSuppliersAddress($id)
    {
        $address = SupplierAddress::findOrFail($id);
        $address->delete();

        $this->audit(
            'supplier.address.deleted',
            'Deleted supplier address ID: ' . $id,
            $address,
            ['supplier_id' => $address->supplier_id, 'id' => $id]
        );

        return response()->json(['message' => 'Address deleted successfully!']);
    }

    public function bulkDeleteSuppliersAddresses(Request $request)
    {
        $ids = (array) $request->input('ids', []);
        SupplierAddress::whereIn('id', $ids)->delete();

        $this->audit(
            'supplier.address.bulk_deleted',
            'Bulk deleted supplier addresses',
            null,
            ['ids' => $ids]
        );

        return response()->json(['message' => 'Selected addresses deleted successfully!']);
    }

    /* ===========================
     * Global (Wholistic) Contacts Page + Datatable
     * =========================== */

    public function suppliersContactsIndex()
    {
        return view('suppliers.contacts.index', [
            'suppliers' => Supplier::all(),
            'countries' => Country::all(),
        ]);
    }

    public function suppliersContactsDatatable(Request $request)
    {
        if (!$request->ajax()) {
            return response('OOPS! invalid request', 400);
        }

        $q = SupplierContact::query()
            ->select('supplier_contacts.*')
            ->with(['supplier:id,name'])
            ->orderBy('supplier_contacts.created_at', 'desc');

        return DataTables::of($q)
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
            ->addIndexColumn()
            ->addColumn('created_at', fn ($row) => optional($row->created_at)->format('d-m-Y H:i'))
            ->addColumn('action', function ($row) {
                return '
                    <button type="button" class="btn btn-sm btn-info edit-btn"
                        data-id="' . $row->id . '"
                        data-supplier_id="' . $row->supplier_id . '"
                        data-supplier_name="' . e($row->supplier?->name) . '"
                        data-name="' . e($row->name) . '"
                        data-role="' . e($row->role) . '"
                        data-email="' . e($row->email) . '"
                        data-phone="' . e($row->phone) . '"
                        data-notes="' . e($row->notes) . '">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $row->id . '">
                        <i class="fas fa-trash"></i>
                    </button>
                ';
            })
            ->rawColumns(['checkbox', 'action'])
            ->make(true);
    }

    public function storeSuppliersContact(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'name' => 'required|string|max:255',
            'role' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $contact = SupplierContact::create($data);

        $this->audit(
            'supplier.contact.created',
            'Created supplier contact: ' . $contact->name,
            $contact,
            ['supplier_id' => $contact->supplier_id, 'id' => $contact->id]
        );

        return response()->json(['message' => 'Contact created successfully!']);
    }

    public function updateSuppliersContact(Request $request, $id)
    {
        $contact = SupplierContact::findOrFail($id);

        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'name' => 'required|string|max:255',
            'role' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $before = $contact->toArray();
        $contact->update($data);

        $this->audit(
            'supplier.contact.updated',
            'Updated supplier contact: ' . $contact->name,
            $contact,
            ['before' => $before, 'after' => $contact->fresh()->toArray()]
        );

        return response()->json(['message' => 'Contact updated successfully!']);
    }

    public function deleteSuppliersContact($id)
    {
        $contact = SupplierContact::findOrFail($id);
        $contact->delete();

        $this->audit(
            'supplier.contact.deleted',
            'Deleted supplier contact ID: ' . $id,
            $contact,
            ['supplier_id' => $contact->supplier_id, 'id' => $id]
        );

        return response()->json(['message' => 'Contact deleted successfully!']);
    }

    public function bulkDeleteSuppliersContacts(Request $request)
    {
        $ids = (array) $request->input('ids', []);
        SupplierContact::whereIn('id', $ids)->delete();

        $this->audit(
            'supplier.contact.bulk_deleted',
            'Bulk deleted supplier contacts',
            null,
            ['ids' => $ids]
        );

        return response()->json(['message' => 'Selected contacts deleted successfully!']);
    }
}
