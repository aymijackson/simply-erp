<?php

namespace Modules\Sales\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesAnalyticsController extends Controller
{
    public function index()
    {
        return view('sales.analytics.index');
    }

    private function filters(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : now()->startOfMonth();
        $to   = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : now()->endOfDay();

        $customerId = (int)($request->customer_id ?? 0);
        $currency   = trim((string)($request->currency_code ?? ''));
        $statusMode = trim((string)($request->status_mode ?? 'posted')); // posted | all

        return compact('from','to','customerId','currency','statusMode');
    }

    private function applyCommon($q, array $f, string $dateColumn, string $alias = '')
    {
        $col = $alias ? ($alias.'.'.$dateColumn) : $dateColumn;
        $q->whereBetween($col, [$f['from']->toDateString(), $f['to']->toDateString()]);
    
        if ($f['customerId'] > 0) {
            $q->where(($alias ? $alias.'.customer_id' : 'customer_id'), $f['customerId']);
        }
    
        if ($f['currency'] !== '') {
            $q->where(($alias ? $alias.'.currency_code' : 'currency_code'), $f['currency']);
        }
    
        if ($f['statusMode'] === 'posted') {
            $q->where(($alias ? $alias.'.status' : 'status'), 'posted');
        }
    
        return $q;
    }


    public function summary(Request $request)
    {
        $f = $this->filters($request);

        // Posted invoices
        $invQ = DB::table('v_sales_invoice_summary');
        $this->applyCommon($invQ, $f, 'invoice_date');

        $invoiced = (float)($invQ->sum('grand_total') ?? 0);

        // Payments received
        $payQ = DB::table('v_sales_payment_summary');
        $this->applyCommon($payQ, $f, 'payment_date');
        $payments = (float)($payQ->sum('amount_received') ?? 0);

        // Credit notes
        $cnQ = DB::table('v_sales_credit_note_summary');
        $this->applyCommon($cnQ, $f, 'credit_note_date');
        $credits = (float)($cnQ->sum('total_amount') ?? 0);

        // Outstanding AR (use balances view; usually all dates, but we can respect filters on invoice_date)
        $arQ = DB::table('v_sales_invoice_summary');
        // AR is about unpaid invoices; still respect customer/currency/status filter; date filter optional but ok.
        $this->applyCommon($arQ, $f, 'invoice_date');
        $outstanding = (float)($arQ->sum('balance_due') ?? 0);

        $overdueQ = DB::table('v_sales_invoice_summary');
        $this->applyCommon($overdueQ, $f, 'invoice_date');
        $overdueQ->whereNotNull('due_date')
                 ->where('due_date', '<', now()->toDateString())
                 ->where('balance_due', '>', 0);
        $overdue = (float)($overdueQ->sum('balance_due') ?? 0);

        // Unallocated payments
        $unallocQ = DB::table('v_sales_payment_summary');
        $this->applyCommon($unallocQ, $f, 'payment_date');
        $unallocatedPayments = (float)($unallocQ->sum('unallocated_total') ?? 0);

        return response()->json([
            'range' => [
                'from' => $f['from']->toDateString(),
                'to'   => $f['to']->toDateString(),
            ],
            'kpis' => [
                'invoiced' => $invoiced,
                'payments' => $payments,
                'credits'  => $credits,
                'net_sales'=> $invoiced - $credits,
                'outstanding_ar' => $outstanding,
                'overdue_ar'     => $overdue,
                'unallocated_payments' => $unallocatedPayments,
            ]
        ]);
    }

    public function trends(Request $request)
    {
        $f = $this->filters($request);
        $group = $request->get('group', 'day'); // day|month
        $dateExpr = $group === 'month'
            ? "DATE_FORMAT(invoice_date, '%Y-%m-01')"
            : "DATE(invoice_date)";

        $invQ = DB::table('v_sales_invoice_summary')
            ->selectRaw("$dateExpr AS d, SUM(grand_total) AS total")
            ->groupBy('d')
            ->orderBy('d');
        $this->applyCommon($invQ, $f, 'invoice_date');
        $inv = $invQ->get();

        $payDateExpr = $group === 'month'
            ? "DATE_FORMAT(payment_date, '%Y-%m-01')"
            : "DATE(payment_date)";

        $payQ = DB::table('v_sales_payment_summary')
            ->selectRaw("$payDateExpr AS d, SUM(amount_received) AS total")
            ->groupBy('d')
            ->orderBy('d');
        $this->applyCommon($payQ, $f, 'payment_date');
        $pay = $payQ->get();

        $cnDateExpr = $group === 'month'
            ? "DATE_FORMAT(credit_note_date, '%Y-%m-01')"
            : "DATE(credit_note_date)";

        $cnQ = DB::table('v_sales_credit_note_summary')
            ->selectRaw("$cnDateExpr AS d, SUM(total_amount) AS total")
            ->groupBy('d')
            ->orderBy('d');
        $this->applyCommon($cnQ, $f, 'credit_note_date');
        $cn = $cnQ->get();

        return response()->json([
            'group' => $group,
            'series' => [
                'invoices' => $inv,
                'payments' => $pay,
                'credit_notes' => $cn,
            ]
        ]);
    }

    public function arAging(Request $request)
    {
        // AR Aging ignores date range typically; but respects customer/currency/status_mode
        $f = $this->filters($request);

        $q = DB::table('v_sales_invoice_summary')
            ->where('balance_due', '>', 0);

        if ($f['customerId'] > 0) $q->where('customer_id', $f['customerId']);
        if ($f['currency'] !== '') $q->where('currency_code', $f['currency']);
        if ($f['statusMode'] === 'posted') $q->where('status', 'posted');

        $today = now()->toDateString();

        $rows = $q->selectRaw("
            SUM(CASE WHEN DATEDIFF(?, due_date) BETWEEN 0 AND 30 THEN balance_due ELSE 0 END) AS b0_30,
            SUM(CASE WHEN DATEDIFF(?, due_date) BETWEEN 31 AND 60 THEN balance_due ELSE 0 END) AS b31_60,
            SUM(CASE WHEN DATEDIFF(?, due_date) BETWEEN 61 AND 90 THEN balance_due ELSE 0 END) AS b61_90,
            SUM(CASE WHEN DATEDIFF(?, due_date) >= 91 THEN balance_due ELSE 0 END) AS b91_plus
        ", [$today,$today,$today,$today])->first();

        return response()->json([
            'aging' => [
                '0_30'   => (float)($rows->b0_30 ?? 0),
                '31_60'  => (float)($rows->b31_60 ?? 0),
                '61_90'  => (float)($rows->b61_90 ?? 0),
                '91_plus'=> (float)($rows->b91_plus ?? 0),
            ]
        ]);
    }

    public function topCustomers(Request $request)
    {
        $f = $this->filters($request);
    
        $q = DB::table('v_sales_invoice_summary as i')
            ->join('customers as c', 'c.id', '=', 'i.customer_id')
            ->selectRaw("i.customer_id, c.name as customer_name, SUM(i.grand_total) as total_invoiced, SUM(i.balance_due) as total_outstanding")
            ->groupBy('i.customer_id','c.name')
            ->orderByDesc('total_invoiced')
            ->limit(10);
    
        $this->applyCommon($q, $f, 'invoice_date', 'i');
    
        return response()->json(['rows' => $q->get()]);
    }

    public function paymentAllocation(Request $request)
    {
        $f = $this->filters($request);

        $q = DB::table('v_sales_payment_summary');
        $this->applyCommon($q, $f, 'payment_date');

        $allocated = (float)($q->sum('allocated_total') ?? 0);

        $q2 = DB::table('v_sales_payment_summary');
        $this->applyCommon($q2, $f, 'payment_date');
        $unallocated = (float)($q2->sum('unallocated_total') ?? 0);

        return response()->json([
            'allocated' => $allocated,
            'unallocated' => $unallocated
        ]);
    }

    public function creditNotes(Request $request)
    {
        $f = $this->filters($request);

        $q = DB::table('v_sales_credit_note_summary');
        $this->applyCommon($q, $f, 'credit_note_date');

        $total = (float)($q->sum('total_amount') ?? 0);

        return response()->json([
            'total' => $total
        ]);
    }
}
