<?php

namespace Modules\Sales\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use Modules\Sales\Models\SalesPayment;
use Modules\Sales\Models\SalesPaymentAllocation;
use Modules\Sales\Models\SalesInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Modules\Finance\Services\Posting\SalesPaymentPostingService;

class SalesPaymentController extends Controller
{
    public function index()
    {
        return view('sales.payments.index');
    }

    public function datatable(Request $request)
    {
        $q = SalesPayment::query()
            ->with('customer')
            ->orderByDesc('id');

        if ($request->filled('status')) $q->where('status', $request->status);
        if ($request->filled('customer_id')) $q->where('customer_id', (int)$request->customer_id);
        if ($request->filled('payment_no')) $q->where('payment_no', 'like', '%'.$request->payment_no.'%');

        $rows = $q->paginate((int)($request->length ?? 10));

        $data = $rows->map(function ($p) {
            $allocated = (float) $p->allocations()->sum('amount_applied');
            $unallocated = (float) $p->amount_received - $allocated;

            return [
                'id'           => $p->id,
                'payment_no'   => $p->payment_no ?? ('PAY-'.$p->id),
                'customer'     => $p->customer?->name ?? ('Customer #'.$p->customer_id),
                'payment_date' => $p->payment_date?->format('d M Y') ?? '-',
                'amount'       => number_format((float)$p->amount_received, 2),
                'allocated'    => number_format($allocated, 2),
                'unallocated'  => number_format($unallocated, 2),
                'status'       => '<span class="badge badge-'.$p->status_badge.'">'.strtoupper($p->status).'</span>',
                'actions'      => view('sales.payments.partials.actions', ['payment'=>$p])->render(),
            ];
        })->values();

        return response()->json([
            'draw'            => (int)($request->draw ?? 1),
            'recordsTotal'    => $rows->total(),
            'recordsFiltered' => $rows->total(),
            'data'            => $data,
        ]);
    }

    public function create()
    {
        return view('sales.payments.form', [
            'payment' => new SalesPayment(),
            'mode' => 'create',
        ]);
    }
    
    public function show(SalesPayment $payment)
    {
        return view('sales.payments.show', [
            'payment' => $payment,
        ]);
    }
    

    public function edit(SalesPayment $payment)
    {
        $payment->load(['customer','allocations.invoice']);

        return view('sales.payments.form', [
            'payment' => $payment,
            'mode' => 'edit',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePayment($request);

        return DB::transaction(function () use ($data) {

            $payment = SalesPayment::create([
                'customer_id'     => $data['customer_id'],
                'payment_no'      => $data['payment_no'] ?? null,
                'payment_date'    => $data['payment_date'],
                'currency_code'   => $data['currency_code'] ?? 'NGN',
                'amount_received' => $data['amount_received'],
                'method'          => $data['method'] ?? null,
                'reference'       => $data['reference'] ?? null,
                'remarks'         => $data['remarks'] ?? null,
                'status'          => 'draft',
            ]);

            return response()->json([
                'message'  => 'Payment created.',
                'redirect' => route('admin.sales.payments.edit', $payment->id),
            ]);
        });
    }

    public function update(Request $request, SalesPayment $payment)
    {
        if ($payment->status !== 'draft') {
            return response()->json(['message'=>'Only draft payments can be edited.'], 422);
        }

        $data = $this->validatePayment($request);

        return DB::transaction(function () use ($data, $payment) {

            $payment->update([
                'customer_id'     => $data['customer_id'],
                'payment_no'      => $data['payment_no'] ?? $payment->payment_no,
                'payment_date'    => $data['payment_date'],
                'currency_code'   => $data['currency_code'] ?? $payment->currency_code,
                'amount_received' => $data['amount_received'],
                'method'          => $data['method'] ?? $payment->method,
                'reference'       => $data['reference'] ?? $payment->reference,
                'remarks'         => $data['remarks'] ?? $payment->remarks,
            ]);

            return response()->json(['message'=>'Payment updated.']);
        });
    }

    public function allocations(SalesPayment $payment)
    {
        $payment->load(['allocations.invoice']);
    
        $invoices = DB::table('v_sales_invoice_balances')
            ->where('customer_id', $payment->customer_id)
            ->orderByDesc('sales_invoice_id')
            ->get();
    
        $existing = $payment->allocations()
            ->get()
            ->keyBy('sales_invoice_id')
            ->map(fn($a) => (float)$a->amount_applied);
    
        $allocatedTotal = (float) $payment->allocations()->sum('amount_applied');
    
        return response()->json([
            'payment' => [
                'id' => $payment->id,
                'status' => $payment->status,
                'amount_received' => (float)$payment->amount_received,
                'allocated_total' => $allocatedTotal,
                'unallocated_total' => max(0, (float)$payment->amount_received - $allocatedTotal),
            ],
            'invoices' => $invoices,
            'existing' => $existing,
        ]);
    }

    public function saveAllocations(Request $request, SalesPayment $payment)
    {
        if ($payment->status !== 'draft') {
            return response()->json(['message' => 'Only draft payments can be allocated.'], 422);
        }
    
        $v = Validator::make($request->all(), [
            'allocations' => ['required', 'array'],
            'allocations.*.sales_invoice_id' => ['required', 'integer', 'exists:sales_invoices,id'],
            'allocations.*.amount_applied'   => ['required', 'numeric', 'min:0'],
        ])->validate();
    
        // Normalize input: group by invoice id (in case UI sends duplicates)
        $input = collect($v['allocations'])
            ->map(function ($a) {
                return [
                    'sales_invoice_id' => (int)$a['sales_invoice_id'],
                    'amount_applied'   => (float)$a['amount_applied'],
                ];
            })
            ->groupBy('sales_invoice_id')
            ->map(fn($rows) => (float) $rows->sum('amount_applied'))
            ->filter(fn($amt) => $amt > 0)
            ->toArray();
    
        // Load invoice balances for THIS customer only
        $balances = DB::table('v_sales_invoice_balances')
            ->where('customer_id', $payment->customer_id)
            ->whereIn('sales_invoice_id', array_keys($input))
            ->get()
            ->keyBy('sales_invoice_id');
    
        // Ensure every invoice belongs to this customer
        if (count($input) !== $balances->count()) {
            return response()->json([
                'message' => 'One or more invoices do not belong to this customer (or are not available).'
            ], 422);
        }
    
        // Cap each allocation by invoice balance_due and compute total
        $final = [];
        $sum = 0.0;
    
        foreach ($input as $invoiceId => $amt) {
            $bal = (float)($balances[$invoiceId]->balance_due ?? 0);
            $applied = max(0, min($amt, $bal)); // cap by balance_due
            if ($applied <= 0) continue;
    
            $final[] = [
                'sales_payment_id' => $payment->id,
                'sales_invoice_id' => (int)$invoiceId,
                'amount_applied'   => $applied,
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
    
            $sum += $applied;
        }
    
        if ($sum > (float)$payment->amount_received) {
            return response()->json([
                'message' => 'Allocated amount cannot exceed Amount Received.'
            ], 422);
        }
    
        return DB::transaction(function () use ($payment, $final) {
    
            // Replace allocations safely
            $payment->allocations()->delete();
    
            if (!empty($final)) {
                SalesPaymentAllocation::insert($final);
            }
    
            $allocated = (float) $payment->allocations()->sum('amount_applied');
            $unallocated = max(0, (float)$payment->amount_received - $allocated);
    
            return response()->json([
                'message' => 'Allocations saved.',
                'allocated_total' => $allocated,
                'unallocated_total' => $unallocated,
            ]);
        });
    }


    public function post(SalesPayment $payment)
    {
        if ($payment->status !== 'draft') {
            return response()->json(['message'=>'Only draft payments can be posted.'], 422);
        }

        $allocated = (float)$payment->allocations()->sum('amount_applied');
        if ($allocated <= 0) {
            return response()->json(['message'=>'Allocate at least one invoice before posting.'], 422);
        }
        if ($allocated > (float)$payment->amount_received) {
            return response()->json(['message'=>'Allocated total exceeds Amount Received.'], 422);
        }

        $payment->update([
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by' => auth()->id(),
        ]);
        
        $companyId = auth()->user()->company_id ?? 1;
        $jeId = SalesPaymentPostingService::post($companyId, $payment->id);

        return response()->json(['message'=>'Payment posted.']);
    }

    public function void(SalesPayment $payment)
    {
        if ($payment->status !== 'posted') {
            return response()->json(['message'=>'Only posted payments can be voided.'], 422);
        }

        $payment->update([
            'status' => 'void',
            'voided_at' => now(),
            'voided_by' => auth()->id(),
        ]);

        // Optional: remove allocations on void
        $payment->allocations()->delete();

        return response()->json(['message'=>'Payment voided.']);
    }

    public function destroy(SalesPayment $payment)
    {
        if ($payment->status !== 'draft') {
            return response()->json(['message'=>'Only draft payments can be deleted.'], 422);
        }

        $payment->allocations()->delete();
        $payment->delete();

        return response()->json(['message'=>'Deleted.']);
    }

    public function select2UnpaidInvoices(Request $request)
    {
        $q = trim((string)$request->q);
        $customerId = (int)($request->customer_id ?? 0);

        $rows = DB::table('v_sales_invoice_balances')
            ->when($customerId > 0, fn($x)=>$x->where('customer_id',$customerId))
            ->when($q !== '', function($x) use ($q){
                $x->where('invoice_no','like',"%{$q}%");
            })
            ->where('balance_due','>', 0)
            ->orderByDesc('sales_invoice_id')
            ->limit(20)
            ->get();

        $results = $rows->map(fn($r)=>[
            'id' => $r->sales_invoice_id,
            'text' => ($r->invoice_no ?? ('INV-'.$r->sales_invoice_id)).' | Bal: '.number_format((float)$r->balance_due,2),
            'balance_due' => (float)$r->balance_due,
        ]);

        return response()->json(['results'=>$results]);
    }

    private function validatePayment(Request $request): array
    {
        return Validator::make($request->all(), [
            'customer_id'     => ['required','integer','exists:customers,id'],
            'payment_no'      => ['nullable','string','max:40'],
            'payment_date'    => ['required','date'],
            'currency_code'   => ['nullable','string','max:10'],
            'amount_received' => ['required','numeric','min:0.01'],
            'method'          => ['nullable','string','max:40'],
            'reference'       => ['nullable','string','max:80'],
            'remarks'         => ['nullable','string'],
        ])->validate();
    }
    
    private function companyReceiptMeta(): array
    {
        // Ideally pull from settings table. For now: config/env placeholders.
        $name    = config('app.name', 'THEKAN-ERP');
        $address = config('app.company_address', 'Company Address Line 1, City, Country');
        $phone   = config('app.company_phone', '+234 000 000 0000');
        $email   = config('app.company_email', 'support@example.com');
        $logoRel = config('app.company_logo', 'assets/img/logo.png'); // put your logo here
    
        // DomPDF prefers absolute filesystem path for images
        $logoPath = public_path($logoRel);
        $logoDataUri = null;
    
        if (is_file($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $logoDataUri = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($logoPath));
        }
    
        return [
            'name' => $name,
            'address' => $address,
            'phone' => $phone,
            'email' => $email,
            'logo_data_uri' => $logoDataUri,
        ];
    }
    
    private function receiptQrDataUri(SalesPayment $payment): string
    {
        $verifyUrl = URL::signedRoute('admin.sales.payments.receipt.verify', $payment->id);
    
        $svg = QrCode::format('svg')
        ->size(120)
        ->generate($verifyUrl);

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
    
    public function print(SalesPayment $payment)
    {
        $payment->load(['customer', 'allocations.invoice']);
    
        return view('sales.payments.receipt', [
            'payment' => $payment,
            'mode'    => 'print',
            'company' => $this->companyReceiptMeta(),
            'qr'      => $this->receiptQrDataUri($payment),
            'verify_url' => URL::signedRoute('admin.sales.payments.receipt.verify', $payment->id),
        ]);
    }
    
    public function pdf(SalesPayment $payment)
    {
        $payment->load(['customer', 'allocations.invoice']);
    
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sales.payments.receipt', [
            'payment' => $payment,
            'mode'    => 'pdf',
            'company' => $this->companyReceiptMeta(),
            'qr'      => $this->receiptQrDataUri($payment),
            'verify_url' => URL::signedRoute('admin.sales.payments.receipt.verify', $payment->id),
        ])->setPaper('a4');
    
        $file = ($payment->payment_no ?? ('PAY-'.$payment->id)).'-receipt.pdf';
        return $pdf->download($file);
    }
    
    public function verifyReceipt(Request $request, SalesPayment $payment)
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid or expired receipt link.');
        }
    
        $payment->load(['customer','allocations.invoice']);
    
        return view('sales.payments.receipt_verify', [
            'payment' => $payment,
            'company' => $this->companyReceiptMeta(),
        ]);
    }


}
