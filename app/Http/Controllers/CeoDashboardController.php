<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CeoDashboardController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id ?? 1;

        $kpis = [
            'cash_balance'         => $this->getCashBalance($companyId),
            'revenue_month'        => $this->getRevenueThisMonth($companyId),
            'expenses_month'       => $this->getExpensesThisMonth($companyId),
            'net_profit_month'     => $this->getRevenueThisMonth($companyId) - $this->getExpensesThisMonth($companyId),
            'accounts_receivable'  => $this->getAccountsReceivable($companyId),
            'accounts_payable'     => $this->getAccountsPayable($companyId),
            'active_projects'      => $this->getActiveProjects($companyId),
            'projects_over_budget' => $this->getProjectsOverBudget($companyId),
            'open_procurement'     => $this->getOpenProcurementExposure($companyId),
            'open_opportunities'   => $this->getOpenOpportunities($companyId),
            'open_tickets'         => $this->getOpenTickets($companyId),
            'low_stock_items'      => $this->getLowStockItems($companyId),
        ];

        $financeTrend = $this->getFinanceTrend($companyId);
        $projectSummary = $this->getProjectSummary($companyId);
        $procurementSummary = $this->getProcurementSummary($companyId);
        $riskItems = $this->getRiskItems($companyId);
        $recentActivities = $this->getRecentActivities($companyId);

        return view('dashboard.ceo', compact(
            'kpis',
            'financeTrend',
            'projectSummary',
            'procurementSummary',
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

    protected function getActiveProjects(int $companyId): int
    {
        if (!$this->hasTable('projects')) return 0;

        $q = DB::table('projects');

        if ($this->hasColumn('projects', 'company_id')) {
            $q->where('company_id', $companyId);
        }
        if ($this->hasColumn('projects', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }
        if ($this->hasColumn('projects', 'status')) {
            $q->whereIn('status', ['active', 'open', 'in_progress']);
        }

        return (int) $q->count();
    }

    protected function getProjectsOverBudget(int $companyId): int
    {
        if (!$this->hasTable('projects')) return 0;
        if (!$this->hasColumn('projects', 'budget_amount') || !$this->hasColumn('projects', 'actual_cost')) return 0;

        $q = DB::table('projects')
            ->where('actual_cost', '>', DB::raw('budget_amount'));

        if ($this->hasColumn('projects', 'company_id')) {
            $q->where('company_id', $companyId);
        }
        if ($this->hasColumn('projects', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }

        return (int) $q->count();
    }

    protected function getOpenProcurementExposure(int $companyId): int
    {
        $count = 0;

        foreach ([
            ['purchase_requisitions', ['open', 'pending']],
            ['supplier_quotations', ['pending']],
            ['purchase_orders', ['open', 'approved', 'partial']],
        ] as [$table, $statuses]) {
            if (!$this->hasTable($table)) continue;

            $q = DB::table($table);

            if ($this->hasColumn($table, 'company_id')) {
                $q->where('company_id', $companyId);
            }
            if ($this->hasColumn($table, 'deleted_at')) {
                $q->whereNull('deleted_at');
            }
            if ($this->hasColumn($table, 'status')) {
                $q->whereIn('status', $statuses);
            }

            $count += (int) $q->count();
        }

        return $count;
    }

    protected function getOpenOpportunities(int $companyId): int
    {
        if (!$this->hasTable('opportunities')) return 0;

        $q = DB::table('opportunities');

        if ($this->hasColumn('opportunities', 'company_id')) {
            $q->where('company_id', $companyId);
        }
        if ($this->hasColumn('opportunities', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }
        if ($this->hasColumn('opportunities', 'status')) {
            $q->whereNotIn('status', ['won', 'lost', 'closed']);
        }

        return (int) $q->count();
    }

    protected function getOpenTickets(int $companyId): int
    {
        if (!$this->hasTable('support_tickets')) return 0;

        $q = DB::table('support_tickets');

        if ($this->hasColumn('support_tickets', 'company_id')) {
            $q->where('company_id', $companyId);
        }
        if ($this->hasColumn('support_tickets', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }
        if ($this->hasColumn('support_tickets', 'status')) {
            $q->whereNotIn('status', ['closed', 'resolved']);
        }

        return (int) $q->count();
    }

    protected function getLowStockItems(int $companyId): int
    {
        if (!$this->hasTable('products')) return 0;
        if (!$this->hasColumn('products', 'product_stock_quantity') || !$this->hasColumn('products', 'reorder_level')) return 0;

        $q = DB::table('products')
            ->whereColumn('product_stock_quantity', '<=', 'reorder_level');

        if ($this->hasColumn('products', 'company_id')) {
            $q->where('company_id', $companyId);
        }
        if ($this->hasColumn('products', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }

        return (int) $q->count();
    }

    protected function getFinanceTrend(int $companyId): array
    {
        $labels = [];
        $revenue = [];
        $expenses = [];

        for ($i = 5; $i >= 0; $i--) {
            $d = now()->copy()->subMonths($i);
            $labels[] = $d->format('M Y');

            $rev = 0;
            if ($this->hasTable('sales_invoices')) {
                $q = DB::table('sales_invoices');
                if ($this->hasColumn('sales_invoices', 'company_id')) $q->where('company_id', $companyId);
                if ($this->hasColumn('sales_invoices', 'deleted_at')) $q->whereNull('deleted_at');
                if ($this->hasColumn('sales_invoices', 'status')) $q->whereIn('status', ['posted', 'part_paid', 'paid']);
                if ($this->hasColumn('sales_invoices', 'invoice_date')) $q->whereMonth('invoice_date', $d->month)->whereYear('invoice_date', $d->year);
                if ($this->hasColumn('sales_invoices', 'total_amount')) $rev = (float) $q->sum('total_amount');
                elseif ($this->hasColumn('sales_invoices', 'total')) $rev = (float) $q->sum('total');
            }

            $exp = 0;
            if ($this->hasTable('expenses')) {
                $q = DB::table('expenses');
                if ($this->hasColumn('expenses', 'company_id')) $q->where('company_id', $companyId);
                if ($this->hasColumn('expenses', 'deleted_at')) $q->whereNull('deleted_at');
                if ($this->hasColumn('expenses', 'expense_date')) $q->whereMonth('expense_date', $d->month)->whereYear('expense_date', $d->year);
                if ($this->hasColumn('expenses', 'amount')) $exp = (float) $q->sum('amount');
            }

            $revenue[] = round($rev, 2);
            $expenses[] = round($exp, 2);
        }

        return compact('labels', 'revenue', 'expenses');
    }

    protected function getProjectSummary(int $companyId): array
    {
        $active = $this->getActiveProjects($companyId);
        $overBudget = $this->getProjectsOverBudget($companyId);

        $projectRevenue = 0;
        if ($this->hasTable('project_invoices')) {
            $q = DB::table('project_invoices');
            if ($this->hasColumn('project_invoices', 'company_id')) $q->where('company_id', $companyId);
            if ($this->hasColumn('project_invoices', 'deleted_at')) $q->whereNull('deleted_at');
            if ($this->hasColumn('project_invoices', 'status')) $q->whereIn('status', ['posted', 'part_paid', 'paid']);
            if ($this->hasColumn('project_invoices', 'total_amount')) $projectRevenue = (float) $q->sum('total_amount');
        }

        $projectCost = 0;
        if ($this->hasTable('project_costs')) {
            $q = DB::table('project_costs');
            if ($this->hasColumn('project_costs', 'company_id')) $q->where('company_id', $companyId);
            if ($this->hasColumn('project_costs', 'deleted_at')) $q->whereNull('deleted_at');
            if ($this->hasColumn('project_costs', 'status')) $q->where('status', 'posted');
            if ($this->hasColumn('project_costs', 'amount')) $projectCost = (float) $q->sum('amount');
        }

        return [
            'active_projects' => $active,
            'over_budget_projects' => $overBudget,
            'project_revenue' => round($projectRevenue, 2),
            'project_cost' => round($projectCost, 2),
        ];
    }

    protected function getProcurementSummary(int $companyId): array
    {
        $summary = [
            'rfqs' => 0,
            'supplier_quotes' => 0,
            'purchase_orders' => 0,
            'goods_receipts_pending_billing' => 0,
        ];

        if ($this->hasTable('purchase_requisitions')) {
            $q = DB::table('purchase_requisitions');
            if ($this->hasColumn('purchase_requisitions', 'company_id')) $q->where('company_id', $companyId);
            if ($this->hasColumn('purchase_requisitions', 'deleted_at')) $q->whereNull('deleted_at');
            if ($this->hasColumn('purchase_requisitions', 'status')) $q->whereIn('status', ['open', 'pending']);
            $summary['rfqs'] = (int) $q->count();
        }

        if ($this->hasTable('supplier_quotations')) {
            $q = DB::table('supplier_quotations');
            if ($this->hasColumn('supplier_quotations', 'company_id')) $q->where('company_id', $companyId);
            if ($this->hasColumn('supplier_quotations', 'deleted_at')) $q->whereNull('deleted_at');
            if ($this->hasColumn('supplier_quotations', 'status')) $q->whereIn('status', ['pending']);
            $summary['supplier_quotes'] = (int) $q->count();
        }

        if ($this->hasTable('purchase_orders')) {
            $q = DB::table('purchase_orders');
            if ($this->hasColumn('purchase_orders', 'company_id')) $q->where('company_id', $companyId);
            if ($this->hasColumn('purchase_orders', 'deleted_at')) $q->whereNull('deleted_at');
            if ($this->hasColumn('purchase_orders', 'status')) $q->whereIn('status', ['open', 'approved', 'partial']);
            $summary['purchase_orders'] = (int) $q->count();
        }

        if ($this->hasTable('goods_receipts')) {
            $q = DB::table('goods_receipts');
            if ($this->hasColumn('goods_receipts', 'company_id')) $q->where('company_id', $companyId);
            if ($this->hasColumn('goods_receipts', 'deleted_at')) $q->whereNull('deleted_at');
            if ($this->hasColumn('goods_receipts', 'billing_status')) $q->whereIn('billing_status', ['pending', 'unbilled']);
            $summary['goods_receipts_pending_billing'] = (int) $q->count();
        }

        return $summary;
    }

    protected function getRiskItems(int $companyId): array
    {
        $overdueInvoices = collect();
        $overdueBills = collect();
        $overBudgetProjects = collect();

        if ($this->hasTable('sales_invoices') && $this->hasColumn('sales_invoices', 'due_date')) {
            $q = DB::table('sales_invoices');
            if ($this->hasColumn('sales_invoices', 'company_id')) $q->where('company_id', $companyId);
            if ($this->hasColumn('sales_invoices', 'deleted_at')) $q->whereNull('deleted_at');
            if ($this->hasColumn('sales_invoices', 'status')) $q->whereIn('status', ['posted', 'part_paid']);
            $overdueInvoices = $q->whereDate('due_date', '<', now()->toDateString())->limit(5)->get();
        }

        if ($this->hasTable('finance_supplier_bills') && $this->hasColumn('finance_supplier_bills', 'due_date')) {
            $q = DB::table('finance_supplier_bills');
            if ($this->hasColumn('finance_supplier_bills', 'company_id')) $q->where('company_id', $companyId);
            if ($this->hasColumn('finance_supplier_bills', 'deleted_at')) $q->whereNull('deleted_at');
            if ($this->hasColumn('finance_supplier_bills', 'status')) $q->whereIn('status', ['posted', 'part_paid']);
            $overdueBills = $q->whereDate('due_date', '<', now()->toDateString())->limit(5)->get();
        }

        if ($this->hasTable('projects') && $this->hasColumn('projects', 'budget_amount') && $this->hasColumn('projects', 'actual_cost')) {
            $q = DB::table('projects')->where('actual_cost', '>', DB::raw('budget_amount'));
            if ($this->hasColumn('projects', 'company_id')) $q->where('company_id', $companyId);
            if ($this->hasColumn('projects', 'deleted_at')) $q->whereNull('deleted_at');
            $overBudgetProjects = $q->limit(5)->get();
        }

        return [
            'overdue_invoices' => $overdueInvoices,
            'overdue_bills' => $overdueBills,
            'over_budget_projects' => $overBudgetProjects,
        ];
    }

    protected function getRecentActivities(int $companyId): array
    {
        $salesInvoices = collect();
        $supplierBills = collect();
        $projectInvoices = collect();
        $journalEntries = collect();

        if ($this->hasTable('sales_invoices')) {
            $q = DB::table('sales_invoices');
            if ($this->hasColumn('sales_invoices', 'company_id')) $q->where('company_id', $companyId);
            if ($this->hasColumn('sales_invoices', 'deleted_at')) $q->whereNull('deleted_at');
            $salesInvoices = $q->orderByDesc('id')->limit(5)->get();
        }

        if ($this->hasTable('finance_supplier_bills')) {
            $q = DB::table('finance_supplier_bills');
            if ($this->hasColumn('finance_supplier_bills', 'company_id')) $q->where('company_id', $companyId);
            if ($this->hasColumn('finance_supplier_bills', 'deleted_at')) $q->whereNull('deleted_at');
            $supplierBills = $q->orderByDesc('id')->limit(5)->get();
        }

        if ($this->hasTable('project_invoices')) {
            $q = DB::table('project_invoices');
            if ($this->hasColumn('project_invoices', 'company_id')) $q->where('company_id', $companyId);
            if ($this->hasColumn('project_invoices', 'deleted_at')) $q->whereNull('deleted_at');
            $projectInvoices = $q->orderByDesc('id')->limit(5)->get();
        }

        if ($this->hasTable('finance_journal_entries')) {
            $q = DB::table('finance_journal_entries');
            if ($this->hasColumn('finance_journal_entries', 'company_id')) $q->where('company_id', $companyId);
            $journalEntries = $q->orderByDesc('id')->limit(5)->get();
        }

        return [
            'sales_invoices' => $salesInvoices,
            'supplier_bills' => $supplierBills,
            'project_invoices' => $projectInvoices,
            'journal_entries' => $journalEntries,
        ];
    }
}