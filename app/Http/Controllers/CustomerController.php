<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Modules\CRM\Models\Customer;
use Modules\CRM\Models\CustomerContact;
use Modules\CRM\Models\CustomerAddress;
use Modules\CRM\Models\Interaction;
use Modules\CRM\Models\Opportunity;
use Modules\CRM\Models\SupportTicket;
use App\Models\Company;
use App\Models\Country;
use App\Models\State;
use Yajra\DataTables\DataTables;

class CustomerController extends Controller
{
    
    public function __construct()
    {
        $this->middleware('auth');
 
        // ── Core: Customers ────────────────────────────────────────────────
        $this->middleware('permission:core.master_data.customers.view',
            ['only' => ['index', 'datatable', 'select2', 'show']]);
 
        $this->middleware('permission:core.master_data.customers.create',
            ['only' => ['store']]);
 
        $this->middleware('permission:core.master_data.customers.edit',
            ['only' => ['edit', 'update']]);
 
        $this->middleware('permission:core.master_data.customers.delete',
            ['only' => ['destroy', 'bulkDelete']]);
 
        // ── CRM: Contacts ──────────────────────────────────────────────────
        $this->middleware('permission:crm.customers.contacts.view',
            ['only' => ['contactsDatatable']]);
 
        $this->middleware('permission:crm.customers.contacts.create',
            ['only' => ['storeContact']]);
 
        $this->middleware('permission:crm.customers.contacts.edit',
            ['only' => ['updateContact']]);
 
        $this->middleware('permission:crm.customers.contacts.delete',
            ['only' => ['destroyContact']]);
 
        // ── CRM: Addresses ─────────────────────────────────────────────────
        $this->middleware('permission:crm.customers.addresses.view',
            ['only' => ['addressesDatatable']]);
 
        $this->middleware('permission:crm.customers.addresses.create',
            ['only' => ['storeAddress']]);
 
        $this->middleware('permission:crm.customers.addresses.edit',
            ['only' => ['updateAddress']]);
 
        $this->middleware('permission:crm.customers.addresses.delete',
            ['only' => ['destroyAddress']]);
    }
    
    // ─────────────────────────────────────────────────────────────────────────
    // AUDIT HELPER
    // ─────────────────────────────────────────────────────────────────────────

    private function audit(string $action, ?string $description = null, $subject = null, array $meta = []): void
    {
        auth()->user()?->audit(
            module: 'crm.customers',
            action: $action,
            description: $description,
            subject: $subject,
            meta: $meta
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SELECT2
    // ─────────────────────────────────────────────────────────────────────────

    public function select2(Request $r)
    {
        $term = $r->q;

        $q = Customer::query()
            ->when($r->filled('q'), fn($qq) => $qq->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name as text']);

        return response()->json($q);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // INDEX
    // ─────────────────────────────────────────────────────────────────────────

    public function index()
    {
        $companies = Company::orderBy('name')->get(['id', 'name']);

        $this->audit('view', 'Viewed customers index');

        return view('customers.index', compact('companies'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SHOW
    // BUG FIX: 'edit' route was referenced in show.blade but didn't exist —
    // show now passes everything needed and edit() is added below.
    // ─────────────────────────────────────────────────────────────────────────

    public function show($id)
    {
        $customer = Customer::with([
            'company:id,name',
            'contacts',
            'addresses.country',
            'addresses.state',
        ])->findOrFail($id);

        $countries = Country::orderBy('name')->get(['id', 'name']);
        $states    = State::where('country_id', optional($customer->addresses->first())->country_id ?? 0)
                          ->orderBy('name')->get(['id', 'name']);

        $this->audit(
            action: 'view.show',
            description: 'Viewed customer: ' . $customer->name,
            subject: $customer,
            meta: ['customer_id' => $customer->id]
        );

        return view('customers.show', compact('customer', 'countries', 'states'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EDIT (BUG FIX: was referenced but missing)
    // ─────────────────────────────────────────────────────────────────────────

    public function edit($id)
    {
        $customer  = Customer::with('company:id,name')->findOrFail($id);
        $companies = Company::orderBy('name')->get(['id', 'name']);

        return view('customers.edit', compact('customer', 'companies'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STORE
    // ─────────────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id'        => ['required', 'exists:companies,id'],
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['nullable', 'email', 'max:255'],
            'phone'             => ['nullable', 'string', 'max:20'],
            'position'          => ['nullable', 'string', 'max:150'],
            'address'           => ['nullable', 'string', 'max:500'],
            'tax_id'            => ['nullable', 'string', 'max:100'],
            'credit_limit'      => ['nullable', 'numeric', 'min:0'],
            'credit_terms_days' => ['nullable', 'integer', 'min:0'],
            'currency_code'     => ['nullable', 'string', 'size:3'],
            'website'           => ['nullable', 'url', 'max:255'],
            'notes'             => ['nullable', 'string'],
            'status'            => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $customer = Customer::create($validated);

        $this->audit('created', "Created customer: {$customer->name}", $customer, [
            'customer_id' => $customer->id,
        ]);

        return response()->json(['message' => 'Customer saved successfully.', 'id' => $customer->id]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // UPDATE
    // ─────────────────────────────────────────────────────────────────────────

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'company_id'        => ['required', 'exists:companies,id'],
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['nullable', 'email', 'max:255'],
            'phone'             => ['nullable', 'string', 'max:20'],
            'position'          => ['nullable', 'string', 'max:150'],
            'address'           => ['nullable', 'string', 'max:500'],
            'tax_id'            => ['nullable', 'string', 'max:100'],
            'credit_limit'      => ['nullable', 'numeric', 'min:0'],
            'credit_terms_days' => ['nullable', 'integer', 'min:0'],
            'currency_code'     => ['nullable', 'string', 'size:3'],
            'website'           => ['nullable', 'url', 'max:255'],
            'notes'             => ['nullable', 'string'],
            'status'            => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $before = $customer->only(array_keys($validated));
        $customer->update($validated);
        $after  = $customer->fresh()->only(array_keys($validated));

        $this->audit('updated', "Updated customer: {$customer->name}", $customer, [
            'before' => $before,
            'after'  => $after,
        ]);

        return response()->json(['message' => 'Customer updated successfully.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DESTROY
    // ─────────────────────────────────────────────────────────────────────────

    public function destroy($id)
    {
        $customer = Customer::with('company:id,name')->findOrFail($id);
        $meta     = ['customer_id' => $customer->id, 'name' => $customer->name];
        $customer->delete();

        $this->audit('deleted', "Deleted customer: {$meta['name']}", null, $meta);

        return response()->json(['message' => 'Customer deleted.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BULK DELETE
    // ─────────────────────────────────────────────────────────────────────────

    public function bulkDelete(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:customers,id'],
        ]);

        $count = Customer::whereIn('id', $data['ids'])->delete();

        $this->audit('bulk.deleted', "Bulk deleted {$count} customer(s)", null, ['ids' => $data['ids']]);

        return response()->json(['message' => "{$count} customer(s) deleted."]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DATATABLE
    // ─────────────────────────────────────────────────────────────────────────

    public function datatable()
    {
        $query = Customer::with('company:id,name')->select('customers.*');

        return DataTables::of($query)
            ->addColumn('checkbox', fn($r) =>
                '<input type="checkbox" class="row-checkbox" value="'.$r->id.'">')
            ->addColumn('company', fn($r) => $r->company?->name ?? '-')
            ->addColumn('credit_terms', fn($r) => $r->credit_terms_label)
            ->addColumn('status_badge', fn($r) => $r->status_badge)
            ->addColumn('actions', function ($r) {
                return '
                    <a href="'.route('admin.customers.show', $r->id).'"
                       class="btn btn-sm btn-outline-primary" title="View">
                       <i class="fas fa-eye"></i></a>
                    <button class="btn btn-sm btn-info btn-edit-customer"
                        data-id="'.$r->id.'"
                        data-record="'.e(json_encode($r->only([
                            'id','company_id','name','email','phone','position',
                            'address','tax_id','credit_limit','credit_terms_days',
                            'currency_code','website','notes','status',
                        ]))).'"
                        title="Edit"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-danger btn-delete-customer"
                        data-id="'.$r->id.'" title="Delete">
                        <i class="fas fa-trash-alt"></i></button>';
            })
            ->rawColumns(['checkbox', 'status_badge', 'actions'])
            ->make(true);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CONTACTS (nested under customer)
    // ─────────────────────────────────────────────────────────────────────────

    public function contactsDatatable(Customer $customer)
    {
        $query = CustomerContact::where('customer_id', $customer->id);

        return DataTables::of($query)
            ->addColumn('full_name', fn($r) => $r->full_name)
            ->addColumn('primary_badge', fn($r) => $r->is_primary
                ? '<span class="badge bg-primary">Primary</span>'
                : '')
            ->addColumn('status_badge', fn($r) => $r->is_active
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-secondary">Inactive</span>')
            ->addColumn('actions', fn($r) =>
                '<button class="btn btn-xs btn-warning btn-edit-contact"
                    data-id="'.$r->id.'"
                    data-record="'.e(json_encode($r->toArray())).'">
                    <i class="fas fa-pencil-alt"></i></button>
                 <button class="btn btn-xs btn-danger btn-delete-contact" data-id="'.$r->id.'">
                    <i class="fas fa-trash"></i></button>')
            ->rawColumns(['primary_badge', 'status_badge', 'actions'])
            ->make(true);
    }

    public function storeContact(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:150'],
            'last_name'  => ['nullable', 'string', 'max:150'],
            'position'   => ['nullable', 'string', 'max:150'],
            'email'      => ['nullable', 'email', 'max:255'],
            'phone'      => ['nullable', 'string', 'max:50'],
            'is_primary' => ['boolean'],
            'is_active'  => ['boolean'],
        ]);

        // Only one primary contact per customer
        if (! empty($validated['is_primary'])) {
            CustomerContact::where('customer_id', $customer->id)
                ->update(['is_primary' => false]);
        }

        $contact = $customer->contacts()->create([
            ...$validated,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return response()->json(['message' => 'Contact saved.', 'contact' => $contact]);
    }

    public function updateContact(Request $request, Customer $customer, CustomerContact $contact)
    {
        abort_unless($contact->customer_id == $customer->id, 403);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:150'],
            'last_name'  => ['nullable', 'string', 'max:150'],
            'position'   => ['nullable', 'string', 'max:150'],
            'email'      => ['nullable', 'email', 'max:255'],
            'phone'      => ['nullable', 'string', 'max:50'],
            'is_primary' => ['boolean'],
            'is_active'  => ['boolean'],
        ]);

        if (! empty($validated['is_primary'])) {
            CustomerContact::where('customer_id', $customer->id)
                ->where('id', '!=', $contact->id)
                ->update(['is_primary' => false]);
        }

        $contact->update([...$validated, 'updated_by' => auth()->id()]);

        return response()->json(['message' => 'Contact updated.']);
    }

    public function destroyContact(Customer $customer, CustomerContact $contact)
    {
        abort_unless($contact->customer_id === $customer->id, 403);
        $contact->delete();

        return response()->json(['message' => 'Contact deleted.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ADDRESSES (nested under customer)
    // ─────────────────────────────────────────────────────────────────────────

    public function addressesDatatable(Customer $customer)
    {
        $query = CustomerAddress::with(['country', 'state'])
            ->where('customer_id', $customer->id);

        return DataTables::of($query)
            ->addColumn('type_badge', fn($r) =>
                '<span class="badge bg-info text-dark">'.ucfirst($r->type).'</span>')
            ->addColumn('full_address', fn($r) => $r->full_address)
            ->addColumn('default_badge', fn($r) => $r->is_default
                ? '<span class="badge bg-primary">Default</span>'
                : '')
            ->addColumn('actions', fn($r) =>
                '<button class="btn btn-xs btn-warning btn-edit-address"
                    data-id="'.$r->id.'"
                    data-record="'.e(json_encode($r->toArray())).'">
                    <i class="fas fa-pencil-alt"></i></button>
                 <button class="btn btn-xs btn-danger btn-delete-address" data-id="'.$r->id.'">
                    <i class="fas fa-trash"></i></button>')
            ->rawColumns(['type_badge', 'default_badge', 'actions'])
            ->make(true);
    }

    public function storeAddress(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'type'        => ['required', Rule::in(['billing','shipping','headquarters','other'])],
            'line1'       => ['required', 'string', 'max:255'],
            'line2'       => ['nullable', 'string', 'max:255'],
            'country_id'  => ['nullable', 'integer'],
            'state_id'    => ['nullable', 'integer'],
            'city_id'     => ['nullable', 'integer'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'is_default'  => ['boolean'],
        ]);

        if (! empty($validated['is_default'])) {
            CustomerAddress::where('customer_id', $customer->id)
                ->where('type', $validated['type'])
                ->update(['is_default' => false]);
        }

        $address = $customer->addresses()->create([
            ...$validated,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return response()->json(['message' => 'Address saved.', 'address' => $address]);
    }

    public function updateAddress(Request $request, Customer $customer, CustomerAddress $address)
    {
        abort_unless($address->customer_id == $customer->id, 403);

        $validated = $request->validate([
            'type'        => ['required', Rule::in(['billing','shipping','headquarters','other'])],
            'line1'       => ['required', 'string', 'max:255'],
            'line2'       => ['nullable', 'string', 'max:255'],
            'country_id'  => ['nullable', 'integer'],
            'state_id'    => ['nullable', 'integer'],
            'city_id'     => ['nullable', 'integer'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'is_default'  => ['boolean'],
        ]);

        if (! empty($validated['is_default'])) {
            CustomerAddress::where('customer_id', $customer->id)
                ->where('type', $validated['type'])
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        $address->update([...$validated, 'updated_by' => auth()->id()]);

        return response()->json(['message' => 'Address updated.']);
    }

    public function destroyAddress(Customer $customer, CustomerAddress $address)
    {
        abort_unless($address->customer_id === $customer->id, 403);
        $address->delete();

        return response()->json(['message' => 'Address deleted.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EXISTING DATATABLES (unchanged, kept for compatibility)
    // ─────────────────────────────────────────────────────────────────────────

    public function opportunitiesDatatable($customerId)
    {
        $query = Opportunity::query()
            ->where('customer_id', $customerId)
            ->select(['id','title','value','stage','probability','close_date','owner_id','notes']);

        return DataTables::of($query)
            ->addColumn('owner', fn($r) =>
                optional(\App\Models\User::find($r->owner_id))->name ?? '-')
            ->addColumn('action', fn($r) =>
                '<a href="'.url('admin/opportunities/'.$r->id).'"
                    class="btn btn-sm btn-outline-primary">View</a>')
            ->editColumn('value', fn($r) => number_format((float) $r->value, 2))
            ->editColumn('probability', fn($r) => is_null($r->probability) ? '-' : $r->probability.'%')
            ->editColumn('close_date', fn($r) => $r->close_date ?: '-')
            ->rawColumns(['action'])
            ->make(true);
    }

    public function invoicesDatatable($customerId)
    {
        $query = DB::table('invoices')
            ->where('customer_id', $customerId)
            ->select(['id','invoice_number','total_amount','payment_status','due_date','created_at']);

        return DataTables::of($query)
            ->addColumn('action', fn($r) =>
                '<a href="'.url('admin/invoices/'.$r->id).'"
                    class="btn btn-sm btn-outline-primary">View</a>')
            ->editColumn('total_amount', fn($r) => number_format((float) $r->total_amount, 2))
            ->editColumn('created_at', fn($r) => $r->created_at
                ? \Carbon\Carbon::parse($r->created_at)->format('Y-m-d H:i') : '-')
            ->rawColumns(['action'])
            ->make(true);
    }

    public function interactionsDatatable($customerId)
    {
        $query = DB::table('interactions')
            ->where('interactable_type', Customer::class)
            ->where('interactable_id', $customerId)
            ->select(['id','subject','details','interaction_type','outcome',
                      'interaction_date','employee_id','created_by']);

        return DataTables::of($query)
            ->addColumn('employee', fn($r) =>
                optional(\App\Models\User::find($r->employee_id))->name ?? '-')
            ->addColumn('action', fn($r) => '
                <button class="btn btn-sm btn-info edit-interaction"
                    data-id="'.$r->id.'"
                    data-subject="'.e($r->subject).'"
                    data-interaction_type="'.e($r->interaction_type).'">
                    <i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-danger delete-interaction" data-id="'.$r->id.'">
                    <i class="fas fa-trash-alt"></i></button>')
            ->editColumn('interaction_date', fn($r) => $r->interaction_date ?: '-')
            ->rawColumns(['action'])
            ->make(true);
    }

    public function supportTicketsDatatable($customerId)
    {
        $query = SupportTicket::query()
            ->where('customer_id', $customerId)
            ->select(['id','ticket_no','subject','priority','category','channel','status','assigned_to','created_at']);

        return DataTables::of($query)
            ->addColumn('assigned_to', fn($r) =>
                optional(\App\Models\User::find($r->assigned_to))->name ?? '-')
            ->addColumn('action', fn($r) =>
                '<a href="'.url('admin/crm/support-tickets/'.$r->id).'"
                    class="btn btn-sm btn-outline-primary">View</a>')
            ->editColumn('created_at', fn($r) => optional($r->created_at)->format('Y-m-d H:i') ?: '-')
            ->rawColumns(['action'])
            ->make(true);
    }
}