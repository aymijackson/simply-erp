<?php

namespace Modules\Sales\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use Modules\Sales\Models\SalesCreditNote;
use Modules\Sales\Models\SalesCreditNoteLine;
use Modules\Sales\Models\SalesInvoice;

class SalesCreditNoteController extends Controller
{
    public function index()
    {
        return view('sales.credit_notes.index');
    }

    public function datatable(Request $request)
    {
        $q = SalesCreditNote::query()
            ->with(['customer','invoice'])
            ->orderByDesc('id');

        if ($request->filled('status')) $q->where('status', $request->status);
        if ($request->filled('customer_id')) $q->where('customer_id', (int)$request->customer_id);
        if ($request->filled('credit_note_no')) $q->where('credit_note_no', 'like', '%'.$request->credit_note_no.'%');

        $rows = $q->paginate((int)($request->length ?? 10));

        $data = $rows->map(function ($cn) {
            return [
                'id' => $cn->id,
                'credit_note_no' => $cn->credit_note_no ?? ('CN-'.$cn->id),
                'customer' => $cn->customer?->name ?? ('Customer #'.$cn->customer_id),
                'invoice_no' => $cn->invoice?->invoice_no ?? ($cn->sales_invoice_id ? 'INV-'.$cn->sales_invoice_id : '-'),
                'date' => $cn->credit_note_date?->format('d M Y') ?? '-',
                'amount' => number_format((float)$cn->grand_total, 2),
                'status' => '<span class="badge badge-'.$cn->status_badge.'">'.strtoupper($cn->status).'</span>',
                'actions' => view('sales.credit_notes.partials.actions', ['creditNote'=>$cn])->render(),
            ];
        })->values();

        return response()->json([
            'draw' => (int)($request->draw ?? 1),
            'recordsTotal' => $rows->total(),
            'recordsFiltered' => $rows->total(),
            'data' => $data,
        ]);
    }

    public function create()
    {
        return view('sales.credit_notes.form', [
            'creditNote' => new SalesCreditNote(),
            'mode' => 'create',
        ]);
    }

    public function show(SalesCreditNote $creditNote)
    {
        $creditNote->load(['customer','invoice','stockReturn','lines']);
        return view('sales.credit_notes.show', compact('creditNote'));
    }

    public function edit(SalesCreditNote $creditNote)
    {
        if ($creditNote->status !== 'draft') {
            return redirect()->route('admin.sales.credit-notes.show', $creditNote->id)
                ->with('error','Only draft credit notes can be edited.');
        }

        $creditNote->load(['customer','invoice','stockReturn','lines']);
        return view('sales.credit_notes.form', [
            'creditNote' => $creditNote,
            'mode' => 'edit',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateHeader($request);

        return DB::transaction(function () use ($data, $request) {

            $cn = SalesCreditNote::create([
                'customer_id' => $data['customer_id'],
                'sales_invoice_id' => $data['sales_invoice_id'] ?? null,
                'stock_return_id' => $data['stock_return_id'] ?? null,
                'credit_note_no' => $data['credit_note_no'] ?? null,
                'credit_note_date' => $data['credit_note_date'],
                'currency_code' => $data['currency_code'] ?? 'NGN',
                'reason' => $data['reason'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'status' => 'draft',
            ]);

            $this->syncLines($cn, $request->input('lines', []));

            $this->recalcTotals($cn);

            return response()->json([
                'message' => 'Credit note created.',
                'redirect' => route('admin.sales.credit-notes.edit', $cn->id),
            ]);
        });
    }

    public function update(Request $request, SalesCreditNote $creditNote)
    {
        if ($creditNote->status !== 'draft') {
            return response()->json(['message'=>'Only draft credit notes can be edited.'], 422);
        }

        $data = $this->validateHeader($request);

        return DB::transaction(function () use ($data, $request, $creditNote) {

            $creditNote->update([
                'customer_id' => $data['customer_id'],
                'sales_invoice_id' => $data['sales_invoice_id'] ?? null,
                'stock_return_id' => $data['stock_return_id'] ?? null,
                'credit_note_no' => $data['credit_note_no'] ?? $creditNote->credit_note_no,
                'credit_note_date' => $data['credit_note_date'],
                'currency_code' => $data['currency_code'] ?? $creditNote->currency_code,
                'reason' => $data['reason'] ?? $creditNote->reason,
                'remarks' => $data['remarks'] ?? $creditNote->remarks,
            ]);

            $this->syncLines($creditNote, $request->input('lines', []));
            $this->recalcTotals($creditNote);

            return response()->json(['message'=>'Credit note updated.']);
        });
    }

    public function post(SalesCreditNote $creditNote)
    {
        if ($creditNote->status !== 'draft') {
            return response()->json(['message'=>'Only draft credit notes can be posted.'], 422);
        }

        if ($creditNote->lines()->count() <= 0) {
            return response()->json(['message'=>'Add at least one line before posting.'], 422);
        }

        if ((float)$creditNote->grand_total <= 0) {
            return response()->json(['message'=>'Credit note total must be greater than zero.'], 422);
        }

        $creditNote->update([
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by' => auth()->id(),
        ]);

        return response()->json(['message'=>'Credit note posted.']);
    }

    public function void(SalesCreditNote $creditNote)
    {
        if ($creditNote->status !== 'posted') {
            return response()->json(['message'=>'Only posted credit notes can be voided.'], 422);
        }

        $creditNote->update([
            'status' => 'void',
            'voided_at' => now(),
            'voided_by' => auth()->id(),
        ]);

        return response()->json(['message'=>'Credit note voided.']);
    }

    public function destroy(SalesCreditNote $creditNote)
    {
        if ($creditNote->status !== 'draft') {
            return response()->json(['message'=>'Only draft credit notes can be deleted.'], 422);
        }

        $creditNote->delete(); // cascades to lines
        return response()->json(['message'=>'Deleted.']);
    }

    private function validateHeader(Request $request): array
    {
        return Validator::make($request->all(), [
            'customer_id' => ['required','integer','exists:customers,id'],
            'sales_invoice_id' => ['nullable','integer','exists:sales_invoices,id'],
            'stock_return_id' => ['nullable','integer','exists:stock_returns,id'],
            'credit_note_no' => ['nullable','string','max:40'],
            'credit_note_date' => ['required','date'],
            'currency_code' => ['nullable','string','max:10'],
            'reason' => ['nullable','string'],
            'remarks' => ['nullable','string'],
        ])->validate();
    }

    private function syncLines(SalesCreditNote $cn, array $lines): void
    {
        // reset lines (simple + reliable)
        $cn->lines()->delete();

        foreach ($lines as $l) {
            $qty = (float)($l['qty'] ?? 0);
            $unit = (float)($l['unit_price'] ?? 0);
            if ($qty <= 0) continue;

            $taxRate = isset($l['tax_rate']) ? (float)$l['tax_rate'] : 0;
            $base = $qty * $unit;
            $taxAmount = $taxRate > 0 ? ($base * $taxRate) : 0;
            $total = $base + $taxAmount;

            SalesCreditNoteLine::create([
                'sales_credit_note_id' => $cn->id,
                'sales_invoice_line_id' => $l['sales_invoice_line_id'] ?? null,
                'product_variant_id' => $l['product_variant_id'] ?? null,
                'description' => $l['description'] ?? null,
                'qty' => $qty,
                'unit_price' => $unit,
                'tax_rate' => $taxRate > 0 ? $taxRate : null,
                'tax_amount' => $taxAmount,
                'line_total' => $total,
            ]);
        }
    }

    private function recalcTotals(SalesCreditNote $cn): void
    {
        $subtotal = (float)$cn->lines()->sum(DB::raw('qty * unit_price'));
        $taxTotal = (float)$cn->lines()->sum('tax_amount');
        $grand = $subtotal + $taxTotal;

        $cn->update([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grand,
        ]);
    }
    
    public function select2Invoices(Request $request)
    {
        $q = trim((string)$request->q);
        $customerId = (int)($request->customer_id ?? 0);
    
        $rows = SalesInvoice::query()
            ->select('id', 'customer_id', 'invoice_no', 'invoice_date', 'status', 'grand_total')
            ->when($customerId > 0, fn($x) => $x->where('customer_id', $customerId))
            ->when($q !== '', function ($x) use ($q) {
                $x->where('invoice_no', 'like', "%{$q}%");
            })
            // recommended filters:
            ->whereIn('status', ['posted']) // adjust if you use other statuses
            ->orderByDesc('id')
            ->limit(20)
            ->get();
    
        $results = $rows->map(function ($inv) {
            $text = ($inv->invoice_no ?? ('INV-'.$inv->id))
                  .' | '.optional($inv->invoice_date)->format('d M Y')
                  .' | Total: '.number_format((float)$inv->grand_total, 2);
    
            return [
                'id' => $inv->id,
                'text' => $text,
            ];
        });
    
        return response()->json(['results' => $results]);
    }
    
    public function invoiceLines(SalesInvoice $invoice, Request $request)
    {
        // security: ensure customer matches selected customer (optional but recommended)
        $customerId = (int)($request->customer_id ?? 0);
        if ($customerId > 0 && (int)$invoice->customer_id !== $customerId) {
            return response()->json(['message' => 'Invoice does not belong to selected customer.'], 422);
        }
    
        // only allow posted invoices to load (recommended)
        if (!in_array($invoice->status, ['posted'], true)) {
            return response()->json(['message' => 'Only posted invoices can be loaded.'], 422);
        }
    
        $invoice->load(['lines']);
    
        $lines = $invoice->lines->map(function ($l) {
            // adapt these fields to your schema
            $desc = $l->description
                ?? $l->product_name
                ?? $l->product?->product_name
                ?? 'Invoice line';
    
            $qty = (float)($l->qty ?? $l->quantity ?? 0);
            $unit = (float)($l->unit_price ?? $l->price ?? 0);
    
            // tax rate: if stored as percent (e.g., 7.5) keep it as decimal fraction? choose one and stay consistent.
            // Here: tax_rate is FRACTION (e.g., 0.075). If you store 7.5, change to ($l->tax_rate/100).
            $taxRate = (float)($l->tax_rate ?? $l->tax_percent ?? 0);
    
            return [
                'description' => $desc,
                'qty' => $qty,
                'unit_price' => $unit,
                'tax_rate' => $taxRate,
            ];
        })->values();
    
        return response()->json([
            'invoice' => [
                'id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no ?? ('INV-'.$invoice->id),
                'invoice_date' => optional($invoice->invoice_date)->format('Y-m-d'),
                'currency_code' => $invoice->currency_code ?? null,
                'grand_total' => (float)$invoice->grand_total,
            ],
            'lines' => $lines,
        ]);
    }
}
