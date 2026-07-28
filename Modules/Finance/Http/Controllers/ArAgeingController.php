<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArAgeingController extends BaseController
{
    public function __construct()
    {
        $this->middleware('permission:finance.reports.ar_ageing.view');
    }

    public function index()
    {
        return view('finance.ar_ageing.index');
    }

    public function datatable(Request $request)
{
    $companyId = auth()->user()->company_id ?? 1;

    $asOf = $request->filled('as_of') ? $request->as_of : date('Y-m-d');
    $currency = $request->filled('currency_code') ? $request->currency_code : null;
    $customerId = (int)($request->customer_id ?? 0);

    // Base query: open invoices with balance_due > 0
    $base = DB::table('v_ar_open_invoices as v')
        ->join('customers as c', 'c.id', '=', 'v.customer_id')
        ->where('v.balance_due', '>', 0)
        ->where('c.company_id', $companyId)
        ->select([
            'v.invoice_id',
            'v.customer_id',
            'v.customer_name',
            'v.currency_code',
            'v.due_date',
            'v.balance_due',
        ]);

    if ($customerId > 0) {
        $base->where('v.customer_id', $customerId);
    }

    if (!empty($currency)) {
        $base->where('v.currency_code', $currency);
    }

    // Age buckets
    $q = DB::query()->fromSub($base, 'x')
        ->selectRaw('
            x.customer_id,
            x.customer_name,
            x.currency_code,

            SUM(CASE WHEN x.due_date IS NULL OR x.due_date > ? THEN x.balance_due ELSE 0 END) AS not_due,
            SUM(CASE WHEN x.due_date IS NOT NULL AND DATEDIFF(?, x.due_date) BETWEEN 0 AND 30 THEN x.balance_due ELSE 0 END) AS b0_30,
            SUM(CASE WHEN x.due_date IS NOT NULL AND DATEDIFF(?, x.due_date) BETWEEN 31 AND 60 THEN x.balance_due ELSE 0 END) AS b31_60,
            SUM(CASE WHEN x.due_date IS NOT NULL AND DATEDIFF(?, x.due_date) BETWEEN 61 AND 90 THEN x.balance_due ELSE 0 END) AS b61_90,
            SUM(CASE WHEN x.due_date IS NOT NULL AND DATEDIFF(?, x.due_date) >= 91 THEN x.balance_due ELSE 0 END) AS b91_plus,

            SUM(x.balance_due) AS total_due
        ', [$asOf, $asOf, $asOf, $asOf, $asOf])
        ->groupBy('x.customer_id', 'x.customer_name', 'x.currency_code');

    // DataTables paging
    $start  = (int)($request->start ?? 0);
    $length = (int)($request->length ?? 10);
    $draw   = (int)($request->draw ?? 1);

    // Count BEFORE pagination
    $recordsTotal = (clone $q)->count();

    // Fetch paginated rows
    $rows = $q->orderByDesc('total_due')
              ->offset($start)
              ->limit($length)
              ->get();

    // Format rows for DataTables
    $data = $rows->map(function ($r) {
        $json = [
            'customer_id'   => $r->customer_id,
            'customer_name' => $r->customer_name,
            'currency_code' => $r->currency_code,
        ];

        $jsonAttr = htmlspecialchars(json_encode($json), ENT_QUOTES, 'UTF-8');

        $btn = '<button type="button" class="btn btn-outline-primary btn-sm btn-drill" data-json="'.$jsonAttr.'">
                    <i class="fas fa-search"></i> View Invoices
                </button>';

        return [
            'customer'  => e($r->customer_name),
            'currency'  => e($r->currency_code ?? ''),
            'not_due'   => number_format((float)$r->not_due, 2),
            'b0_30'     => number_format((float)$r->b0_30, 2),
            'b31_60'    => number_format((float)$r->b31_60, 2),
            'b61_90'    => number_format((float)$r->b61_90, 2),
            'b91_plus'  => number_format((float)$r->b91_plus, 2),
            'total'     => number_format((float)$r->total_due, 2),
            'actions'   => $btn,
        ];
    })->values();

    return response()->json([
        'draw'            => $draw,
        'recordsTotal'    => $recordsTotal,
        'recordsFiltered' => $recordsTotal,
        'data'            => $data,
    ]);
}

    public function customerInvoices(Request $request, $customerId)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $asOf = $request->filled('as_of') ? $request->as_of : date('Y-m-d');
        $currency = $request->filled('currency_code') ? $request->currency_code : null;

        $q = DB::table('v_ar_open_invoices as v')
            ->join('sales_invoices as i', 'i.id', '=', 'v.invoice_id')
            ->where('i.company_id', $companyId)
            ->where('v.customer_id', (int)$customerId)
            ->where('v.balance_due', '>', 0);

        if (!empty($currency)) $q->where('v.currency_code', $currency);

        $rows = $q->orderByRaw('COALESCE(v.due_date, v.invoice_date) asc')
            ->get([
                'v.invoice_id',
                'v.invoice_no',
                'v.invoice_date',
                'v.due_date',
                'v.currency_code',
                'v.grand_total',
                'v.total_paid',
                'v.balance_due',
            ])
            ->map(function ($r) use ($asOf) {
                $days = null;
                if (!empty($r->due_date)) {
                    $days = (int)DB::selectOne("SELECT DATEDIFF(?, ?) AS d", [$asOf, $r->due_date])->d;
                }
                return [
                    'invoice_id' => $r->invoice_id,
                    'invoice_no' => $r->invoice_no ?? ('INV-'.$r->invoice_id),
                    'invoice_date' => $r->invoice_date,
                    'due_date' => $r->due_date,
                    'days_past_due' => $days,
                    'currency_code' => $r->currency_code,
                    'grand_total' => (float)$r->grand_total,
                    'paid' => (float)$r->total_paid,
                    'balance_due' => (float)$r->balance_due,
                ];
            });

        return response()->json(['invoices' => $rows]);
    }

    // Select2 customers lookup (name only)
    public function customers(Request $request)
    {
        $q = trim((string)$request->q);

        $rows = DB::table('customers')
            ->when($q !== '', fn($x) => $x->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(30)
            ->get(['id', DB::raw("name as text")]);

        return response()->json(['results' => $rows]);
    }
}