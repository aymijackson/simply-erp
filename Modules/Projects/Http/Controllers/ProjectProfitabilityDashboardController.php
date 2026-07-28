<?php

namespace Modules\Projects\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\ProjectBudget;

class ProjectProfitabilityDashboardController extends Controller
{
    public function index()
    {
        return view('projects.profitability.index');
    }

    public function lookupProjects(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $term = trim((string) $request->get('q', ''));

        $rows = Project::query()
            ->where('company_id', $companyId)
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('project_code', 'like', "%{$term}%")
                        ->orWhere('project_name', 'like', "%{$term}%");
                });
            })
            ->orderBy('project_name')
            ->limit(30)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'text' => trim(($p->project_code ?? '') . ' - ' . ($p->project_name ?? '')),
            ]);

        return response()->json(['results' => $rows]);
    }

    public function data(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'date_from'  => ['nullable', 'date'],
            'date_to'    => ['nullable', 'date'],
        ]);

        $projectId = (int) $request->project_id;
        $dateFrom  = $request->date_from ?: null;
        $dateTo    = $request->date_to ?: null;

        $project = Project::query()
            ->where('company_id', $companyId)
            ->where('id', $projectId)
            ->firstOrFail();

        $budget = ProjectBudget::query()
            ->where('company_id', $companyId)
            ->where('project_id', $projectId)
            ->whereIn('status', ['approved', 'revised', 'closed'])
            ->orderByDesc('version_no')
            ->orderByDesc('id')
            ->first();

        $budgetAmount = (float) ($budget->total_budget_amount ?? 0);

        $costQuery = DB::table('project_costs')
            ->where('company_id', $companyId)
            ->where('project_id', $projectId)
            ->whereNull('deleted_at')
            ->where('status', 'posted');

        if ($dateFrom) {
            $costQuery->whereDate('cost_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $costQuery->whereDate('cost_date', '<=', $dateTo);
        }

        $actualCost = round((float) (clone $costQuery)->sum('amount'), 2);

        $labourCost = round((float) (clone $costQuery)
            ->where('cost_category', 'labour')
            ->sum('amount'), 2);

        $nonLabourCost = round($actualCost - $labourCost, 2);

        $costByCategory = (clone $costQuery)
            ->selectRaw('cost_category, COALESCE(SUM(amount),0) as total_amount')
            ->groupBy('cost_category')
            ->orderByDesc('total_amount')
            ->get()
            ->map(function ($row) use ($actualCost) {
                $amount = (float) $row->total_amount;
                return [
                    'category' => ucfirst(str_replace('_', ' ', (string) $row->cost_category)),
                    'amount'   => round($amount, 2),
                    'percent'  => $actualCost > 0 ? round(($amount / $actualCost) * 100, 2) : 0,
                ];
            })->values();

        $timesheetQuery = DB::table('project_timesheets')
            ->where('company_id', $companyId)
            ->where('project_id', $projectId)
            ->whereNull('deleted_at');

        if ($dateFrom) {
            $timesheetQuery->whereDate('entry_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $timesheetQuery->whereDate('entry_date', '<=', $dateTo);
        }

        $totalHours = round((float) (clone $timesheetQuery)->sum('hours'), 2);
        $billableHours = round((float) (clone $timesheetQuery)->sum('billable_hours'), 2);
        $nonBillableHours = round($totalHours - $billableHours, 2);

        $recognisedRevenue = round((float) (clone $timesheetQuery)->sum('billable_amount'), 2);

        $billedRevenue = 0.00;
        if (SchemaHasTableAndColumn('sales_invoices', 'project_id') && SchemaHasTableAndColumn('sales_invoices', 'status')) {
            $invoiceQuery = DB::table('sales_invoices')
                ->where('company_id', $companyId)
                ->where('project_id', $projectId)
                ->whereNull('deleted_at')
                ->whereIn('status', ['posted', 'paid', 'part_paid']);

            if ($dateFrom && SchemaHasTableAndColumn('sales_invoices', 'invoice_date')) {
                $invoiceQuery->whereDate('invoice_date', '>=', $dateFrom);
            }
            if ($dateTo && SchemaHasTableAndColumn('sales_invoices', 'invoice_date')) {
                $invoiceQuery->whereDate('invoice_date', '<=', $dateTo);
            }

            $billedRevenue = round((float) $invoiceQuery->sum('total_amount'), 2);
        }

        $revenueForProfit = $billedRevenue > 0 ? $billedRevenue : $recognisedRevenue;
        $grossProfit = round($revenueForProfit - $actualCost, 2);
        $grossMargin = $revenueForProfit > 0 ? round(($grossProfit / $revenueForProfit) * 100, 2) : 0;

        $remainingBudget = round($budgetAmount - $actualCost, 2);
        $budgetVariance  = $remainingBudget;

        $budgetUsedPercent = $budgetAmount > 0 ? round(($actualCost / $budgetAmount) * 100, 2) : 0;
        $labourRatio = $actualCost > 0 ? round(($labourCost / $actualCost) * 100, 2) : 0;

        $durationDays = null;
        if ($dateFrom && $dateTo) {
            $start = \Carbon\Carbon::parse($dateFrom);
            $end   = \Carbon\Carbon::parse($dateTo);
            $durationDays = max(1, $start->diffInDays($end) + 1);
        } elseif (!empty($project->start_date) && !empty($project->end_date)) {
            $start = \Carbon\Carbon::parse($project->start_date);
            $end   = \Carbon\Carbon::parse($project->end_date);
            $durationDays = max(1, $start->diffInDays($end) + 1);
        }

        $burnRatePerDay = ($durationDays && $actualCost > 0)
            ? round($actualCost / $durationDays, 2)
            : 0;

        $tasksBase = DB::table('project_tasks')
            ->where('company_id', $companyId)
            ->where('project_id', $projectId)
            ->whereNull('deleted_at');

        $totalTasks = (clone $tasksBase)->count();
        $completedTasks = (clone $tasksBase)->whereIn('status', ['completed', 'closed'])->count();
        $taskCompletionPercent = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0;

        $milestonesBase = DB::table('project_milestones')
            ->where('company_id', $companyId)
            ->where('project_id', $projectId)
            ->whereNull('deleted_at');

        $totalMilestones = (clone $milestonesBase)->count();
        $completedMilestones = (clone $milestonesBase)->whereIn('status', ['completed', 'closed'])->count();
        $milestoneCompletionPercent = $totalMilestones > 0 ? round(($completedMilestones / $totalMilestones) * 100, 2) : 0;

        $health = $this->determineHealth(
            $budgetAmount,
            $budgetUsedPercent,
            $grossMargin,
            $taskCompletionPercent,
            $milestoneCompletionPercent
        );

        return response()->json([
            'project' => [
                'id'            => $project->id,
                'project_code'  => $project->project_code,
                'project_name'  => $project->project_name,
                'status'        => $project->status ?? null,
                'start_date'    => $project->start_date,
                'end_date'      => $project->end_date,
            ],
            'kpis' => [
                'budget_amount'                => $budgetAmount,
                'actual_cost'                  => $actualCost,
                'remaining_budget'             => $remainingBudget,
                'budget_variance'              => $budgetVariance,
                'budget_used_percent'          => $budgetUsedPercent,
                'labour_cost'                  => $labourCost,
                'non_labour_cost'              => $nonLabourCost,
                'labour_ratio_percent'         => $labourRatio,
                'billed_revenue'               => $billedRevenue,
                'recognised_revenue'           => $recognisedRevenue,
                'profit_revenue_basis'         => $revenueForProfit,
                'gross_profit'                 => $grossProfit,
                'gross_margin_percent'         => $grossMargin,
                'total_hours'                  => $totalHours,
                'billable_hours'               => $billableHours,
                'non_billable_hours'           => $nonBillableHours,
                'billable_ratio_percent'       => $totalHours > 0 ? round(($billableHours / $totalHours) * 100, 2) : 0,
                'burn_rate_per_day'            => $burnRatePerDay,
                'task_completion_percent'      => $taskCompletionPercent,
                'milestone_completion_percent' => $milestoneCompletionPercent,
                'total_tasks'                  => $totalTasks,
                'completed_tasks'              => $completedTasks,
                'total_milestones'             => $totalMilestones,
                'completed_milestones'         => $completedMilestones,
            ],
            'cost_by_category' => $costByCategory,
            'health' => $health,
        ]);
    }

    protected function determineHealth(
        float $budgetAmount,
        float $budgetUsedPercent,
        float $grossMargin,
        float $taskCompletionPercent,
        float $milestoneCompletionPercent
    ): array {
        $score = 0;

        if ($budgetAmount <= 0) {
            $score += 0;
        } elseif ($budgetUsedPercent <= 70) {
            $score += 35;
        } elseif ($budgetUsedPercent <= 90) {
            $score += 25;
        } elseif ($budgetUsedPercent <= 100) {
            $score += 15;
        } else {
            $score += 0;
        }

        if ($grossMargin >= 30) {
            $score += 30;
        } elseif ($grossMargin >= 15) {
            $score += 20;
        } elseif ($grossMargin >= 0) {
            $score += 10;
        } else {
            $score += 0;
        }

        $avgProgress = ($taskCompletionPercent + $milestoneCompletionPercent) / 2;

        if ($avgProgress >= 80) {
            $score += 35;
        } elseif ($avgProgress >= 50) {
            $score += 25;
        } elseif ($avgProgress >= 25) {
            $score += 15;
        } else {
            $score += 5;
        }

        if ($score >= 75) {
            return [
                'label' => 'Healthy',
                'class' => 'success',
                'score' => $score,
            ];
        }

        if ($score >= 45) {
            return [
                'label' => 'Watch',
                'class' => 'warning',
                'score' => $score,
            ];
        }

        return [
            'label' => 'Critical',
            'class' => 'danger',
            'score' => $score,
        ];
    }
}

if (!function_exists('SchemaHasTableAndColumn')) {
    function SchemaHasTableAndColumn(string $table, string $column): bool
    {
        try {
            return \Illuminate\Support\Facades\Schema::hasTable($table)
                && \Illuminate\Support\Facades\Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }
}