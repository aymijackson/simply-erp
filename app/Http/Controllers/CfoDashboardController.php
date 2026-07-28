<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CfoDashboardController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id ?? 1;

        $kpis = [
            'cash_balance'             => $this->getCashBalance($companyId),
            'accounts_receivable'      => $this->getAccountsReceivable($companyId),
            'accounts_payable'         => $this->getAccountsPayable($companyId),
            'revenue_month'            => $this->getRevenueThisMonth($companyId),
            'expenses_month'           => $this->getExpensesThisMonth($companyId),
            'net_profit_month'         => $this->getRevenueThisMonth($companyId) - $this->getExpensesThisMonth($companyId),
            'overdue_receivables'      => $this->getOverdueReceivablesAmount($companyId),
            'overdue_payables'         => $this->getOverduePayablesAmount($companyId),
            'posted_supplier_bills'    => $this->getPostedSupplierBillsCount($companyId),
            'posted_sales_invoices'    => $this->getPostedSalesInvoicesCount($companyId),
            'project_invoice_total'    => $this->getProjectInvoiceTotalThisMonth($companyId),
            'supplier_credit_total'    => $this->getSupplierCreditTotal($companyId),
        ];

        $profitTrend = $this->getProfitTrend($companyId);
        $bankBreakdown = $this->getBankBreakdown($companyId);
        $arApTrend = $this->getArApTrend($companyId);

        $riskItems = [
            'overdue_customer_invoices' => $this->getOverdueCustomerInvoices($companyId),
            'overdue_supplier_bills'    => $this->getOverdueSupplierBills($companyId),
            'largest_unpaid_invoices'   => $this->getLargestUnpaidInvoices($companyId),
            'largest_unpaid_bills'      => $this->getLargestUnpaidBills($companyId),
        ];

        $recentActivities = [
            'sales_invoices'   => $this->getRecentSalesInvoices($companyId),
            'supplier_bills'   => $this->getRecentSupplierBills($companyId),
            'supplier_credits' => $this->getRecentSupplierCredits($companyId),
            'journal_entries'  => $this->getRecentJournalEntries($companyId),
            'project_invoices' => $this->getRecentProjectInvoices($companyId),
        ];

        return view('dashboard.cfo', compact(
            'kpis',
            'profitTrend',
            'bankBreakdown',
            'arApTrend',
            'riskItems',
            'recentActivities'
        ));
    }

    protected function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function hasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasTable($table) && Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function getCashBalance(int $companyId): float
    {
        if (!$this->hasTable('finance_bank_accounts')) return 0;

        $q = DB::table('finance_bank_accounts');

        if ($this->hasColumn('finance_bank_accounts', 'company_id')) {
            $q->where('company_id', $companyId);
        }

        if ($this->hasColumn('finance_bank_accounts', 'current_balance')) {
            return round((float) $q->sum('current_balance'), 2);
        }

        if ($this->hasColumn('finance_bank_accounts', 'balance')) {
            return round((float) $q->sum('balance'), 2);
        }

        return 0;
    }

    protected function getAccountsReceivable(int $companyId): float
    {
        if (!$this->hasTable('sales_invoices')) return 0;

        $q = DB::table('sales_invoices');

        if ($this->hasColumn('sales_invoices', 'company_id')) {
            $q->where('company_id', $companyId);
        }
        if ($this->hasColumn('sales_invoices', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }
        if ($this->hasColumn('sales_invoices', 'status')) {
            $q->whereIn('status', ['posted', 'part_paid']);
        }
        if ($this->hasColumn('sales_invoices', 'balance_due')) {
            return round((float) $q->sum('balance_due'), 2);
        }
        if ($this->hasColumn('sales_invoices', 'balance')) {
            return round((float) $q->sum('balance'), 2);
        }

        return 0;
    }

    protected function getAccountsPayable(int $companyId): float
    {
        if (!$this->hasTable('finance_supplier_bills')) return 0;

        $q = DB::table('finance_supplier_bills');

        if ($this->hasColumn('finance_supplier_bills', 'company_id')) {
            $q->where('company_id', $companyId);
        }
        if ($this->hasColumn('finance_supplier_bills', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }
        if ($this->hasColumn('finance_supplier_bills', 'status')) {
            $q->whereIn('status', ['posted', 'part_paid']);
        }
        if ($this->hasColumn('finance_supplier_bills', 'balance_due')) {
            return round((float) $q->sum('balance_due'), 2);
        }

        return 0;
    }

    protected function getRevenueThisMonth(int $companyId): float
    {
        if (!$this->hasTable('sales_invoices')) return 0;

        $q = DB::table('sales_invoices');

        if ($this->hasColumn('sales_invoices', 'company_id')) {
            $q->where('company_id', $companyId);
        }
        if ($this->hasColumn('sales_invoices', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }
        if ($this->hasColumn('sales_invoices', 'status')) {
            $q->whereIn('status', ['posted', 'part_paid', 'paid']);
        }
        if ($this->hasColumn('sales_invoices', 'invoice_date')) {
            $q->whereMonth('invoice_date', now()->month)
              ->whereYear('invoice_date', now()->year);
        }

        if ($this->hasColumn('sales_invoices', 'total_amount')) {
            return round((float) $q->sum('total_amount'), 2);
        }
        if ($this->hasColumn('sales_invoices', 'total')) {
            return round((float) $q->sum('total'), 2);
        }

        return 0;
    }

    protected function getExpensesThisMonth(int $companyId): float
    {
        if ($this->hasTable('expenses')) {
            $q = DB::table('expenses');

            if ($this->hasColumn('expenses', 'company_id')) {
                $q->where('company_id', $companyId);
            }
            if ($this->hasColumn('expenses', 'deleted_at')) {
                $q->whereNull('deleted_at');
            }
            if ($this->hasColumn('expenses', 'expense_date')) {
                $q->whereMonth('expense_date', now()->month)
                  ->whereYear('expense_date', now()->year);
            }
            if ($this->hasColumn('expenses', 'amount')) {
                return round((float) $q->sum('amount'), 2);
            }
        }

        return 0;
    }

    protected function getOverdueReceivablesAmount(int $companyId): float
    {
        if (!$this->hasTable('sales_invoices') || !$this->hasColumn('sales_invoices', 'due_date')) return 0;

        $q = DB::table('sales_invoices');

        if ($this->hasColumn('sales_invoices', 'company_id')) {
            $q->where('company_id', $companyId);
        }
        if ($this->hasColumn('sales_invoices', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }
        if ($this->hasColumn('sales_invoices', 'status')) {
            $q->whereIn('status', ['posted', 'part_paid']);
        }

        $q->whereDate('due_date', '<', now()->toDateString());

        if ($this->hasColumn('sales_invoices', 'balance_due')) {
            return round((float) $q->sum('balance_due'), 2);
        }
        if ($this->hasColumn('sales_invoices', 'balance')) {
            return round((float) $q->sum('balance'), 2);
        }

        return 0;
    }

    protected function getOverduePayablesAmount(int $companyId): float
    {
        if (!$this->hasTable('finance_supplier_bills') || !$this->hasColumn('finance_supplier_bills', 'due_date')) return 0;

        $q = DB::table('finance_supplier_bills');

        if ($this->hasColumn('finance_supplier_bills', 'company_id')) {
            $q->where('company_id', $companyId);
        }
        if ($this->hasColumn('finance_supplier_bills', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }
        if ($this->hasColumn('finance_supplier_bills', 'status')) {
            $q->whereIn('status', ['posted', 'part_paid']);
        }

        $q->whereDate('due_date', '<', now()->toDateString());

        if ($this->hasColumn('finance_supplier_bills', 'balance_due')) {
            return round((float) $q->sum('balance_due'), 2);
        }

        return 0;
    }

    protected function getPostedSupplierBillsCount(int $companyId): int
    {
        if (!$this->hasTable('finance_supplier_bills')) return 0;

        $q = DB::table('finance_supplier_bills');
        if ($this->hasColumn('finance_supplier_bills', 'company_id')) $q->where('company_id', $companyId);
        if ($this->hasColumn('finance_supplier_bills', 'deleted_at')) $q->whereNull('deleted_at');
        if ($this->hasColumn('finance_supplier_bills', 'status')) $q->whereIn('status', ['posted', 'part_paid', 'paid']);

        return (int) $q->count();
    }

    protected function getPostedSalesInvoicesCount(int $companyId): int
    {
        if (!$this->hasTable('sales_invoices')) return 0;

        $q = DB::table('sales_invoices');
        if ($this->hasColumn('sales_invoices', 'company_id')) $q->where('company_id', $companyId);
        if ($this->hasColumn('sales_invoices', 'deleted_at')) $q->whereNull('deleted_at');
        if ($this->hasColumn('sales_invoices', 'status')) $q->whereIn('status', ['posted', 'part_paid', 'paid']);

        return (int) $q->count();
    }

    protected function getProjectInvoiceTotalThisMonth(int $companyId): float
    {
        if (!$this->hasTable('project_invoices')) return 0;

        $q = DB::table('project_invoices');
        if ($this->hasColumn('project_invoices', 'company_id')) $q->where('company_id', $companyId);
        if ($this->hasColumn('project_invoices', 'deleted_at')) $q->whereNull('deleted_at');
        if ($this->hasColumn('project_invoices', 'status')) $q->whereIn('status', ['posted', 'part_paid', 'paid']);
        if ($this->hasColumn('project_invoices', 'invoice_date')) {
            $q->whereMonth('invoice_date', now()->month)->whereYear('invoice_date', now()->year);
        }
        if ($this->hasColumn('project_invoices', 'total_amount')) {
            return round((float) $q->sum('total_amount'), 2);
        }

        return 0;
    }

    protected function getSupplierCreditTotal(int $companyId): float
    {
        if (!$this->hasTable('finance_supplier_credits')) return 0;

        $q = DB::table('finance_supplier_credits');
        if ($this->hasColumn('finance_supplier_credits', 'company_id')) $q->where('company_id', $companyId);
        if ($this->hasColumn('finance_supplier_credits', 'deleted_at')) $q->whereNull('deleted_at');
        if ($this->hasColumn('finance_supplier_credits', 'status')) $q->whereIn('status', ['posted']);
        if ($this->hasColumn('finance_supplier_credits', 'unapplied_amount')) {
            return round((float) $q->sum('unapplied_amount'), 2);
        }
        if ($this->hasColumn('finance_supplier_credits', 'total_amount')) {
            return round((float) $q->sum('total_amount'), 2);
        }

        return 0;
    }

    protected function getProfitTrend(int $companyId): array
    {
        $labels = [];
        $revenue = [];
        $expenses = [];
        $profit = [];

        for ($i = 5; $i >= 0; $i--) {
            $d = now()->copy()->subMonths($i);
            $labels[] = $d->format('M Y');

            $rev = 0;
            $exp = 0;

            if ($this->hasTable('sales_invoices')) {
                $q = DB::table('sales_invoices');
                if ($this->hasColumn('sales_invoices', 'company_id')) $q->where('company_id', $companyId);
                if ($this->hasColumn('sales_invoices', 'deleted_at')) $q->whereNull('deleted_at');
                if ($this->hasColumn('sales_invoices', 'status')) $q->whereIn('status', ['posted', 'part_paid', 'paid']);
                if ($this->hasColumn('sales_invoices', 'invoice_date')) $q->whereMonth('invoice_date', $d->month)->whereYear('invoice_date', $d->year);
                if ($this->hasColumn('sales_invoices', 'total_amount')) $rev = (float) $q->sum('total_amount');
                elseif ($this->hasColumn('sales_invoices', 'total')) $rev = (float) $q->sum('total');
            }

            if ($this->hasTable('expenses')) {
                $q = DB::table('expenses');
                if ($this->hasColumn('expenses', 'company_id')) $q->where('company_id', $companyId);
                if ($this->hasColumn('expenses', 'deleted_at')) $q->whereNull('deleted_at');
                if ($this->hasColumn('expenses', 'expense_date')) $q->whereMonth('expense_date', $d->month)->whereYear('expense_date', $d->year);
                if ($this->hasColumn('expenses', 'amount')) $exp = (float) $q->sum('amount');
            }

            $revenue[] = round($rev, 2);
            $expenses[] = round($exp, 2);
            $profit[] = round($rev - $exp, 2);
        }

        return compact('labels', 'revenue', 'expenses', 'profit');
    }

    protected function getBankBreakdown(int $companyId): array
    {
        if (!$this->hasTable('finance_bank_accounts')) return [];

        $q = DB::table('finance_bank_accounts');
        if ($this->hasColumn('finance_bank_accounts', 'company_id')) $q->where('company_id', $companyId);

        $rows = $q->limit(10)->get();

        return $rows->map(function ($row) {
            return [
                'name' => $row->name ?? $row->account_name ?? ('Bank-'.$row->id),
                'balance' => (float) ($row->current_balance ?? $row->balance ?? 0),
            ];
        })->toArray();
    }

    protected function getArApTrend(int $companyId): array
    {
        $labels = [];
        $ar = [];
        $ap = [];

        for ($i = 5; $i >= 0; $i--) {
            $d = now()->copy()->subMonths($i);
            $labels[] = $d->format('M Y');

            $arVal = 0;
            if ($this->hasTable('sales_invoices')) {
                $q = DB::table('sales_invoices');
                if ($this->hasColumn('sales_invoices', 'company_id')) $q->where('company_id', $companyId);
                if ($this->hasColumn('sales_invoices', 'deleted_at')) $q->whereNull('deleted_at');
                if ($this->hasColumn('sales_invoices', 'status')) $q->whereIn('status', ['posted', 'part_paid']);
                if ($this->hasColumn('sales_invoices', 'invoice_date')) $q->whereMonth('invoice_date', $d->month)->whereYear('invoice_date', $d->year);
                if ($this->hasColumn('sales_invoices', 'balance_due')) $arVal = (float) $q->sum('balance_due');
                elseif ($this->hasColumn('sales_invoices', 'balance')) $arVal = (float) $q->sum('balance');
            }

            $apVal = 0;
            if ($this->hasTable('finance_supplier_bills')) {
                $q = DB::table('finance_supplier_bills');
                if ($this->hasColumn('finance_supplier_bills', 'company_id')) $q->where('company_id', $companyId);
                if ($this->hasColumn('finance_supplier_bills', 'deleted_at')) $q->whereNull('deleted_at');
                if ($this->hasColumn('finance_supplier_bills', 'status')) $q->whereIn('status', ['posted', 'part_paid']);
                if ($this->hasColumn('finance_supplier_bills', 'bill_date')) $q->whereMonth('bill_date', $d->month)->whereYear('bill_date', $d->year);
                if ($this->hasColumn('finance_supplier_bills', 'balance_due')) $apVal = (float) $q->sum('balance_due');
            }

            $ar[] = round($arVal, 2);
            $ap[] = round($apVal, 2);
        }

        return compact('labels', 'ar', 'ap');
    }

    protected function getOverdueCustomerInvoices(int $companyId)
    {
        if (!$this->hasTable('sales_invoices') || !$this->hasColumn('sales_invoices', 'due_date')) return collect();

        $q = DB::table('sales_invoices');
        if ($this->hasColumn('sales_invoices', 'company_id')) $q->where('company_id', $companyId);
        if ($this->hasColumn('sales_invoices', 'deleted_at')) $q->whereNull('deleted_at');
        if ($this->hasColumn('sales_invoices', 'status')) $q->whereIn('status', ['posted', 'part_paid']);

        return $q->whereDate('due_date', '<', now()->toDateString())
            ->orderBy('due_date')
            ->limit(5)
            ->get();
    }

    protected function getOverdueSupplierBills(int $companyId)
    {
        if (!$this->hasTable('finance_supplier_bills') || !$this->hasColumn('finance_supplier_bills', 'due_date')) return collect();

        $q = DB::table('finance_supplier_bills');
        if ($this->hasColumn('finance_supplier_bills', 'company_id')) $q->where('company_id', $companyId);
        if ($this->hasColumn('finance_supplier_bills', 'deleted_at')) $q->whereNull('deleted_at');
        if ($this->hasColumn('finance_supplier_bills', 'status')) $q->whereIn('status', ['posted', 'part_paid']);

        return $q->whereDate('due_date', '<', now()->toDateString())
            ->orderBy('due_date')
            ->limit(5)
            ->get();
    }

    protected function getLargestUnpaidInvoices(int $companyId)
    {
        if (!$this->hasTable('sales_invoices')) return collect();

        $q = DB::table('sales_invoices');
        if ($this->hasColumn('sales_invoices', 'company_id')) $q->where('company_id', $companyId);
        if ($this->hasColumn('sales_invoices', 'deleted_at')) $q->whereNull('deleted_at');
        if ($this->hasColumn('sales_invoices', 'status')) $q->whereIn('status', ['posted', 'part_paid']);

        if ($this->hasColumn('sales_invoices', 'balance_due')) {
            return $q->orderByDesc('balance_due')->limit(5)->get();
        }

        return collect();
    }

    protected function getLargestUnpaidBills(int $companyId)
    {
        if (!$this->hasTable('finance_supplier_bills')) return collect();

        $q = DB::table('finance_supplier_bills');
        if ($this->hasColumn('finance_supplier_bills', 'company_id')) $q->where('company_id', $companyId);
        if ($this->hasColumn('finance_supplier_bills', 'deleted_at')) $q->whereNull('deleted_at');
        if ($this->hasColumn('finance_supplier_bills', 'status')) $q->whereIn('status', ['posted', 'part_paid']);

        if ($this->hasColumn('finance_supplier_bills', 'balance_due')) {
            return $q->orderByDesc('balance_due')->limit(5)->get();
        }

        return collect();
    }

    protected function getRecentSalesInvoices(int $companyId)
    {
        if (!$this->hasTable('sales_invoices')) return collect();

        $q = DB::table('sales_invoices');
        if ($this->hasColumn('sales_invoices', 'company_id')) $q->where('company_id', $companyId);
        if ($this->hasColumn('sales_invoices', 'deleted_at')) $q->whereNull('deleted_at');

        return $q->orderByDesc('id')->limit(5)->get();
    }

    protected function getRecentSupplierBills(int $companyId)
    {
        if (!$this->hasTable('finance_supplier_bills')) return collect();

        $q = DB::table('finance_supplier_bills');
        if ($this->hasColumn('finance_supplier_bills', 'company_id')) $q->where('company_id', $companyId);
        if ($this->hasColumn('finance_supplier_bills', 'deleted_at')) $q->whereNull('deleted_at');

        return $q->orderByDesc('id')->limit(5)->get();
    }

    protected function getRecentSupplierCredits(int $companyId)
    {
        if (!$this->hasTable('finance_supplier_credits')) return collect();

        $q = DB::table('finance_supplier_credits');
        if ($this->hasColumn('finance_supplier_credits', 'company_id')) $q->where('company_id', $companyId);
        if ($this->hasColumn('finance_supplier_credits', 'deleted_at')) $q->whereNull('deleted_at');

        return $q->orderByDesc('id')->limit(5)->get();
    }

    protected function getRecentJournalEntries(int $companyId)
    {
        if (!$this->hasTable('finance_journal_entries')) return collect();

        $q = DB::table('finance_journal_entries');
        if ($this->hasColumn('finance_journal_entries', 'company_id')) $q->where('company_id', $companyId);

        return $q->orderByDesc('id')->limit(5)->get();
    }

    protected function getRecentProjectInvoices(int $companyId)
    {
        if (!$this->hasTable('project_invoices')) return collect();

        $q = DB::table('project_invoices');
        if ($this->hasColumn('project_invoices', 'company_id')) $q->where('company_id', $companyId);
        if ($this->hasColumn('project_invoices', 'deleted_at')) $q->whereNull('deleted_at');

        return $q->orderByDesc('id')->limit(5)->get();
    }
}