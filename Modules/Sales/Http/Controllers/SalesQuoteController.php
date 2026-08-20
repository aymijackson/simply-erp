<?php

namespace Modules\Sales\Http\Controllers;

use App\Models\AuditLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesQuote;
use Yajra\DataTables\Facades\DataTables;

class SalesQuoteController extends Controller
{
    public function index()
    {
        return view('sales.quotes.index');
    }

    public function datatable(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $q = SalesQuote::query()
            ->where('company_id', $companyId)
            ->with('customer:id,name')
            ->select(['id', 'quote_no', 'customer_id', 'quote_date', 'currency_code', 'status', 'total_amount', 'created_at'])
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('customer_id')) {
            $q->where('customer_id', $request->customer_id);
        }
        if ($request->filled('quote_no')) {
            $q->where('quote_no', 'like', '%'.$request->quote_no.'%');
        }
        if ($request->filled('date_from')) {
            $q->whereDate('quote_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->whereDate('quote_date', '<=', $request->date_to);
        }

        return DataTables::eloquent($q)
            ->addColumn('customer', fn ($r) => $r->customer?->name ?? '-')
            ->addColumn('quote_date_fmt', fn ($r) => optional($r->quote_date)->format('d-m-Y'))
            ->addColumn('total_amount_fmt', fn ($r) => number_format((float) $r->total_amount, 2))
            ->addColumn('actions', fn ($r) => view('sales.quotes.partials.actions', compact('r'))->render())
            ->addColumn('created_at', fn ($r) => date('d-m-Y', strtotime($r->created_at)))
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function create()
    {
        return view('sales.quotes.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateQuote($request);
        $companyId = auth()->user()->company_id ?? 1;

        $quote = DB::transaction(function () use ($data, $companyId) {
            $hdr = SalesQuote::create([
                'company_id'    => $companyId,
                'customer_id'   => $data['header']['customer_id'],
                'quote_no'      => $this->nextQuoteNo($companyId),
                'quote_date'    => $data['header']['quote_date'],
                'valid_until'   => $data['header']['valid_until'],
                'currency_code' => $data['header']['currency_code'],
                'reference'     => $data['header']['reference'],
                'notes'         => $data['header']['notes'],
                'status'        => 'draft',
                'created_by'    => auth()->id(),
                'updated_by'    => auth()->id(),
            ]);

            $hdr->lines()->createMany($data['lines']);
            $hdr->update($this->computeTotals($hdr->fresh('lines')));

            return $hdr->fresh('lines');
        });

        $this->audit('create', 'Created sales quote draft', $quote, [
            'quote_no' => $quote->quote_no,
            'customer_id' => $quote->customer_id,
            'total_amount' => (float) $quote->total_amount,
        ]);

        return redirect()->route('admin.sales.quotes.show', $quote->id)
            ->with('success', 'Sales Quote created.');
    }

    public function show(SalesQuote $quote)
    {
        $this->authorizeCompany($quote);
        $quote->load(['customer', 'lines.variant.product', 'salesOrder', 'reviewer']);

        return view('sales.quotes.show', compact('quote'));
    }

    public function edit(SalesQuote $quote)
    {
        $this->authorizeCompany($quote);

        if (! in_array($quote->status, ['draft', 'won'], true)) {
            return redirect()->route('admin.sales.quotes.show', $quote->id)
                ->with('error', 'Only draft or won quotes can be edited.');
        }

        $quote->load(['customer', 'lines.variant.product']);

        return view('sales.quotes.edit', compact('quote'));
    }

    public function update(Request $request, SalesQuote $quote)
    {
        $this->authorizeCompany($quote);

        if (! in_array($quote->status, ['draft', 'won'], true)) {
            return back()->with('error', 'Only draft or won quotes can be edited.');
        }

        $data = $this->validateQuote($request);

        DB::transaction(function () use ($data, $quote) {
            $quote->update([
                'customer_id'   => $data['header']['customer_id'],
                'quote_date'    => $data['header']['quote_date'],
                'valid_until'   => $data['header']['valid_until'],
                'currency_code' => $data['header']['currency_code'],
                'reference'     => $data['header']['reference'],
                'notes'         => $data['header']['notes'],
                'updated_by'    => auth()->id(),
            ]);

            $quote->lines()->delete();
            $quote->lines()->createMany($data['lines']);
            $quote->update($this->computeTotals($quote->fresh('lines')));
        });

        $this->audit('update', 'Updated sales quote', $quote, [
            'quote_no' => $quote->quote_no,
            'status' => $quote->status,
        ]);

        return redirect()->route('admin.sales.quotes.show', $quote->id)
            ->with('success', 'Sales Quote updated.');
    }

    public function destroy(SalesQuote $quote)
    {
        $this->authorizeCompany($quote);

        if ($quote->status !== 'draft') {
            return back()->with('error', 'Only draft quotes can be deleted.');
        }

        $meta = ['quote_no' => $quote->quote_no, 'customer_id' => $quote->customer_id];

        $quote->lines()->delete();
        $quote->delete();

        $this->audit('delete', 'Deleted sales quote draft', null, $meta + ['quote_id' => $quote->id]);

        return redirect()->route('admin.sales.quotes.index')->with('success', 'Quote deleted.');
    }

    public function send(SalesQuote $quote)
    {
        return $this->changeStatus($quote, 'draft', 'sent', 'sent_at', 'sent_by', 'Sales quote sent to customer');
    }

    public function win(SalesQuote $quote)
    {
        return $this->changeStatus($quote, 'sent', 'won', 'won_at', 'won_by', 'Sales quote marked as won');
    }

    public function reject(SalesQuote $quote)
    {
        return $this->changeStatus($quote, 'sent', 'rejected', 'rejected_at', 'rejected_by', 'Sales quote rejected');
    }

    public function expire(SalesQuote $quote)
    {
        $this->authorizeCompany($quote);

        if ($quote->status !== 'sent') {
            return response()->json(['message' => 'Only sent quotes can be marked as expired.'], 422);
        }

        $quote->update(['status' => 'expired', 'expired_at' => now(), 'updated_by' => auth()->id()]);

        $this->audit('expire', 'Sales quote expired', $quote, ['quote_no' => $quote->quote_no]);

        return response()->json(['message' => 'Quote marked as expired.']);
    }

    /**
     * Not a status transition — an edit action available only while the quote is
     * 'won', repeatable, before someone with the convert permission clicks the
     * single Convert button. Status stays 'won'.
     */
    public function review(Request $request, SalesQuote $quote)
    {
        $this->authorizeCompany($quote);

        if ($quote->status !== 'won') {
            return response()->json(['message' => 'Only won quotes can be reviewed.'], 422);
        }

        $request->validate(['review_comments' => ['nullable', 'string']]);

        $quote->update([
            'reviewed_at'     => now(),
            'reviewed_by'     => auth()->id(),
            'review_comments' => $request->input('review_comments'),
            'updated_by'      => auth()->id(),
        ]);

        $this->audit('review', 'Sales quote reviewed', $quote, ['quote_no' => $quote->quote_no]);

        return response()->json(['message' => 'Review saved.']);
    }

    public function convert(SalesQuote $quote)
    {
        $this->authorizeCompany($quote);

        if ($quote->status !== 'won') {
            return response()->json(['message' => 'Only won quotes can be converted to a sales order.'], 422);
        }

        $quote->load('lines');

        $order = DB::transaction(function () use ($quote) {
            $hdr = SalesOrder::create([
                'order_no'      => $this->nextOrderNumberForConversion(),
                'customer_id'   => $quote->customer_id,
                'order_date'    => now()->toDateString(),
                'currency_code' => $quote->currency_code,
                'status'        => 'draft',
                'reference'     => $quote->quote_no,
                'remarks'       => $quote->notes,
            ]);

            $lines = $quote->lines->map(fn ($l) => [
                'product_variant_id' => $l->product_variant_id,
                'description'        => $l->description,
                'qty_ordered'        => (float) $l->qty,
                'unit_price'         => (float) $l->unit_price,
            ])->all();

            $hdr->lines()->createMany($lines);

            $subtotal = $hdr->lines->sum(fn ($l) => ((float) $l->qty_ordered) * ((float) $l->unit_price));
            $hdr->update(['subtotal' => $subtotal, 'tax_total' => 0, 'grand_total' => $subtotal]);

            $quote->update([
                'status'        => 'converted',
                'converted_at'  => now(),
                'converted_by'  => auth()->id(),
                'sales_order_id' => $hdr->id,
                'updated_by'    => auth()->id(),
            ]);

            return $hdr->fresh('lines');
        });

        $this->audit('convert', 'Sales quote converted to sales order', $quote, [
            'quote_no' => $quote->quote_no,
            'sales_order_id' => $order->id,
            'order_no' => $order->order_no,
        ]);

        return response()->json([
            'message' => 'Converted to Sales Order.',
            'redirect' => route('admin.sales.orders.show', $order->id),
        ]);
    }

    public function select2(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $q = $request->get('q', '');

        $quotes = SalesQuote::query()
            ->where('company_id', $companyId)
            ->when($q, fn ($qry) => $qry->where('quote_no', 'like', "%{$q}%"))
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'quote_no', 'status']);

        return $quotes->map(fn ($o) => [
            'id' => $o->id,
            'text' => $o->quote_no.' ('.$o->status.')',
        ]);
    }

    public function pdf(SalesQuote $quote)
    {
        $this->authorizeCompany($quote);
        $quote->load(['customer', 'lines.variant.product']);

        $pdf = Pdf::loadView('sales.quotes.pdf', ['quote' => $quote])->setPaper('a4', 'portrait');

        $this->audit('download_pdf', 'Sales quote PDF downloaded', $quote, ['quote_no' => $quote->quote_no]);

        return $pdf->download(($quote->quote_no ?: 'sales-quote-'.$quote->id).'.pdf');
    }

    /** ---------------- Helpers ---------------- */

    protected function authorizeCompany(SalesQuote $quote): void
    {
        $companyId = auth()->user()->company_id ?? 1;
        abort_unless((int) $quote->company_id === (int) $companyId, 404);
    }

    protected function changeStatus(SalesQuote $quote, string $from, string $to, string $stampField, string $userField, string $desc)
    {
        $this->authorizeCompany($quote);

        if ($quote->status !== $from) {
            return response()->json(['message' => "Only {$from} quotes can be moved to {$to}."], 422);
        }

        $quote->update([
            'status'     => $to,
            $stampField  => now(),
            $userField   => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $this->audit($to, $desc, $quote, ['quote_no' => $quote->quote_no]);

        return response()->json(['message' => ucfirst($to).' successfully.']);
    }

    protected function computeTotals(SalesQuote $quote): array
    {
        $subtotal = $quote->lines->sum(fn ($l) => ((float) $l->qty) * ((float) $l->unit_price));
        $discountTotal = $quote->lines->sum(fn ($l) => (float) $l->discount_amount);
        $taxTotal = $quote->lines->sum(fn ($l) => (float) $l->tax_amount);
        $total = $quote->lines->sum(fn ($l) => (float) $l->line_total);

        return [
            'subtotal'       => round($subtotal, 2),
            'discount_total' => round($discountTotal, 2),
            'tax_total'      => round($taxTotal, 2),
            'total_amount'   => round($total, 2),
        ];
    }

    protected function validateQuote(Request $request): array
    {
        $v = $request->validate([
            'customer_id' => ['required', 'integer'],
            'quote_date'  => ['required', 'date'],
            'valid_until' => ['nullable', 'date'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'reference'   => ['nullable', 'string', 'max:100'],
            'notes'       => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_variant_id' => ['required', 'integer'],
            'lines.*.description'        => ['nullable', 'string', 'max:255'],
            'lines.*.qty'                 => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price'          => ['required', 'numeric', 'min:0'],
            'lines.*.discount_percent'    => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_rate'            => ['nullable', 'numeric', 'min:0'],
        ]);

        $header = [
            'customer_id'   => (int) $v['customer_id'],
            'quote_date'    => $v['quote_date'],
            'valid_until'   => $v['valid_until'] ?? null,
            'currency_code' => ! empty($v['currency_code']) ? strtoupper(trim($v['currency_code'])) : 'USD',
            'reference'     => $v['reference'] ?? null,
            'notes'         => $v['notes'] ?? null,
        ];

        $lines = [];
        foreach ($v['lines'] as $ln) {
            $qty = (float) $ln['qty'];
            $unitPrice = (float) $ln['unit_price'];
            $discountPercent = ! empty($ln['discount_percent']) ? (float) $ln['discount_percent'] : 0.0;
            $taxRate = ! empty($ln['tax_rate']) ? (float) $ln['tax_rate'] : 0.0;

            $gross = $qty * $unitPrice;
            $discountAmount = $discountPercent > 0 ? round($gross * $discountPercent / 100, 2) : 0.0;
            $taxBase = $gross - $discountAmount;
            $taxAmount = $taxRate > 0 ? round($taxBase * $taxRate / 100, 2) : 0.0;
            $lineTotal = round($taxBase + $taxAmount, 2);

            $lines[] = [
                'product_variant_id' => (int) $ln['product_variant_id'],
                'description'        => $ln['description'] ?? null,
                'qty'                => $qty,
                'unit_price'         => $unitPrice,
                'discount_percent'   => $discountPercent,
                'discount_amount'    => $discountAmount,
                'tax_rate'           => $taxRate,
                'tax_amount'         => $taxAmount,
                'line_total'         => $lineTotal,
            ];
        }

        return compact('header', 'lines');
    }

    protected function nextQuoteNo(int $companyId): string
    {
        $date = now()->format('Ymd');
        $count = SalesQuote::where('company_id', $companyId)
            ->whereDate('created_at', now()->toDateString())
            ->count() + 1;

        return 'QT-'.$date.'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    protected function nextOrderNumberForConversion(): string
    {
        $date = now()->format('Ymd');
        $count = SalesOrder::whereDate('created_at', now()->toDateString())->count() + 1;

        return 'SO-'.$date.'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    protected function audit(string $action, ?string $description, $subject = null, array $meta = []): void
    {
        AuditLog::create([
            'user_id'      => auth()->id(),
            'module'       => 'sales.quotes',
            'action'       => $action,
            'description'  => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->id,
            'route'        => request()->route()?->getName(),
            'url'          => request()->fullUrl(),
            'method'       => request()->method(),
            'ip'           => request()->ip(),
            'user_agent'   => request()->userAgent(),
            'meta'         => $meta,
        ]);
    }
}
