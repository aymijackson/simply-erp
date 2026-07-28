<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CooDashboardController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id ?? 1;

        $kpis = [
            'open_requisitions'          => $this->countByStatuses('purchase_requisitions', $companyId, ['open', 'pending']),
            'pending_supplier_quotes'    => $this->countByStatuses('supplier_quotations', $companyId, ['pending']),
            'open_purchase_orders'       => $this->countByStatuses('purchase_orders', $companyId, ['open', 'approved', 'partial']),
            'goods_receipts_pending'     => $this->countByStatuses('goods_receipts', $companyId, ['pending', 'draft'], 'status'),
            'grn_pending_billing'        => $this->countByStatuses('goods_receipts', $companyId, ['pending', 'unbilled'], 'billing_status'),
            'active_projects'            => $this->countByStatuses('projects', $companyId, ['active', 'open', 'in_progress']),
            'projects_over_budget'       => $this->getProjectsOverBudgetCount($companyId),
            'billable_hours_month'       => $this->getBillableHoursThisMonth($companyId),
            'open_tickets'               => $this->countOpenTickets($companyId),
            'overdue_tickets'            => $this->countOverdueTickets($companyId),
            'low_stock_items'            => $this->countLowStockItems($companyId),
            'open_work_orders'           => $this->countByStatuses('work_orders', $companyId, ['open', 'released', 'in_progress']),
            'late_milestones'            => $this->countLateMilestones($companyId),
        ];

        $procurementPipeline = $this->getProcurementPipeline($companyId);
        $projectExecution = $this->getProjectExecutionSummary($companyId);
        $supportSummary = $this->getSupportSummary($companyId);
        $inventorySummary = $this->getInventorySummary($companyId);
        $productionSummary = $this->getProductionSummary($companyId);

        $riskItems = [
            'late_projects'        => $this->getLateProjects($companyId),
            'late_milestones'      => $this->getLateMilestones($companyId),
            'low_stock_items'      => $this->getLowStockItems($companyId),
            'overdue_tickets'      => $this->getOverdueTickets($companyId),
            'pending_goods_receipt'=> $this->getPendingGoodsReceipts($companyId),
        ];

        $recentActivities = [
            'purchase_orders' => $this->getRecentRows('purchase_orders', $companyId, 5),
            'goods_receipts'  => $this->getRecentRows('goods_receipts', $companyId, 5),
            'project_tasks'   => $this->getRecentRows('project_tasks', $companyId, 5),
            'timesheets'      => $this->getRecentRows('project_timesheets', $companyId, 5),
            'tickets'         => $this->getRecentRows('support_tickets', $companyId, 5),
        ];

        return view('dashboard.coo', compact(
            'kpis',
            'procurementPipeline',
            'projectExecution',
            'supportSummary',
            'inventorySummary',
            'productionSummary',
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

    protected function baseQuery(string $table, int $companyId)
    {
        $q = DB::table($table);

        if ($this->hasColumn($table, 'company_id')) {
            $q->where('company_id', $companyId);
        }

        if ($this->hasColumn($table, 'deleted_at')) {
            $q->whereNull('deleted_at');
        }

        return $q;
    }

    protected function countByStatuses(string $table, int $companyId, array $statuses, string $statusColumn = 'status'): int
    {
        if (!$this->hasTable($table) || !$this->hasColumn($table, $statusColumn)) {
            return 0;
        }

        return (int) $this->baseQuery($table, $companyId)
            ->whereIn($statusColumn, $statuses)
            ->count();
    }

    protected function countOpenTickets(int $companyId): int
    {
        if (!$this->hasTable('support_tickets') || !$this->hasColumn('support_tickets', 'status')) {
            return 0;
        }

        return (int) $this->baseQuery('support_tickets', $companyId)
            ->whereNotIn('status', ['closed', 'resolved'])
            ->count();
    }

    protected function countOverdueTickets(int $companyId): int
    {
        if (!$this->hasTable('support_tickets') || !$this->hasColumn('support_tickets', 'due_date')) {
            return 0;
        }

        $q = $this->baseQuery('support_tickets', $companyId)
            ->whereDate('due_date', '<', now()->toDateString());

        if ($this->hasColumn('support_tickets', 'status')) {
            $q->whereNotIn('status', ['closed', 'resolved']);
        }

        return (int) $q->count();
    }

    protected function countLowStockItems(int $companyId): int
    {
        if (
            !$this->hasTable('products') ||
            !$this->hasColumn('products', 'product_stock_quantity') ||
            !$this->hasColumn('products', 'reorder_level')
        ) {
            return 0;
        }

        return (int) $this->baseQuery('products', $companyId)
            ->whereColumn('product_stock_quantity', '<=', 'reorder_level')
            ->count();
    }

    protected function getProjectsOverBudgetCount(int $companyId): int
    {
        if (
            !$this->hasTable('projects') ||
            !$this->hasColumn('projects', 'budget_amount') ||
            !$this->hasColumn('projects', 'actual_cost')
        ) {
            return 0;
        }

        return (int) $this->baseQuery('projects', $companyId)
            ->where('actual_cost', '>', DB::raw('budget_amount'))
            ->count();
    }

    protected function getBillableHoursThisMonth(int $companyId): float
    {
        if (!$this->hasTable('project_timesheets')) {
            return 0;
        }

        $q = $this->baseQuery('project_timesheets', $companyId);

        if ($this->hasColumn('project_timesheets', 'entry_date')) {
            $q->whereMonth('entry_date', now()->month)
              ->whereYear('entry_date', now()->year);
        }

        if ($this->hasColumn('project_timesheets', 'billable_hours')) {
            return round((float) $q->sum('billable_hours'), 2);
        }

        return 0;
    }

    protected function countLateMilestones(int $companyId): int
    {
        if (!$this->hasTable('project_milestones') || !$this->hasColumn('project_milestones', 'due_date')) {
            return 0;
        }

        $q = $this->baseQuery('project_milestones', $companyId)
            ->whereDate('due_date', '<', now()->toDateString());

        if ($this->hasColumn('project_milestones', 'status')) {
            $q->whereNotIn('status', ['completed', 'closed']);
        }

        return (int) $q->count();
    }

    protected function getProcurementPipeline(int $companyId): array
    {
        return [
            'labels' => ['Requisitions', 'Quotations', 'Purchase Orders', 'Goods Receipts', 'Pending Billing'],
            'values' => [
                $this->countByStatuses('purchase_requisitions', $companyId, ['open', 'pending']),
                $this->countByStatuses('supplier_quotations', $companyId, ['pending']),
                $this->countByStatuses('purchase_orders', $companyId, ['open', 'approved', 'partial']),
                $this->countByStatuses('goods_receipts', $companyId, ['received', 'partial', 'posted']),
                $this->countByStatuses('goods_receipts', $companyId, ['pending', 'unbilled'], 'billing_status'),
            ],
        ];
    }

    protected function getProjectExecutionSummary(int $companyId): array
    {
        $active = $this->countByStatuses('projects', $companyId, ['active', 'open', 'in_progress']);

        $openTasks = 0;
        $completedTasks = 0;

        if ($this->hasTable('project_tasks') && $this->hasColumn('project_tasks', 'status')) {
            $openTasks = (int) $this->baseQuery('project_tasks', $companyId)
                ->whereNotIn('status', ['completed', 'closed'])
                ->count();

            $completedTasks = (int) $this->baseQuery('project_tasks', $companyId)
                ->whereIn('status', ['completed', 'closed'])
                ->count();
        }

        $lateMilestones = $this->countLateMilestones($companyId);

        return [
            'active_projects' => $active,
            'open_tasks' => $openTasks,
            'completed_tasks' => $completedTasks,
            'late_milestones' => $lateMilestones,
        ];
    }

    protected function getSupportSummary(int $companyId): array
    {
        $open = $this->countOpenTickets($companyId);
        $overdue = $this->countOverdueTickets($companyId);

        $resolved = 0;
        if ($this->hasTable('support_tickets') && $this->hasColumn('support_tickets', 'status')) {
            $resolved = (int) $this->baseQuery('support_tickets', $companyId)
                ->whereIn('status', ['resolved', 'closed'])
                ->count();
        }

        return [
            'open_tickets' => $open,
            'overdue_tickets' => $overdue,
            'resolved_tickets' => $resolved,
        ];
    }

    protected function getInventorySummary(int $companyId): array
    {
        $lowStock = $this->countLowStockItems($companyId);

        $totalProducts = 0;
        $stockQty = 0;

        if ($this->hasTable('products')) {
            $q = $this->baseQuery('products', $companyId);

            $totalProducts = (int) (clone $q)->count();

            if ($this->hasColumn('products', 'product_stock_quantity')) {
                $stockQty = round((float) (clone $q)->sum('product_stock_quantity'), 2);
            }
        }

        return [
            'low_stock_items' => $lowStock,
            'total_products' => $totalProducts,
            'total_stock_qty' => $stockQty,
        ];
    }

    protected function getProductionSummary(int $companyId): array
    {
        $open = $this->countByStatuses('work_orders', $companyId, ['open', 'released', 'in_progress']);
        $completed = $this->countByStatuses('work_orders', $companyId, ['completed', 'closed']);
        $draft = $this->countByStatuses('work_orders', $companyId, ['draft']);

        return [
            'open_work_orders' => $open,
            'completed_work_orders' => $completed,
            'draft_work_orders' => $draft,
        ];
    }

    protected function getLateProjects(int $companyId)
    {
        if (!$this->hasTable('projects') || !$this->hasColumn('projects', 'end_date')) {
            return collect();
        }

        $q = $this->baseQuery('projects', $companyId)
            ->whereDate('end_date', '<', now()->toDateString());

        if ($this->hasColumn('projects', 'status')) {
            $q->whereNotIn('status', ['completed', 'closed', 'cancelled']);
        }

        return $q->limit(5)->get();
    }

    protected function getLateMilestones(int $companyId)
    {
        if (!$this->hasTable('project_milestones') || !$this->hasColumn('project_milestones', 'due_date')) {
            return collect();
        }

        $q = $this->baseQuery('project_milestones', $companyId)
            ->whereDate('due_date', '<', now()->toDateString());

        if ($this->hasColumn('project_milestones', 'status')) {
            $q->whereNotIn('status', ['completed', 'closed']);
        }

        return $q->limit(5)->get();
    }

    protected function getLowStockItems(int $companyId)
    {
        if (
            !$this->hasTable('products') ||
            !$this->hasColumn('products', 'product_stock_quantity') ||
            !$this->hasColumn('products', 'reorder_level')
        ) {
            return collect();
        }

        return $this->baseQuery('products', $companyId)
            ->whereColumn('product_stock_quantity', '<=', 'reorder_level')
            ->limit(5)
            ->get();
    }

    protected function getOverdueTickets(int $companyId)
    {
        if (!$this->hasTable('support_tickets') || !$this->hasColumn('support_tickets', 'due_date')) {
            return collect();
        }

        $q = $this->baseQuery('support_tickets', $companyId)
            ->whereDate('due_date', '<', now()->toDateString());

        if ($this->hasColumn('support_tickets', 'status')) {
            $q->whereNotIn('status', ['resolved', 'closed']);
        }

        return $q->limit(5)->get();
    }

    protected function getPendingGoodsReceipts(int $companyId)
    {
        if (!$this->hasTable('goods_receipts')) {
            return collect();
        }

        $q = $this->baseQuery('goods_receipts', $companyId);

        if ($this->hasColumn('goods_receipts', 'status')) {
            $q->whereIn('status', ['pending', 'draft', 'partial']);
        }

        return $q->limit(5)->get();
    }

    protected function getRecentRows(string $table, int $companyId, int $limit = 5)
    {
        if (!$this->hasTable($table)) {
            return collect();
        }

        return $this->baseQuery($table, $companyId)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }
}