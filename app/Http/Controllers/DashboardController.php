<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id ?? 1;

        $kpis = [
            'cash_balance'              => $this->getCashBalance($companyId),
            'accounts_receivable'       => $this->getAccountsReceivable($companyId),
            'accounts_payable'          => $this->getAccountsPayable($companyId),
            'revenue_month'             => $this->getRevenueThisMonth($companyId),
            'expenses_month'            => $this->getExpensesThisMonth($companyId),
            'net_profit_month'          => $this->getRevenueThisMonth($companyId) - $this->getExpensesThisMonth($companyId),
            'active_projects'           => $this->getActiveProjects($companyId),
            'projects_over_budget'      => $this->getProjectsOverBudget($companyId),
            'open_rfqs'                 => $this->getCountIfTableExists('purchase_requisitions', ['company_id' => $companyId, 'status' => 'open']),
            'pending_supplier_quotes'   => $this->getCountIfTableExists('supplier_quotations', ['company_id' => $companyId, 'status' => 'pending']),
            'open_purchase_orders'      => $this->getCountIfTableExists('purchase_orders', ['company_id' => $companyId, 'status' => 'open']),
            'grn_pending_billing'       => $this->getGrnPendingBilling($companyId),
            'open_tickets'              => $this->getOpenTickets($companyId),
            'low_stock_items'           => $this->getLowStockItems($companyId),
            'active_leads'              => $this->getActiveLeads($companyId),
            'open_opportunities'        => $this->getOpenOpportunities($companyId),
            'billable_hours_month'      => $this->getBillableHoursThisMonth($companyId),
            'project_invoice_total'     => $this->getProjectInvoiceTotalThisMonth($companyId),
        ];

        $financeTrend = $this->getFinanceTrend($companyId);
        $procurementStatus = $this->getProcurementStatusSummary($companyId);
        $projectStatus = $this->getProjectStatusSummary($companyId);
        $ticketStatus = $this->getTicketStatusSummary($companyId);
        $topCostProjects = $this->getTopCostProjects($companyId);

        $attentionItems = [
            'overdue_customer_invoices' => $this->getOverdueCustomerInvoices($companyId),
            'overdue_supplier_bills'    => $this->getOverdueSupplierBills($companyId),
            'over_budget_projects'      => $this->getOverBudgetProjectsList($companyId),
            'low_stock_items'           => $this->getLowStockItemsList($companyId),
        ];

        $recentActivities = [
            'sales_invoices'   => $this->getRecentSalesInvoices($companyId),
            'supplier_bills'   => $this->getRecentSupplierBills($companyId),
            'project_invoices' => $this->getRecentProjectInvoices($companyId),
            'timesheets'       => $this->getRecentTimesheets($companyId),
            'journal_entries'  => $this->getRecentJournalEntries($companyId),
        ];

        return view('dashboard.index', compact(
            'kpis',
            'financeTrend',
            'procurementStatus',
            'projectStatus',
            'ticketStatus',
            'topCostProjects',
            'attentionItems',
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

    protected function getCountIfTableExists(string $table, array $where = []): int
    {
        if (!$this->hasTable($table)) {
            return 0;
        }

        $q = DB::table($table);

        foreach ($where as $k => $v) {
            if ($this->hasColumn($table, $k)) {
                $q->where($k, $v);
            }
        }

        if ($this->hasColumn($table, 'deleted_at')) {
            $q->whereNull('deleted_at');
        }

        return (int) $q->count();
    }

    protected function getCashBalance(int $companyId): float
    {
        if ($this->hasTable('finance_bank_accounts')) {
            $q = DB::table('finance_bank_accounts');

            if ($this->hasColumn('finance_bank_accounts', 'company_id')) {
                $q->where('company_id', $companyId);
            }
            if ($this->hasColumn('finance_bank_accounts', 'current_balance')) {
                return (float) $q->sum('current_balance');
            }
            if ($this->hasColumn('finance_bank_accounts', 'balance')) {
                return (float) $q->sum('balance');
            }
        }

        return 0;
    }

    protected function getAccountsReceivable(int $companyId): float
    {
        if ($this->hasTable('sales_invoices')) {
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
                return (float) $q->sum('balance_due');
            }
            if ($this->hasColumn('sales_invoices', 'balance')) {
                return (float) $q->sum('balance');
            }
        }

        return 0;
    }

    protected function getAccountsPayable(int $companyId): float
    {
        if ($this->hasTable('finance_supplier_bills')) {
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
                return (float) $q->sum('balance_due');
            }
        }

        return 0;
    }

    protected function getRevenueThisMonth(int $companyId): float
    {
        $month = now()->month;
        $year = now()->year;

        if ($this->hasTable('sales_invoices')) {
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
                $q->whereMonth('invoice_date', $month)->whereYear('invoice_date', $year);
            }
            if ($this->hasColumn('sales_invoices', 'total_amount')) {
                return (float) $q->sum('total_amount');
            }
            if ($this->hasColumn('sales_invoices', 'total')) {
                return (float) $q->sum('total');
            }
        }

        return 0;
    }

    protected function getExpensesThisMonth(int $companyId): float
    {
        $month = now()->month;
        $year = now()->year;

        if ($this->hasTable('expenses')) {
            $q = DB::table('expenses');

            if ($this->hasColumn('expenses', 'company_id')) {
                $q->where('company_id', $companyId);
            }
            if ($this->hasColumn('expenses', 'deleted_at')) {
                $q->whereNull('deleted_at');
            }
            if ($this->hasColumn('expenses', 'expense_date')) {
                $q->whereMonth('expense_date', $month)->whereYear('expense_date', $year);
            }
            if ($this->hasColumn('expenses', 'amount')) {
                return (float) $q->sum('amount');
            }
        }

        if ($this->hasTable('finance_journal_entry_lines') && $this->hasTable('finance_journal_entries') && $this->hasTable('finance_accounts')) {
            $q = DB::table('finance_journal_entry_lines as l')
                ->join('finance_journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
                ->join('finance_accounts as a', 'a.id', '=', 'l.account_id');

            if ($this->hasColumn('finance_journal_entries', 'company_id')) {
                $q->where('e.company_id', $companyId);
            }
            if ($this->hasColumn('finance_journal_entries', 'status')) {
                $q->where('e.status', 'posted');
            }
            if ($this->hasColumn('finance_journal_entries', 'entry_date')) {
                $q->whereMonth('e.entry_date', $month)->whereYear('e.entry_date', $year);
            }

            if ($this->hasColumn('finance_accounts', 'account_type') && $this->hasColumn('finance_journal_entry_lines', 'debit')) {
                return (float) $q->where('a.account_type', 'expense')->sum('l.debit');
            }
        }

        return 0;
    }

    protected function getActiveProjects(int $companyId): int
    {
        if (!$this->hasTable('projects')) {
            return 0;
        }

        $q = DB::table('projects');

        if ($this->hasColumn('projects', 'company_id')) {
            $q->where('company_id', $companyId);
        }
        if ($this->hasColumn('projects', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }
        if ($this->hasColumn('projects', 'status')) {
            $q->whereIn('status', ['active', 'in_progress', 'open']);
        }

        return (int) $q->count();
    }

    protected function getProjectsOverBudget(int $companyId): int
    {
        if (!$this->hasTable('projects') || !$this->hasColumn('projects', 'budget_amount') || !$this->hasColumn('projects', 'actual_cost')) {
            return 0;
        }

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

    protected function getGrnPendingBilling(int $companyId): int
    {
        if (!$this->hasTable('goods_receipts')) {
            return 0;
        }

        $q = DB::table('goods_receipts');

        if ($this->hasColumn('goods_receipts', 'company_id')) {
            $q->where('company_id', $companyId);
        }
        if ($this->hasColumn('goods_receipts', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }
        if ($this->hasColumn('goods_receipts', 'billing_status')) {
            $q->whereIn('billing_status', ['pending', 'unbilled']);
            return (int) $q->count();
        }

        return 0;
    }

    protected function getOpenTickets(int $companyId): int
    {
        if (!$this->hasTable('support_tickets')) {
            return 0;
        }

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
        if (!$this->hasTable('products')) {
            return 0;
        }

        $q = DB::table('products');

        if ($this->hasColumn('products', 'company_id')) {
            $q->where('company_id', $companyId);
        }
        if ($this->hasColumn('products', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }

        if ($this->hasColumn('products', 'product_stock_quantity') && $this->hasColumn('products', 'reorder_level')) {
            $q->whereColumn('product_stock_quantity', '<=', 'reorder_level');
            return (int) $q->count();
        }

        return 0;
    }

    protected function getActiveLeads(int $companyId): int
    {
        if (!$this->hasTable('leads')) {
            return 0;
        }

        $q = DB::table('leads');

        if ($this->hasColumn('leads', 'company_id')) {
            $q->where('company_id', $companyId);
        }
        if ($this->hasColumn('leads', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }
        if ($this->hasColumn('leads', 'status')) {
            $q->whereNotIn('status', ['converted', 'closed', 'lost']);
        }

        return (int) $q->count();
    }

    protected function getOpenOpportunities(int $companyId): int
    {
        if (!$this->hasTable('opportunities')) {
            return 0;
        }

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

    protected function getBillableHoursThisMonth(int $companyId): float
    {
        if (!$this->hasTable('project_timesheets')) {
            return 0;
        }

        $q = DB::table('project_timesheets');

        if ($this->hasColumn('project_timesheets', 'company_id')) {
            $q->where('company_id', $companyId);
        }
        if ($this->hasColumn('project_timesheets', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }
        if ($this->hasColumn('project_timesheets', 'entry_date')) {
            $q->whereMonth('entry_date', now()->month)
              ->whereYear('entry_date', now()->year);
        }
        if ($this->hasColumn('project_timesheets', 'billable_hours')) {
            return (float) $q->sum('billable_hours');
        }

        return 0;
    }

    protected function getProjectInvoiceTotalThisMonth(int $companyId): float
    {
        if (!$this->hasTable('project_invoices')) {
            return 0;
        }

        $q = DB::table('project_invoices');

        if ($this->hasColumn('project_invoices', 'company_id')) {
            $q->where('company_id', $companyId);
        }
        if ($this->hasColumn('project_invoices', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }
        if ($this->hasColumn('project_invoices', 'invoice_date')) {
            $q->whereMonth('invoice_date', now()->month)
              ->whereYear('invoice_date', now()->year);
        }
        if ($this->hasColumn('project_invoices', 'status')) {
            $q->whereIn('status', ['posted', 'part_paid', 'paid']);
        }
        if ($this->hasColumn('project_invoices', 'total_amount')) {
            return (float) $q->sum('total_amount');
        }

        return 0;
    }

    protected function getFinanceTrend(int $companyId): array
    {
        $months = [];
        $revenue = [];
        $expenses = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->copy()->subMonths($i);
            $months[] = $date->format('M Y');

            $rev = 0;
            if ($this->hasTable('sales_invoices')) {
                $q = DB::table('sales_invoices');
                if ($this->hasColumn('sales_invoices', 'company_id')) {
                    $q->where('company_id', $companyId);
                }
                if ($this->hasColumn('sales_invoices', 'status')) {
                    $q->whereIn('status', ['posted', 'part_paid', 'paid']);
                }
                if ($this->hasColumn('sales_invoices', 'invoice_date')) {
                    $q->whereMonth('invoice_date', $date->month)
                      ->whereYear('invoice_date', $date->year);
                }
                if ($this->hasColumn('sales_invoices', 'total_amount')) {
                    $rev = (float) $q->sum('total_amount');
                } elseif ($this->hasColumn('sales_invoices', 'total')) {
                    $rev = (float) $q->sum('total');
                }
            }

            $exp = 0;
            if ($this->hasTable('expenses')) {
                $q = DB::table('expenses');
                if ($this->hasColumn('expenses', 'company_id')) {
                    $q->where('company_id', $companyId);
                }
                if ($this->hasColumn('expenses', 'expense_date')) {
                    $q->whereMonth('expense_date', $date->month)
                      ->whereYear('expense_date', $date->year);
                }
                if ($this->hasColumn('expenses', 'amount')) {
                    $exp = (float) $q->sum('amount');
                }
            }

            $revenue[] = round($rev, 2);
            $expenses[] = round($exp, 2);
        }

        return [
            'labels' => $months,
            'revenue' => $revenue,
            'expenses' => $expenses,
        ];
    }

    protected function getProcurementStatusSummary(int $companyId): array
    {
        return [
            'rfqs' => $this->getCountIfTableExists('purchase_requisitions', ['company_id' => $companyId, 'status' => 'open']),
            'quotes_pending' => $this->getCountIfTableExists('supplier_quotations', ['company_id' => $companyId, 'status' => 'pending']),
            'purchase_orders' => $this->getCountIfTableExists('purchase_orders', ['company_id' => $companyId, 'status' => 'open']),
            'grn_pending_billing' => $this->getGrnPendingBilling($companyId),
        ];
    }

    protected function getProjectStatusSummary(int $companyId): array
    {
        if (!$this->hasTable('projects')) {
            return [
                'labels' => ['Active', 'Completed', 'On Hold', 'Cancelled'],
                'values' => [0, 0, 0, 0],
            ];
        }

        $labels = ['Active', 'Completed', 'On Hold', 'Cancelled'];
        $statuses = [
            ['active', 'in_progress', 'open'],
            ['completed', 'closed'],
            ['on_hold', 'paused'],
            ['cancelled'],
        ];

        $values = [];

        foreach ($statuses as $group) {
            $q = DB::table('projects');
            if ($this->hasColumn('projects', 'company_id')) {
                $q->where('company_id', $companyId);
            }
            if ($this->hasColumn('projects', 'deleted_at')) {
                $q->whereNull('deleted_at');
            }
            if ($this->hasColumn('projects', 'status')) {
                $q->whereIn('status', $group);
            }
            $values[] = (int) $q->count();
        }

        return compact('labels', 'values');
    }

    protected function getTicketStatusSummary(int $companyId): array
    {
        if (!$this->hasTable('support_tickets')) {
            return [
                'labels' => ['Open', 'In Progress', 'Resolved', 'Closed'],
                'values' => [0, 0, 0, 0],
            ];
        }

        $map = [
            'Open' => ['open'],
            'In Progress' => ['in_progress', 'pending'],
            'Resolved' => ['resolved'],
            'Closed' => ['closed'],
        ];

        $labels = [];
        $values = [];

        foreach ($map as $label => $statuses) {
            $q = DB::table('support_tickets');
            if ($this->hasColumn('support_tickets', 'company_id')) {
                $q->where('company_id', $companyId);
            }
            if ($this->hasColumn('support_tickets', 'deleted_at')) {
                $q->whereNull('deleted_at');
            }
            if ($this->hasColumn('support_tickets', 'status')) {
                $q->whereIn('status', $statuses);
            }
            $labels[] = $label;
            $values[] = (int) $q->count();
        }

        return compact('labels', 'values');
    }

    protected function getTopCostProjects(int $companyId): array
    {
        if (!$this->hasTable('project_costs') || !$this->hasTable('projects')) {
            return [];
        }

        $q = DB::table('project_costs as pc')
            ->join('projects as p', 'p.id', '=', 'pc.project_id')
            ->selectRaw('p.project_name, p.project_code, SUM(pc.amount) as total_amount');

        if ($this->hasColumn('project_costs', 'company_id')) {
            $q->where('pc.company_id', $companyId);
        }
        if ($this->hasColumn('project_costs', 'deleted_at')) {
            $q->whereNull('pc.deleted_at');
        }
        if ($this->hasColumn('project_costs', 'status')) {
            $q->where('pc.status', 'posted');
        }

        return $q->groupBy('p.project_name', 'p.project_code')
            ->orderByDesc('total_amount')
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'name' => trim(($r->project_code ?? '') . ' - ' . ($r->project_name ?? '')),
                'amount' => (float) $r->total_amount,
            ])->toArray();
    }

    protected function getOverdueCustomerInvoices(int $companyId)
    {
        if (!$this->hasTable('sales_invoices') || !$this->hasColumn('sales_invoices', 'due_date')) {
            return collect();
        }

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

        return $q->whereDate('due_date', '<', now()->toDateString())
            ->limit(8)
            ->get();
    }

    protected function getOverdueSupplierBills(int $companyId)
    {
        if (!$this->hasTable('finance_supplier_bills') || !$this->hasColumn('finance_supplier_bills', 'due_date')) {
            return collect();
        }

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

        return $q->whereDate('due_date', '<', now()->toDateString())
            ->limit(8)
            ->get();
    }

    protected function getOverBudgetProjectsList(int $companyId)
    {
        if (!$this->hasTable('projects') || !$this->hasColumn('projects', 'budget_amount') || !$this->hasColumn('projects', 'actual_cost')) {
            return collect();
        }

        $q = DB::table('projects')
            ->where('actual_cost', '>', DB::raw('budget_amount'));

        if ($this->hasColumn('projects', 'company_id')) {
            $q->where('company_id', $companyId);
        }
        if ($this->hasColumn('projects', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }

        return $q->limit(8)->get();
    }

    protected function getLowStockItemsList(int $companyId)
    {
        if (!$this->hasTable('products') || !$this->hasColumn('products', 'product_stock_quantity') || !$this->hasColumn('products', 'reorder_level')) {
            return collect();
        }

        $q = DB::table('products')
            ->whereColumn('product_stock_quantity', '<=', 'reorder_level');

        if ($this->hasColumn('products', 'company_id')) {
            $q->where('company_id', $companyId);
        }
        if ($this->hasColumn('products', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }

        return $q->limit(8)->get();
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

    protected function getRecentProjectInvoices(int $companyId)
    {
        if (!$this->hasTable('project_invoices')) return collect();

        $q = DB::table('project_invoices');
        if ($this->hasColumn('project_invoices', 'company_id')) $q->where('company_id', $companyId);
        if ($this->hasColumn('project_invoices', 'deleted_at')) $q->whereNull('deleted_at');

        return $q->orderByDesc('id')->limit(5)->get();
    }

    protected function getRecentTimesheets(int $companyId)
    {
        if (!$this->hasTable('project_timesheets')) return collect();

        $q = DB::table('project_timesheets');
        if ($this->hasColumn('project_timesheets', 'company_id')) $q->where('company_id', $companyId);
        if ($this->hasColumn('project_timesheets', 'deleted_at')) $q->whereNull('deleted_at');

        return $q->orderByDesc('id')->limit(5)->get();
    }

    protected function getRecentJournalEntries(int $companyId)
    {
        if (!$this->hasTable('finance_journal_entries')) return collect();

        $q = DB::table('finance_journal_entries');
        if ($this->hasColumn('finance_journal_entries', 'company_id')) $q->where('company_id', $companyId);

        return $q->orderByDesc('id')->limit(5)->get();
    }
}