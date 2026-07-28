<?php

namespace Modules\Sales\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use Modules\Sales\Models\SalesInvoice;
use Modules\Sales\Models\SalesInvoiceLine;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderLine;
use Modules\Finance\Services\Posting\SalesInvoicePostingService;

class SalesInvoiceController extends Controller
{
    public function __construct()
    {
        // ✅ Adjust to your permission strings
        $this->middleware('permission:sales.invoices.view')->only(['index','datatable','show','pdf']);
        $this->middleware('permission:sales.invoices.create')->only(['create','store']);
        $this->middleware('permission:sales.invoices.edit')->only(['edit','update']);
        $this->middleware('permission:sales.invoices.post')->only(['post']);
        $this->middleware('permission:sales.invoices.cancel')->only(['cancel']);
        $this->middleware('permission:sales.invoices.delete')->only(['destroy']);
    }

    private function audit(string $action, string $description, $subject = null, array $meta = []): void
    {
        // ✅ Replace with your actual audit system
        // $this->audit(action: $action, description: $description, subject: $subject, meta: $meta);
    }

    public function index()
    {
        return view('sales.invoices.index');
    }

    public function datatable(Request $request)
    {
        $q = SalesInvoice::query()
            ->with(['customer','order'])
            ->orderByDesc('id');

        if ($request->filled('status')) $q->where('status', $request->status);
        if ($request->filled('customer_id')) $q->where('customer_id', (int)$request->customer_id);
        if ($request->filled('invoice_no')) $q->where('invoice_no', 'like', '%'.$request->invoice_no.'%');

        $rows = $q->paginate((int)($request->length ?? 10));

        $data = $rows->map(function ($inv) {
            return [
                'id'          => $inv->id,
                'invoice_no'  => $inv->invoice_no ?? ('INV-'.$inv->id),
                'order'       => $inv->order?->order_no ?? '-',
                'customer'    => $inv->customer?->name ?? ('Customer #'.$inv->customer_id),
                'invoice_date'=> $inv->invoice_date?->format('d M Y') ?? '-',
                'total'       => number_format((float)$inv->grand_total, 2),
                'status'      => '<span class="badge badge-'.$inv->status_badge.'">'.strtoupper($inv->status).'</span>',
                'created'     => optional($inv->created_at)->format('d M Y, H:i'),
                'actions'     => view('sales.invoices.partials.actions', compact('inv'))->render(),
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
        return view('sales.invoices.form', [
            'invoice' => new SalesInvoice(),
            'mode'    => 'create',
        ]);
    }
    
    public function edit(SalesInvoice $invoice)
    {
        if (in_array($invoice->status, ['posted','cancelled'], true)) {
            return redirect()
                ->route('admin.sales.invoices.show', $invoice->id)
                ->with('error', 'You cannot edit a posted/cancelled invoice.');
        }
    
        $invoice->load(['lines.variant.product','lines.orderLine','customer','order']);
    
        return view('sales.invoices.form', [
            'invoice' => $invoice,
            'mode'    => 'edit',
        ]);
    }

    public function store(Request $request)
    {
        $payload = $this->validateInvoice($request);

        return DB::transaction(function () use ($payload) {

            $invoice = SalesInvoice::create([
                'invoice_no'     => $payload['invoice_no'] ?? null,
                'sales_order_id' => $payload['sales_order_id'],
                'customer_id'    => $payload['customer_id'],
                'invoice_date'   => $payload['invoice_date'] ?? now()->toDateString(),
                'due_date'       => $payload['due_date'] ?? null,
                'currency_code'  => $payload['currency_code'] ?? null,
                'reference'      => $payload['reference'] ?? null,
                'remarks'        => $payload['remarks'] ?? null,
                'status'         => 'draft',
            ]);

            $totals = $this->syncLinesAndComputeTotals($invoice, $payload['lines'] ?? [], null);

            $invoice->update($totals);

            $this->audit(
                action: 'create',
                description: 'Created sales invoice (draft)',
                subject: $invoice,
                meta: ['invoice_id' => $invoice->id, 'order_id' => $invoice->sales_order_id]
            );

            return response()->json([
                'message'  => 'Invoice created.',
                'id'       => $invoice->id,
                'redirect' => route('admin.sales.invoices.edit', $invoice->id),
            ]);
        });
    }

    public function update(Request $request, SalesInvoice $invoice)
    {
        if (in_array($invoice->status, ['posted','cancelled'], true)) {
            return response()->json(['message' => 'You cannot update a posted/cancelled invoice.'], 422);
        }

        $payload = $this->validateInvoice($request, $invoice->id);

        return DB::transaction(function () use ($payload, $invoice) {

            $invoice->update([
                'invoice_no'     => $payload['invoice_no'] ?? $invoice->invoice_no,
                'sales_order_id' => $payload['sales_order_id'],
                'customer_id'    => $payload['customer_id'],
                'invoice_date'   => $payload['invoice_date'] ?? $invoice->invoice_date,
                'due_date'       => $payload['due_date'] ?? $invoice->due_date,
                'currency_code'  => $payload['currency_code'] ?? $invoice->currency_code,
                'reference'      => $payload['reference'] ?? $invoice->reference,
                'remarks'        => $payload['remarks'] ?? $invoice->remarks,
            ]);

            // rebuild lines
            $invoice->lines()->delete();

            // IMPORTANT: exclude this invoice when computing remaining (so editing works)
            $totals = $this->syncLinesAndComputeTotals($invoice, $payload['lines'] ?? [], $invoice->id);

            $invoice->update($totals);

            $this->audit(
                action: 'update',
                description: 'Updated sales invoice (draft)',
                subject: $invoice,
                meta: ['invoice_id' => $invoice->id]
            );

            return response()->json(['message' => 'Invoice updated.']);
        });
    }

    public function show(SalesInvoice $invoice)
    {
        $invoice->load(['lines.variant.product', 'customer','order']);
        return view('sales.invoices.show', compact('invoice'));
    }

    public function post(SalesInvoice $invoice)
    {
        if ($invoice->status !== 'draft') {
            return response()->json(['message' => 'Only draft invoices can be posted.'], 422);
        }

        $invoice->load(['lines']);

        if ($invoice->lines->isEmpty()) {
            return response()->json(['message' => 'Add at least one line before posting.'], 422);
        }

        $invoice->update([
            'status'    => 'posted',
            'posted_at' => now(),
            'posted_by' => auth()->id(),
        ]);
        
        $companyId = auth()->user()->company_id ?? 1;
        $jeId = SalesInvoicePostingService::post($companyId, $invoice->id);
        
        $this->audit('post', 'Posted sales invoice', $invoice, ['invoice_id' => $invoice->id]);

        return response()->json(['message' => 'Invoice posted.']);
    }

    public function cancel(SalesInvoice $invoice)
    {
        if ($invoice->status !== 'draft') {
            return response()->json(['message' => 'Only draft invoices can be cancelled.'], 422);
        }

        $invoice->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => auth()->id(),
        ]);

        $this->audit('cancel', 'Cancelled sales invoice', $invoice, ['invoice_id' => $invoice->id]);

        return response()->json(['message' => 'Invoice cancelled.']);
    }

    public function destroy(SalesInvoice $invoice)
    {
        if ($invoice->status !== 'draft') {
            return response()->json(['message' => 'Only draft invoices can be deleted.'], 422);
        }

        $id = $invoice->id;
        $invoice->delete();

        $this->audit('delete', 'Deleted sales invoice', null, ['invoice_id' => $id]);

        return response()->json(['message' => 'Deleted.']);
    }

    /**
     * ✅ Select2: confirmed orders only
     */
    public function select2ConfirmedOrders(Request $request)
    {
        $q = trim((string)$request->q);

        $orders = SalesOrder::query()
            ->where('status', 'confirmed')
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where('order_no', 'like', "%{$q}%");
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id','order_no','customer_id','order_date']);

        $results = $orders->map(fn($o) => [
            'id'   => $o->id,
            'text' => $o->order_no.' ('.$o->order_date.')',
        ]);

        return response()->json(['results' => $results]);
    }

    /**
     * ✅ When order selected -> customer + lines with qty_remaining_for_invoice
     */
    public function orderPayload(SalesOrder $order)
    {
        if ($order->status !== 'confirmed') {
            return response()->json(['message' => 'Only confirmed orders can be invoiced.'], 422);
        }

        $order->load(['customer','lines.variant.product']);

        $lines = $order->lines->map(function ($ln) use ($order) {
            $ordered = (float)$ln->qty_ordered;

            // sum posted invoice qty for this order line
            $alreadyInvoiced = (float) DB::table('sales_invoice_lines as sil')
                ->join('sales_invoices as si', 'si.id', '=', 'sil.sales_invoice_id')
                ->where('si.sales_order_id', $order->id)
                ->where('si.status', 'posted')
                ->where('sil.sales_order_line_id', $ln->id)
                ->sum('sil.qty_to_invoice');

            $remaining = max(0, $ordered - $alreadyInvoiced);

            return [
                'sales_order_line_id' => $ln->id,
                'product_variant_id'  => (string)$ln->product_variant_id,
                'variant_text'        => ($ln->variant?->product?->product_name ?? 'Item').' - '.($ln->variant?->sku ?? ('Variant #'.$ln->product_variant_id)),
                'qty_ordered'         => $ordered,
                'qty_invoiced'        => $alreadyInvoiced,
                'qty_remaining'       => $remaining,
                'unit_price'          => (float)$ln->unit_price,
            ];
        })->values();

        return response()->json([
            'order' => [
                'id'            => $order->id,
                'order_no'      => $order->order_no,
                'customer_id'   => (string)$order->customer_id,
                'customer_text' => $order->customer?->name ?? ('Customer #'.$order->customer_id),
            ],
            'lines' => $lines,
        ]);
    }

    /**
     * ✅ PDF
     * Requires barryvdh/laravel-dompdf (most ERPs use it)
     */
    public function pdf(SalesInvoice $invoice)
    {
        $invoice->load(['lines.variant.product','customer','order']);

        // If you have dompdf:
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sales.invoices.pdf', compact('invoice'));
            $name = ($invoice->invoice_no ?? ('INV-'.$invoice->id)).'.pdf';
            return $pdf->download($name);
        }

        // fallback: HTML preview
        return view('sales.invoices.pdf', compact('invoice'));
    }

    private function validateInvoice(Request $request, ?int $invoiceId = null): array
    {
        // Normalize types coming from UI
        $typeMap = [
            'product'        => 'product',
            'item'           => 'product',
    
            'custom'         => 'custom',
            'custom_charge'  => 'custom',
            'charge'         => 'custom',
            'fixed'          => 'custom',
            'fee'            => 'custom',
    
            'percent'        => 'percent',
            'percentage'     => 'percent',
            'percent_charge' => 'percent',
    
            'discount'       => 'discount',
            'disc'           => 'discount',
        ];
    
        $payload = $request->all();
    
        if (!empty($payload['lines']) && is_array($payload['lines'])) {
            foreach ($payload['lines'] as $i => $ln) {
                $raw = strtolower(trim((string)($ln['line_type'] ?? 'custom')));
                $payload['lines'][$i]['line_type'] = $typeMap[$raw] ?? $raw;
            }
        }
    
        $v = Validator::make($payload, [
            'invoice_no'     => ['nullable','string','max:40'],
            'sales_order_id' => ['required','integer','exists:sales_orders,id'],
            'customer_id'    => ['required','integer','exists:customers,id'],
            'invoice_date'   => ['nullable','date'],
            'due_date'       => ['nullable','date'],
            'currency_code'  => ['nullable','string','max:10'],
            'reference'      => ['nullable','string','max:80'],
            'remarks'        => ['nullable','string'],
    
            'lines'             => ['required','array','min:1'],
            'lines.*.line_type' => ['required', Rule::in(['product','custom','percent','discount'])],
    
            // product linkage (nullable here; enforced in after())
            'lines.*.sales_order_line_id' => ['nullable','integer','exists:sales_order_lines,id'],
            'lines.*.product_variant_id'  => ['nullable','integer','exists:product_variants,id'],
    
            'lines.*.title'       => ['nullable','string','max:80'],
            'lines.*.description' => ['nullable','string','max:255'],
    
            'lines.*.qty_to_invoice' => ['nullable','numeric','min:0'],
            'lines.*.unit_price'     => ['nullable','numeric','min:0'],
    
            // percent lines (your UI)
            'lines.*.calc_basis'   => ['nullable', Rule::in(['subtotal','grand_total'])],
            'lines.*.calc_percent' => ['nullable','numeric','min:0','max:100'],
    
            // tax
            'lines.*.tax_code_id' => ['nullable','integer'],
            'lines.*.tax_rate'    => ['nullable','numeric','min:0','max:100'],
            'lines.*.is_taxable'  => ['nullable','boolean'],
        ]);
    
        $v->after(function ($validator) use ($payload) {
            $lines = $payload['lines'] ?? [];
    
            foreach ($lines as $i => $ln) {
                $type = $ln['line_type'] ?? null;
    
                if ($type === 'product') {
                    if (empty($ln['sales_order_line_id'])) $validator->errors()->add("lines.$i.sales_order_line_id", 'Required for product line.');
                    if (empty($ln['product_variant_id']))  $validator->errors()->add("lines.$i.product_variant_id",  'Required for product line.');
                    if (!isset($ln['qty_to_invoice']))     $validator->errors()->add("lines.$i.qty_to_invoice",      'Qty is required.');
                    if (!isset($ln['unit_price']))         $validator->errors()->add("lines.$i.unit_price",          'Unit price is required.');
                }
    
                if ($type === 'custom') {
                    if (!isset($ln['unit_price'])) $validator->errors()->add("lines.$i.unit_price", 'Amount is required for custom line.');
                    if (empty(trim((string)($ln['description'] ?? '')))) $validator->errors()->add("lines.$i.description", 'Description is required for custom line.');
                }
    
                if ($type === 'percent') {
                    if (!isset($ln['calc_percent'])) $validator->errors()->add("lines.$i.calc_percent", 'Percent rate is required.');
                    if (empty(trim((string)($ln['description'] ?? '')))) $validator->errors()->add("lines.$i.description", 'Description is required for percent charge.');
                }
    
                if ($type === 'discount') {
                    // accept either fixed amount (unit_price) or percent (calc_percent)
                    $hasPct = isset($ln['calc_percent']);
                    $hasAmt = isset($ln['unit_price']);
                    if (!$hasPct && !$hasAmt) {
                        $validator->errors()->add("lines.$i.calc_percent", 'Provide calc_percent or unit_price for discount.');
                        $validator->errors()->add("lines.$i.unit_price", 'Provide calc_percent or unit_price for discount.');
                    }
                    if (empty(trim((string)($ln['description'] ?? '')))) $validator->errors()->add("lines.$i.description", 'Description is required for discount line.');
                }
            }
        });
    
        return $v->validate();
    }


    /**
     * ✅ Server-side cap logic + totals
     * qty_to_invoice <= (qty_ordered - posted_invoiced), excluding current invoice if editing
     */
    private function computeInvoiceTotals(array $lines): array
    {
        $subtotal = 0.0;   // items only
        $charges  = 0.0;   // charges positive
        $discount = 0.0;   // discount negative values
        $taxTotal = 0.0;
    
        foreach ($lines as &$ln) {
    
            $type = $ln['line_type'] ?? 'item';
            $qty  = (float)($ln['qty_to_invoice'] ?? 0);
            $price= (float)($ln['unit_price'] ?? 0);
            $rate = (float)($ln['tax_rate'] ?? 0);
            $taxable = !empty($ln['is_taxable']);
    
            // line_total should already be computed before here,
            // but we compute again safely:
            $lineTotal = 0.0;
    
            if ($type === 'item') {
                $lineTotal = max(0, $qty) * max(0, $price);
                $subtotal += $lineTotal;
    
            } elseif ($type === 'charge') {
                $lineTotal = (float)($ln['line_total'] ?? 0);
                $charges += $lineTotal;
    
            } elseif ($type === 'discount') {
                // make it negative always
                $lineTotal = -abs((float)($ln['line_total'] ?? 0));
                $discount += $lineTotal; // negative
    
            } else {
                // note / other: does not affect totals
                $lineTotal = (float)($ln['line_total'] ?? 0);
            }
    
            // tax on line (only if taxable and line affects money)
            $taxAmount = 0.0;
            if ($taxable && in_array($type, ['item','charge'], true) && $rate > 0) {
                $taxAmount = ($lineTotal * $rate) / 100.0;
            }
    
            $ln['line_total'] = $lineTotal;
            $ln['tax_amount'] = $taxAmount;
    
            $taxTotal += $taxAmount;
        }
    
        $net = $subtotal + $charges + $discount;
        $grand = $net + $taxTotal;
    
        return [
            'lines'       => $lines,
            'subtotal'    => $subtotal,
            'tax_total'   => $taxTotal,
            'grand_total' => $grand,
        ];
    }


    private function syncLinesAndComputeTotals(SalesInvoice $invoice, array $lines, ?int $excludeInvoiceId = null): array
    {
        $subtotal = 0.0; // net before tax (discounts reduce this)
        $taxTotal = 0.0;
    
        // --- First pass: compute product + custom + fixed discount bases (percent lines later) ---
        $computed = [];
    
        foreach ($lines as $ln) {
            $type = strtolower((string)($ln['line_type'] ?? 'custom'));
            $type = in_array($type, ['product','custom','percent','discount'], true) ? $type : 'custom';
    
            $isTaxable = array_key_exists('is_taxable', $ln) ? (bool)$ln['is_taxable'] : true;
            $taxRate   = (float)($ln['tax_rate'] ?? 0);
    
            $qty  = (float)($ln['qty_to_invoice'] ?? 1);
            $unit = (float)($ln['unit_price'] ?? 0);
    
            $base = 0.0;
    
            if ($type === 'product') {
                $orderLineId = (int)($ln['sales_order_line_id'] ?? 0);
                $variantId   = (int)($ln['product_variant_id'] ?? 0);
    
                $orderLine = SalesOrderLine::query()
                    ->where('id', $orderLineId)
                    ->where('sales_order_id', $invoice->sales_order_id)
                    ->first();
    
                if (!$orderLine) continue;
    
                $ordered = (float)$orderLine->qty_ordered;
    
                $alreadyInvoiced = (float) DB::table('sales_invoice_lines as sil')
                    ->join('sales_invoices as si', 'si.id', '=', 'sil.sales_invoice_id')
                    ->where('si.sales_order_id', $invoice->sales_order_id)
                    ->where('si.status', 'posted')
                    ->where('sil.sales_order_line_id', $orderLineId)
                    ->when($excludeInvoiceId, fn($q) => $q->where('si.id', '!=', $excludeInvoiceId))
                    ->sum('sil.qty_to_invoice');
    
                $maxByOrder = max(0, $ordered - $alreadyInvoiced);
                $qty = min(max(0, $qty), $maxByOrder);
    
                $base = $qty * max(0, $unit);
    
                $computed[] = [
                    'type' => $type,
                    'sales_order_line_id' => $orderLineId,
                    'product_variant_id'  => $variantId,
                    'description' => $ln['description'] ?? null,
                    'title' => $ln['title'] ?? null,
                    'qty' => $qty,
                    'unit' => max(0, $unit),
                    'base' => $base,
                    'tax_rate' => $taxRate,
                    'is_taxable' => $isTaxable,
                    'tax_code_id' => $ln['tax_code_id'] ?? null,
                    'calc_basis' => $ln['calc_basis'] ?? null,
                    'calc_percent' => $ln['calc_percent'] ?? null,
                ];
    
                $subtotal += $base;
                continue;
            }
    
            if ($type === 'custom') {
                $qty = max(0, $qty ?: 1);
                $base = $qty * max(0, $unit);
                $computed[] = [
                    'type' => $type,
                    'sales_order_line_id' => null,
                    'product_variant_id'  => null,
                    'description' => $ln['description'] ?? null,
                    'title' => $ln['title'] ?? null,
                    'qty' => $qty,
                    'unit' => max(0, $unit),
                    'base' => $base,
                    'tax_rate' => $taxRate,
                    'is_taxable' => $isTaxable,
                    'tax_code_id' => $ln['tax_code_id'] ?? null,
                    'calc_basis' => $ln['calc_basis'] ?? null,
                    'calc_percent' => $ln['calc_percent'] ?? null,
                ];
                $subtotal += $base;
                continue;
            }
    
            if ($type === 'discount') {
                // fixed discount: unit_price * qty (stored negative)
                if (isset($ln['unit_price']) && $unit > 0) {
                    $qty = max(0, $qty ?: 1);
                    $base = -1 * abs($qty * $unit);
    
                    $computed[] = [
                        'type' => $type,
                        'sales_order_line_id' => null,
                        'product_variant_id'  => null,
                        'description' => $ln['description'] ?? null,
                        'title' => $ln['title'] ?? null,
                        'qty' => $qty,
                        'unit' => abs($unit),
                        'base' => $base,
                        'tax_rate' => 0, // discounts usually not taxed; keep 0
                        'is_taxable' => false,
                        'tax_code_id' => $ln['tax_code_id'] ?? null,
                        'calc_basis' => $ln['calc_basis'] ?? null,
                        'calc_percent' => $ln['calc_percent'] ?? null,
                    ];
    
                    $subtotal += $base;
                } else {
                    // percent discount handled in pass 2 (same as percent)
                    $computed[] = [
                        'type' => $type,
                        'sales_order_line_id' => null,
                        'product_variant_id'  => null,
                        'description' => $ln['description'] ?? null,
                        'title' => $ln['title'] ?? null,
                        'qty' => 1,
                        'unit' => 0,
                        'base' => 0,
                        'tax_rate' => 0,
                        'is_taxable' => false,
                        'tax_code_id' => $ln['tax_code_id'] ?? null,
                        'calc_basis' => $ln['calc_basis'] ?? 'subtotal',
                        'calc_percent' => (float)($ln['calc_percent'] ?? 0),
                    ];
                }
                continue;
            }
    
            if ($type === 'percent') {
                // handled in pass 2
                $computed[] = [
                    'type' => $type,
                    'sales_order_line_id' => null,
                    'product_variant_id'  => null,
                    'description' => $ln['description'] ?? null,
                    'title' => $ln['title'] ?? null,
                    'qty' => 1,
                    'unit' => 0,
                    'base' => 0,
                    'tax_rate' => $taxRate,
                    'is_taxable' => $isTaxable,
                    'tax_code_id' => $ln['tax_code_id'] ?? null,
                    'calc_basis' => $ln['calc_basis'] ?? 'subtotal',
                    'calc_percent' => (float)($ln['calc_percent'] ?? 0),
                ];
                continue;
            }
        }
    
        // --- Second pass: percent charge + percent discount (based on current subtotal) ---
        $runningSubtotal = $subtotal;
        $runningGrandBeforePercent = $runningSubtotal; // tax not added yet in this simple base; tax is computed per-line below
    
        foreach ($computed as &$c) {
            if (!in_array($c['type'], ['percent','discount'], true)) continue;
    
            $pct = (float)($c['calc_percent'] ?? 0);
            if ($pct <= 0) continue;
    
            $basis = $c['calc_basis'] ?? 'subtotal';
            $baseValue = ($basis === 'grand_total') ? $runningGrandBeforePercent : $runningSubtotal;
    
            $amount = ($baseValue * $pct) / 100.0;
    
            if ($c['type'] === 'discount') {
                $amount = -abs($amount);
                $c['is_taxable'] = false;
                $c['tax_rate'] = 0;
            }
    
            $c['qty']  = 1;
            $c['unit'] = abs($amount);
            $c['base'] = $amount;
    
            $runningSubtotal += $amount;
            $runningGrandBeforePercent = $runningSubtotal; // still before tax
        }
        unset($c);
    
        // --- Create lines + compute tax ---
        foreach ($computed as $c) {
            $base = (float)$c['base'];
    
            $taxAmount = 0.0;
            if (!empty($c['is_taxable']) && (float)$c['tax_rate'] > 0 && $base > 0) {
                $taxAmount = ($base * (float)$c['tax_rate']) / 100.0;
            }
    
            $lineTotal = $base + $taxAmount;
    
            $taxTotal += $taxAmount;
    
            SalesInvoiceLine::create([
                'sales_invoice_id'    => $invoice->id,
                'sales_order_line_id' => $c['sales_order_line_id'],
                'product_variant_id'  => $c['product_variant_id'],
                'line_type'           => $c['type'],
                'charge_code'         => $c['title'], // use this to store "Delivery", "Handling", etc.
                'is_taxable'          => !empty($c['is_taxable']) ? 1 : 0,
                'tax_rate'            => (float)$c['tax_rate'],
                'tax_amount'          => $taxAmount,
                'description'         => $c['description'],
                'qty_to_invoice'      => (float)$c['qty'],
                'unit_price'          => (float)$c['unit'],
                'line_total'          => $lineTotal,
            ]);
        }
    
        // subtotal here is net before tax (including discounts)
        $subtotal = array_reduce($computed, fn($s,$c) => $s + (float)$c['base'], 0.0);
        $grand = $subtotal + $taxTotal;
    
        return [
            'subtotal'    => $subtotal,
            'tax_total'   => $taxTotal,
            'grand_total' => $grand,
        ];
    }

}
