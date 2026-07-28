<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerStatementController extends Controller
{
    public function index(Request $request)
    {
        $initialCustomer = null;

        if ($request->filled('customer_id')) {
            $initialCustomer = DB::table('customers')
                ->where('id', (int) $request->customer_id)
                ->select('id', DB::raw('name as text'))
                ->first();
        }

        return view('finance.customer_statements.index', [
            'initialCustomer' => $initialCustomer,
        ]);
    }

    public function customers(Request $request)
    {
        $q = trim((string)$request->q);

        // Your customers table likely exists. If it is companies/customers adjust here.
        $rows = DB::table('customers')
            ->when($q !== '', fn($x) => $x->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(30)
            ->get([
                'id',
                DB::raw("name as text")
            ]);

        return response()->json(['results' => $rows]);
    }

    public function summary(Request $request)
    {
        $data = $this->validateFilters($request);

        $customerId = (int)$data['customer_id'];
        $from = $data['date_from'];
        $to = $data['date_to'];

        $opening = $this->openingBalance($customerId, $from);
        $period = $this->periodTotals($customerId, $from, $to);

        return response()->json([
            'opening_balance' => round($opening, 2),
            'charges' => round($period['charges'], 2),
            'credits' => round($period['credits'], 2),
            'closing_balance' => round($opening + $period['charges'] - $period['credits'], 2),
        ]);
    }

    public function rows(Request $request)
    {
        $data = $this->validateFilters($request);

        $customerId = (int)$data['customer_id'];
        $from = $data['date_from'];
        $to = $data['date_to'];

        $rows = $this->statementRows($customerId, $from, $to);

        // Running balance (client-side can do it too, but we’ll do it here)
        $bal = $this->openingBalance($customerId, $from);
        $out = [];

        foreach ($rows as $r) {
            $bal += (float)$r->debit;
            $bal -= (float)$r->credit;

            $out[] = [
                'date' => $r->txn_date,
                'type' => $r->txn_type,
                'ref'  => $r->ref,
                'description' => $r->description,
                'debit' => (float)$r->debit,
                'credit' => (float)$r->credit,
                'balance' => round($bal, 2),
            ];
        }

        return response()->json([
            'opening_balance' => round($this->openingBalance($customerId, $from), 2),
            'rows' => $out,
        ]);
    }

    /** ================== Helpers ================== */

    private function validateFilters(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['required','integer'],
            'date_from' => ['required','date'],
            'date_to' => ['required','date'],
        ]);
    }

    private function openingBalance(int $customerId, string $dateFrom): float
    {
        // Opening = all charges - all credits before dateFrom
        $charges = (float) DB::table('sales_invoices')
            ->where('customer_id', $customerId)
            ->whereDate('invoice_date', '<', $dateFrom)
            ->whereIn('status', ['posted','part_paid','paid']) // adjust if your statuses differ
            ->sum('grand_total');

        $credits = (float) DB::table('sales_payment_allocations as a')
            ->join('sales_payments as p', 'p.id', '=', 'a.sales_payment_id')
            ->join('sales_invoices as i', 'i.id', '=', 'a.sales_invoice_id')
            ->where('p.customer_id', $customerId)
            ->whereDate('p.payment_date', '<', $dateFrom)
            ->whereIn('p.status', ['posted'])
            ->sum('a.amount_applied');

        // Optional: include credit notes if you have the header table ready.
        // If you don't, this will safely do nothing (because table might not exist).
        $creditNotes = 0.0;
        if ($this->tableExists('sales_credit_notes')) {
            $creditNotes = (float) DB::table('sales_credit_notes')
                ->where('customer_id', $customerId)
                ->whereDate('credit_note_date', '<', $dateFrom)
                ->whereIn('status', ['posted'])
                ->sum('grand_total');
        }

        return ($charges - $credits - $creditNotes);
    }

    private function periodTotals(int $customerId, string $from, string $to): array
    {
        $charges = (float) DB::table('sales_invoices')
            ->where('customer_id', $customerId)
            ->whereBetween('invoice_date', [$from, $to])
            ->whereIn('status', ['posted','part_paid','paid'])
            ->sum('grand_total');

        $credits = (float) DB::table('sales_payment_allocations as a')
            ->join('sales_payments as p', 'p.id', '=', 'a.sales_payment_id')
            ->join('sales_invoices as i', 'i.id', '=', 'a.sales_invoice_id')
            ->where('p.customer_id', $customerId)
            ->whereBetween('p.payment_date', [$from, $to])
            ->whereIn('p.status', ['posted'])
            ->sum('a.amount_applied');

        $creditNotes = 0.0;
        if ($this->tableExists('sales_credit_notes')) {
            $creditNotes = (float) DB::table('sales_credit_notes')
                ->where('customer_id', $customerId)
                ->whereBetween('credit_note_date', [$from, $to])
                ->whereIn('status', ['posted'])
                ->sum('grand_total');
        }

        return [
            'charges' => $charges,
            'credits' => ($credits + $creditNotes),
        ];
    }

    private function statementRows(int $customerId, string $from, string $to)
    {
        // 1) Invoices (debit)
        $inv = DB::table('sales_invoices as i')
            ->where('i.customer_id', $customerId)
            ->whereBetween('i.invoice_date', [$from, $to])
            ->whereIn('i.status', ['posted','part_paid','paid'])
            ->selectRaw("
                i.invoice_date as txn_date,
                'Invoice' as txn_type,
                COALESCE(i.invoice_no, CONCAT('INV-', i.id)) as ref,
                COALESCE(i.reference,'') as description,
                i.grand_total as debit,
                0 as credit
            ");

        // 2) Payments allocated (credit) — show per invoice allocation
        $pay = DB::table('sales_payment_allocations as a')
            ->join('sales_payments as p', 'p.id', '=', 'a.sales_payment_id')
            ->join('sales_invoices as i', 'i.id', '=', 'a.sales_invoice_id')
            ->where('p.customer_id', $customerId)
            ->whereBetween('p.payment_date', [$from, $to])
            ->whereIn('p.status', ['posted'])
            ->selectRaw("
                p.payment_date as txn_date,
                'Payment' as txn_type,
                COALESCE(p.payment_no, CONCAT('RCPT-', p.id)) as ref,
                CONCAT('Applied to ', COALESCE(i.invoice_no, CONCAT('INV-', i.id))) as description,
                0 as debit,
                a.amount_applied as credit
            ");

        // 3) Optional credit notes as credit
        $cr = null;
        if ($this->tableExists('sales_credit_notes')) {
            $cr = DB::table('sales_credit_notes as c')
                ->where('c.customer_id', $customerId)
                ->whereBetween('c.credit_note_date', [$from, $to])
                ->whereIn('c.status', ['posted'])
                ->selectRaw("
                    c.credit_note_date as txn_date,
                    'Credit Note' as txn_type,
                    COALESCE(c.credit_note_no, CONCAT('CN-', c.id)) as ref,
                    COALESCE(c.remarks,'') as description,
                    0 as debit,
                    c.grand_total as credit
                ");
        }

        // union
        $q = $inv->unionAll($pay);
        if ($cr) $q = $q->unionAll($cr);

        return DB::query()->fromSub($q, 'x')
            ->orderBy('txn_date')
            ->orderBy('txn_type')
            ->get();
    }

    private function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }
}