<?php

namespace Modules\Projects\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\ProjectInvoice;
use Modules\Projects\Models\ProjectInvoiceLine;
use Modules\Projects\Models\ProjectMilestone;
use Modules\Projects\Models\ProjectTask;
use Modules\Projects\Models\ProjectTimesheet;

class ProjectInvoiceController extends Controller
{
    public function index()
    {
        return view('projects.invoices.index');
    }

    public function datatable(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $query = ProjectInvoice::query()
            ->with(['project:id,project_code,project_name'])
            ->where('company_id', $companyId)
            ->when($request->filled('project_id'), fn($q) => $q->where('project_id', $request->project_id))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('billing_method'), fn($q) => $q->where('billing_method', $request->billing_method))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('invoice_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('invoice_date', '<=', $request->date_to))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim((string) $request->q);
                $q->where(function ($sub) use ($term) {
                    $sub->where('invoice_no', 'like', "%{$term}%")
                        ->orWhere('reference', 'like', "%{$term}%")
                        ->orWhere('memo', 'like', "%{$term}%")
                        ->orWhereHas('project', function ($p) use ($term) {
                            $p->where('project_code', 'like', "%{$term}%")
                              ->orWhere('project_name', 'like', "%{$term}%");
                        });
                });
            });

        $start  = (int) ($request->start ?? 0);
        $length = (int) ($request->length ?? 10);
        $draw   = (int) ($request->draw ?? 1);

        $recordsTotal = (clone $query)->count();

        $rows = (clone $query)
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->offset($start)
            ->limit($length)
            ->get();

        $data = $rows->map(function ($row) {
            $statusBadge = match ($row->status) {
                'posted'    => '<span class="badge bg-success">POSTED</span>',
                'part_paid' => '<span class="badge bg-warning text-dark">PART PAID</span>',
                'paid'      => '<span class="badge bg-primary">PAID</span>',
                'voided'    => '<span class="badge bg-dark">VOIDED</span>',
                default     => '<span class="badge bg-secondary">DRAFT</span>',
            };

            $json = [
                'id'             => $row->id,
                'project_id'     => $row->project_id,
                'project_label'  => ($row->project->project_code ?? '') . ' - ' . ($row->project->project_name ?? ''),
                'invoice_no'     => $row->invoice_no,
                'invoice_date'   => optional($row->invoice_date)->format('Y-m-d'),
                'due_date'       => optional($row->due_date)->format('Y-m-d'),
                'billing_method' => $row->billing_method,
                'currency_code'  => $row->currency_code,
                'fx_rate'        => (float) $row->fx_rate,
                'reference'      => $row->reference,
                'memo'           => $row->memo,
                'status'         => $row->status,
            ];

            $actions = view('projects.invoices.partials.actions', [
                'row'  => $row,
                'json' => $json,
            ])->render();

            return [
                'id'             => $row->id,
                'project'        => e(($row->project->project_code ?? '') . ' - ' . ($row->project->project_name ?? '')),
                'invoice_no'     => e($row->invoice_no ?? '—'),
                'invoice_date'   => e(optional($row->invoice_date)->format('d-m-Y') ?: '—'),
                'due_date'       => e(optional($row->due_date)->format('d-m-Y') ?: '—'),
                'billing_method' => e(ucwords(str_replace('_', ' ', $row->billing_method))),
                'currency_code'  => e($row->currency_code ?? 'NGN'),
                'total_amount'   => number_format((float) $row->total_amount, 2),
                'balance_due'    => number_format((float) $row->balance_due, 2),
                'status'         => $statusBadge,
                'actions'        => $actions,
            ];
        })->values();

        $summary = ProjectInvoice::query()
            ->where('company_id', $companyId)
            ->when($request->filled('project_id'), fn($q) => $q->where('project_id', $request->project_id))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('billing_method'), fn($q) => $q->where('billing_method', $request->billing_method))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('invoice_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('invoice_date', '<=', $request->date_to));

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => $data,
            'meta' => [
                'subtotal'    => round((float) (clone $summary)->sum('subtotal'), 2),
                'tax_total'   => round((float) (clone $summary)->sum('tax_total'), 2),
                'total_amount'=> round((float) (clone $summary)->sum('total_amount'), 2),
                'balance_due' => round((float) (clone $summary)->sum('balance_due'), 2),
            ],
        ]);
    }

    public function lines($invoiceId)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $invoice = ProjectInvoice::where('company_id', $companyId)->findOrFail($invoiceId);

        $lines = ProjectInvoiceLine::query()
            ->where('project_invoice_id', $invoice->id)
            ->orderBy('id')
            ->get()
            ->map(function ($row) {
                return [
                    'id'           => $row->id,
                    'task_id'      => $row->task_id,
                    'milestone_id' => $row->milestone_id,
                    'timesheet_id' => $row->timesheet_id,
                    'source_type'  => $row->source_type,
                    'source_id'    => $row->source_id,
                    'description'  => $row->description,
                    'quantity'     => (float) $row->quantity,
                    'unit_price'   => (float) $row->unit_price,
                    'tax_rate'     => (float) $row->tax_rate,
                    'tax_amount'   => (float) $row->tax_amount,
                    'line_total'   => (float) $row->line_total,
                ];
            })->values();

        return response()->json(['lines' => $lines]);
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $data = $this->validateInvoice($request);

        return DB::transaction(function () use ($companyId, $data) {
            $totals = $this->computeTotals($data['lines']);

            $invoice = ProjectInvoice::create([
                'company_id'      => $companyId,
                'project_id'      => $data['project_id'],
                'customer_id'     => $data['customer_id'] ?? null,
                'invoice_no'      => $data['invoice_no'] ?? null,
                'invoice_date'    => $data['invoice_date'],
                'due_date'        => $data['due_date'] ?? null,
                'billing_method'  => $data['billing_method'],
                'currency_code'   => $data['currency_code'] ?? 'NGN',
                'fx_rate'         => $data['fx_rate'] ?? 1,
                'reference'       => $data['reference'] ?? null,
                'memo'            => $data['memo'] ?? null,
                'subtotal'        => $totals['subtotal'],
                'tax_total'       => $totals['tax_total'],
                'total_amount'    => $totals['total_amount'],
                'amount_paid'     => 0,
                'balance_due'     => $totals['total_amount'],
                'status'          => 'draft',
                'created_by'      => auth()->id(),
                'updated_by'      => auth()->id(),
            ]);

            $this->syncLines($invoice, $data['lines']);

            return response()->json([
                'message' => 'Project invoice created successfully.',
                'id'      => $invoice->id,
            ]);
        });
    }

    public function update(Request $request, $id)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $invoice = ProjectInvoice::where('company_id', $companyId)->findOrFail($id);

        if ($invoice->status !== 'draft') {
            return response()->json(['message' => 'Only draft invoices can be edited.'], 422);
        }

        $data = $this->validateInvoice($request);

        return DB::transaction(function () use ($invoice, $data) {
            $totals = $this->computeTotals($data['lines']);

            $this->releaseInvoiceSources($invoice);

            $invoice->update([
                'project_id'      => $data['project_id'],
                'customer_id'     => $data['customer_id'] ?? null,
                'invoice_no'      => $data['invoice_no'] ?? null,
                'invoice_date'    => $data['invoice_date'],
                'due_date'        => $data['due_date'] ?? null,
                'billing_method'  => $data['billing_method'],
                'currency_code'   => $data['currency_code'] ?? 'NGN',
                'fx_rate'         => $data['fx_rate'] ?? 1,
                'reference'       => $data['reference'] ?? null,
                'memo'            => $data['memo'] ?? null,
                'subtotal'        => $totals['subtotal'],
                'tax_total'       => $totals['tax_total'],
                'total_amount'    => $totals['total_amount'],
                'balance_due'     => round($totals['total_amount'] - (float) $invoice->amount_paid, 2),
                'updated_by'      => auth()->id(),
            ]);

            $this->syncLines($invoice, $data['lines']);

            return response()->json([
                'message' => 'Project invoice updated successfully.',
            ]);
        });
    }

    public function destroy($id)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $invoice = ProjectInvoice::where('company_id', $companyId)->findOrFail($id);

        if ($invoice->status !== 'draft') {
            return response()->json(['message' => 'Only draft invoices can be deleted.'], 422);
        }

        DB::transaction(function () use ($invoice) {
            $this->releaseInvoiceSources($invoice);
            ProjectInvoiceLine::where('project_invoice_id', $invoice->id)->delete();
            $invoice->delete();
        });

        return response()->json([
            'message' => 'Project invoice deleted successfully.',
        ]);
    }

    public function post($id)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $invoice = ProjectInvoice::where('company_id', $companyId)->findOrFail($id);

        if ($invoice->status !== 'draft') {
            return response()->json(['message' => 'Only draft invoices can be posted.'], 422);
        }

        DB::transaction(function () use ($invoice) {
            $invoice->update([
                'status'    => 'posted',
                'posted_at' => now(),
                'posted_by' => auth()->id(),
                'updated_by'=> auth()->id(),
            ]);
        });

        return response()->json([
            'message' => 'Project invoice posted successfully.',
        ]);
    }

    public function void($id)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $invoice = ProjectInvoice::where('company_id', $companyId)->findOrFail($id);

        if (!in_array($invoice->status, ['posted', 'part_paid'])) {
            return response()->json(['message' => 'Only posted or part-paid invoices can be voided.'], 422);
        }

        DB::transaction(function () use ($invoice) {
            $this->releaseInvoiceSources($invoice);

            $invoice->update([
                'status'    => 'voided',
                'voided_at' => now(),
                'voided_by' => auth()->id(),
                'updated_by'=> auth()->id(),
            ]);
        });

        return response()->json([
            'message' => 'Project invoice voided successfully.',
        ]);
    }

    public function lookupProjects(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $q = trim((string) $request->get('q', ''));

        $rows = Project::query()
            ->where('company_id', $companyId)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('project_code', 'like', "%{$q}%")
                        ->orWhere('project_name', 'like', "%{$q}%");
                });
            })
            ->orderBy('project_name')
            ->limit(30)
            ->get()
            ->map(fn($p) => [
                'id'   => $p->id,
                'text' => trim(($p->project_code ?? '') . ' - ' . ($p->project_name ?? '')),
            ]);

        return response()->json(['results' => $rows]);
    }

    public function lookupTasks(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $projectId = $request->get('project_id');
        $q = trim((string) $request->get('q', ''));

        $rows = ProjectTask::query()
            ->where('company_id', $companyId)
            ->when($projectId, fn($query) => $query->where('project_id', $projectId))
            ->when($q !== '', fn($query) => $query->where('task_name', 'like', "%{$q}%"))
            ->orderBy('task_name')
            ->limit(30)
            ->get()
            ->map(fn($t) => [
                'id'   => $t->id,
                'text' => trim(($t->task_code ?? '') . ' - ' . ($t->task_name ?? '')),
            ]);

        return response()->json(['results' => $rows]);
    }

    public function lookupMilestones(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $projectId = $request->get('project_id');
        $q = trim((string) $request->get('q', ''));

        $rows = ProjectMilestone::query()
            ->where('company_id', $companyId)
            ->when($projectId, fn($query) => $query->where('project_id', $projectId))
            ->when($q !== '', fn($query) => $query->where('milestone_name', 'like', "%{$q}%"))
            ->when(Schema::hasColumn('project_milestones', 'is_billable'), fn($query) => $query->where('is_billable', 1))
            ->when(Schema::hasColumn('project_milestones', 'is_invoiced'), fn($query) => $query->where('is_invoiced', 0))
            ->orderBy('milestone_name')
            ->limit(30)
            ->get()
            ->map(fn($m) => [
                'id'             => $m->id,
                'text'           => trim(($m->milestone_code ?? '') . ' - ' . ($m->milestone_name ?? '')),
                'billing_amount' => (float) ($m->billing_amount ?? 0),
            ]);

        return response()->json(['results' => $rows]);
    }

    public function lookupBillableTimesheets(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $projectId = $request->get('project_id');
        $q = trim((string) $request->get('q', ''));

        $rows = ProjectTimesheet::query()
            ->where('company_id', $companyId)
            ->when($projectId, fn($query) => $query->where('project_id', $projectId))
            ->where('is_billable', 1)
            ->whereIn('status', ['approved'])
            ->when(Schema::hasColumn('project_timesheets', 'is_invoiced'), fn($query) => $query->where('is_invoiced', 0))
            ->when($q !== '', fn($query) => $query->where('description', 'like', "%{$q}%"))
            ->orderByDesc('entry_date')
            ->limit(30)
            ->get()
            ->map(function ($t) {
                return [
                    'id'              => $t->id,
                    'text'            => 'TS-' . $t->id . ' | ' . $t->entry_date . ' | ' . number_format((float) $t->billable_hours, 2) . ' hrs',
                    'billable_hours'  => (float) $t->billable_hours,
                    'billing_rate'    => (float) $t->billing_rate,
                    'billable_amount' => (float) $t->billable_amount,
                    'description'     => $t->description,
                ];
            });

        return response()->json(['results' => $rows]);
    }

    protected function validateInvoice(Request $request): array
    {
        return Validator::make($request->all(), [
            'project_id'      => ['required', 'integer', 'exists:projects,id'],
            'customer_id'     => ['nullable', 'integer'],
            'invoice_no'      => ['nullable', 'string', 'max:50'],
            'invoice_date'    => ['required', 'date'],
            'due_date'        => ['nullable', 'date'],
            'billing_method'  => ['required', 'in:fixed_fee,milestone,timesheet,manual,mixed'],
            'currency_code'   => ['nullable', 'string', 'size:3'],
            'fx_rate'         => ['nullable', 'numeric', 'min:0.000001'],
            'reference'       => ['nullable', 'string', 'max:100'],
            'memo'            => ['nullable', 'string'],
            'lines'           => ['required', 'array', 'min:1'],
            'lines.*.task_id'      => ['nullable', 'integer', 'exists:project_tasks,id'],
            'lines.*.milestone_id' => ['nullable', 'integer', 'exists:project_milestones,id'],
            'lines.*.timesheet_id' => ['nullable', 'integer', 'exists:project_timesheets,id'],
            'lines.*.source_type'  => ['required', 'in:manual,milestone,timesheet,fixed_fee'],
            'lines.*.source_id'    => ['nullable', 'integer'],
            'lines.*.description'  => ['nullable', 'string', 'max:255'],
            'lines.*.quantity'     => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price'   => ['required', 'numeric', 'min:0'],
            'lines.*.tax_rate'     => ['nullable', 'numeric', 'min:0'],
        ])->validate();
    }

    protected function computeTotals(array $lines): array
    {
        $subtotal = 0.00;
        $taxTotal = 0.00;
        $total = 0.00;

        foreach ($lines as $line) {
            $qty = (float) ($line['quantity'] ?? 0);
            $unit = (float) ($line['unit_price'] ?? 0);
            $taxRate = (float) ($line['tax_rate'] ?? 0);

            $base = $qty * $unit;
            $tax = $base * ($taxRate / 100);
            $lineTotal = $base + $tax;

            $subtotal += $base;
            $taxTotal += $tax;
            $total += $lineTotal;
        }

        return [
            'subtotal'     => round($subtotal, 2),
            'tax_total'    => round($taxTotal, 2),
            'total_amount' => round($total, 2),
        ];
    }

    protected function syncLines(ProjectInvoice $invoice, array $lines): void
    {
        ProjectInvoiceLine::where('project_invoice_id', $invoice->id)->delete();

        $rows = [];

        foreach ($lines as $line) {
            $qty = (float) ($line['quantity'] ?? 0);
            $unit = (float) ($line['unit_price'] ?? 0);
            $taxRate = (float) ($line['tax_rate'] ?? 0);

            $base = $qty * $unit;
            $tax = $base * ($taxRate / 100);
            $lineTotal = $base + $tax;

            $rows[] = [
                'company_id'         => $invoice->company_id,
                'project_invoice_id' => $invoice->id,
                'project_id'         => $invoice->project_id,
                'task_id'            => $line['task_id'] ?? null,
                'milestone_id'       => $line['milestone_id'] ?? null,
                'timesheet_id'       => $line['timesheet_id'] ?? null,
                'source_type'        => $line['source_type'],
                'source_id'          => $line['source_id'] ?? null,
                'description'        => $line['description'] ?? null,
                'quantity'           => $qty,
                'unit_price'         => $unit,
                'tax_rate'           => $taxRate,
                'tax_amount'         => round($tax, 2),
                'line_total'         => round($lineTotal, 2),
                'created_at'         => now(),
                'updated_at'         => now(),
            ];
        }

        if (!empty($rows)) {
            ProjectInvoiceLine::insert($rows);
        }

        $this->markInvoiceSources($invoice);
    }

    protected function markInvoiceSources(ProjectInvoice $invoice): void
    {
        $lines = ProjectInvoiceLine::where('project_invoice_id', $invoice->id)->get();

        foreach ($lines as $line) {
            if ($line->source_type === 'milestone' && $line->milestone_id) {
                $update = [];
                if (Schema::hasColumn('project_milestones', 'is_invoiced')) {
                    $update['is_invoiced'] = 1;
                }
                if (Schema::hasColumn('project_milestones', 'invoiced_amount')) {
                    $update['invoiced_amount'] = DB::raw('COALESCE(invoiced_amount,0) + ' . (float) $line->line_total);
                }
                if (!empty($update)) {
                    DB::table('project_milestones')->where('id', $line->milestone_id)->update($update);
                }
            }

            if ($line->source_type === 'timesheet' && $line->timesheet_id) {
                $update = [];
                if (Schema::hasColumn('project_timesheets', 'is_invoiced')) {
                    $update['is_invoiced'] = 1;
                }
                if (Schema::hasColumn('project_timesheets', 'invoiced_hours')) {
                    $update['invoiced_hours'] = DB::raw('COALESCE(invoiced_hours,0) + ' . (float) $line->quantity);
                }
                if (Schema::hasColumn('project_timesheets', 'invoiced_amount')) {
                    $update['invoiced_amount'] = DB::raw('COALESCE(invoiced_amount,0) + ' . (float) $line->line_total);
                }
                if (!empty($update)) {
                    DB::table('project_timesheets')->where('id', $line->timesheet_id)->update($update);
                }
            }
        }
    }

    protected function releaseInvoiceSources(ProjectInvoice $invoice): void
    {
        $lines = ProjectInvoiceLine::where('project_invoice_id', $invoice->id)->get();

        foreach ($lines as $line) {
            if ($line->source_type === 'milestone' && $line->milestone_id) {
                if (Schema::hasColumn('project_milestones', 'is_invoiced') || Schema::hasColumn('project_milestones', 'invoiced_amount')) {
                    $milestone = DB::table('project_milestones')->where('id', $line->milestone_id)->first();
                    if ($milestone) {
                        $newAmount = max(0, ((float) ($milestone->invoiced_amount ?? 0)) - (float) $line->line_total);
                        $update = [];
                        if (Schema::hasColumn('project_milestones', 'invoiced_amount')) {
                            $update['invoiced_amount'] = $newAmount;
                        }
                        if (Schema::hasColumn('project_milestones', 'is_invoiced')) {
                            $update['is_invoiced'] = $newAmount > 0 ? 1 : 0;
                        }
                        DB::table('project_milestones')->where('id', $line->milestone_id)->update($update);
                    }
                }
            }

            if ($line->source_type === 'timesheet' && $line->timesheet_id) {
                $timesheet = DB::table('project_timesheets')->where('id', $line->timesheet_id)->first();
                if ($timesheet) {
                    $newHours = max(0, ((float) ($timesheet->invoiced_hours ?? 0)) - (float) $line->quantity);
                    $newAmount = max(0, ((float) ($timesheet->invoiced_amount ?? 0)) - (float) $line->line_total);

                    $update = [];
                    if (Schema::hasColumn('project_timesheets', 'invoiced_hours')) {
                        $update['invoiced_hours'] = $newHours;
                    }
                    if (Schema::hasColumn('project_timesheets', 'invoiced_amount')) {
                        $update['invoiced_amount'] = $newAmount;
                    }
                    if (Schema::hasColumn('project_timesheets', 'is_invoiced')) {
                        $update['is_invoiced'] = ($newHours > 0 || $newAmount > 0) ? 1 : 0;
                    }

                    DB::table('project_timesheets')->where('id', $line->timesheet_id)->update($update);
                }
            }
        }
    }
}