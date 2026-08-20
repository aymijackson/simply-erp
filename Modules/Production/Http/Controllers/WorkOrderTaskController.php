<?php
// Modules/Production/Http/Controllers/WorkOrderTaskWebController.php
namespace Modules\Production\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

use Modules\Production\Models\{
    WorkOrder,
    WorkOrderTask,
    WorkOrderTaskAssignment,
    WorkOrderTaskTimeLog,
    WorkOrderTaskChecklistItem
};
use Modules\HRM\Models\Employee;

class WorkOrderTaskController extends Controller
{
    private function companyId(Request $r): int
    {
        return (int) ($r->user()->company_id ?? 1);
    }

    public function workOrderTasksSelect2(WorkOrder $work_order, Request $r)
    {
        abort_unless($work_order->company_id == $this->companyId($r), 404);

        $q      = (string) $r->input('q', '');
        $except = (array) $r->input('except', []); // optional: exclude current task

        $tasks = \Modules\Production\Models\WorkOrderTask::query()
            ->where('work_order_id', $work_order->id)
            ->when($q, fn($qq) => $qq->where('title', 'like', "%{$q}%"))
            ->when($except, fn($qq) => $qq->whereNotIn('id', $except))
            ->orderBy('sequence_index')->orderBy('id')
            ->limit(20)
            ->get();

        // IMPORTANT: Select2 needs 'text'
        $results = $tasks->map(fn($t) => ['id' => $t->id, 'text' => $t->title]);

        return response()->json(['results' => $results, 'pagination' => ['more' => false]]);

    }

    /** DataTable: tasks for a work order */
    public function datatable(Request $r, $workOrderId)
    {
        $workOrder = WorkOrder::findOrFail($workOrderId);
        abort_unless($workOrder->company_id == $this->companyId($r), 404);

        $employeeId = optional(optional(auth()->user())->employee)->id ?? 3; // if you map users->employee

        $q = WorkOrderTask::with(['step', 'assignees', 'checklistItems'])
            ->where('work_order_id', $workOrderId)
            ->orderBy('sequence_index')
            ->orderBy('id');

        return DataTables::of($q)
            ->addIndexColumn()
            ->addColumn('title', fn($t) => e($t->title))
            ->addColumn('step_name', fn($t) => e(optional($t->step)->name ?: '—'))
            ->addColumn('assignees_html', function ($t) {
                if ($t->assignees->isEmpty()) return '—';
                return $t->assignees->map(fn($e) =>
                    '<span class="badge bg-secondary text-white me-1">'.e($e->first_name.' '.$e->last_name).'</span>'
                )->implode(' ');
            })
            ->addColumn('status_badge', function ($t) {
                $map = [
                    'pending'     => 'secondary',
                    'in_progress' => 'warning',
                    'paused'      => 'info',
                    'blocked'     => 'dark',
                    'completed'   => 'success',
                    'cancelled'   => 'danger',
                ];
                $c = $map[$t->status] ?? 'secondary';
                return '<span class="badge bg-'.$c.'  text-white">'.e(Str::headline($t->status)).'</span>';
            })
            ->addColumn('est_fmt', fn($t) => $t->estimated_minutes !== null ? number_format($t->estimated_minutes) : '—')
            ->addColumn('act_fmt', fn($t) => number_format((int)$t->actual_minutes))
            ->addColumn('due_fmt', fn($t) => $t->due_at ? $t->due_at->format('d M Y H:i') : '—')
            ->addColumn('progress_html', function ($t) {
                $pct = method_exists($t, 'getProgressPercentAttribute') ? $t->progress_percent : 0;
                return <<<HTML
<div class="progress" style="height: 8px;">
  <div class="progress-bar" role="progressbar" style="width: {$pct}%;" aria-valuenow="{$pct}" aria-valuemin="0" aria-valuemax="100"></div>
</div>
<small class="text-muted">{$pct}%</small>
HTML;
            })
            ->addColumn('actions', function ($t) use ($employeeId) {
                // payload for edit modal
                $payload = [
                    'id'                 => $t->id,
                    'title'              => $t->title,
                    'priority'           => $t->priority,
                    'work_order_step_id'  => $t->work_order_step_id,
                    'step_name'          => optional($t->step)->name,
                    'estimated_minutes'  => $t->estimated_minutes,
                    'due_at_local'       => $t->due_at ? $t->due_at->format('Y-m-d\TH:i') : null,
                    'description'        => $t->description,
                    'assignees'          => $t->assignees->map(fn($e)=> ['id'=>$e->id,'text'=>$e->first_name.' '.$e->last_name])->values(),
                    'checklist'          => $t->checklistItems->map(fn($c)=> ['label'=>$c->label])->values(),
                ];
                $json = e(json_encode($payload));

                // Buttons shown by status
                $buttons = [];
                if (in_array($t->status, ['pending','paused'])) {
                    $buttons[] = '<button class="btn btn-sm btn-primary task-start" data-id="'.$t->id.'" data-emp="'.(int)$employeeId.'"><i class="fas fa-play"></i></button>';
                }
                if ($t->status === 'in_progress') {
                    $buttons[] = '<button class="btn btn-sm btn-outline-secondary task-stop" data-id="'.$t->id.'" data-emp="'.(int)$employeeId.'"><i class="fas fa-stop"></i></button>';
                    $buttons[] = '<button class="btn btn-sm btn-success task-complete" data-id="'.$t->id.'" data-emp="'.(int)$employeeId.'"><i class="fas fa-check"></i></button>';
                }
                if ($t->status !== 'completed' && $t->status !== 'cancelled') {
                    $buttons[] = '<button class="btn btn-sm btn-warning edit-task" data-record=\''.$json.'\'><i class="fas fa-edit"></i></button>';
                }
                // Dependencies + Time Logs buttons
                $buttons[] = '<button class="btn btn-sm btn-outline-dark deps-btn" data-task="'.$t->id.'" title="Dependencies"><i class="fas fa-link"></i></button>';
                $buttons[] = '<button class="btn btn-sm btn-outline-dark logs-btn" data-task="'.$t->id.'" title="Time Logs"><i class="fas fa-clock"></i></button>';

                $buttons[] = '<button class="btn btn-sm btn-danger del-task" data-id="'.$t->id.'"><i class="fas fa-trash"></i></button>';

                return '<div class="btn-group btn-group-sm" role="group">'.implode('', $buttons).'</div>';
            })
            ->rawColumns(['assignees_html','status_badge','progress_html','actions'])
            ->toJson();
    }

    /** Create task under a work order */
    public function store(Request $r, $workOrderId)
    {
        $workOrder = WorkOrder::findOrFail($workOrderId);
        abort_unless($workOrder->company_id == $this->companyId($r), 404);

        $data = $r->validate([
            'title'             => 'required|string|max:255',
            'priority'          => 'nullable|in:low,normal,high,urgent',
            'work_order_step_id' => 'nullable|exists:work_order_steps,id',
            'estimated_minutes' => 'nullable|integer|min:0',
            'due_at'            => 'nullable|date',
            'description'       => 'nullable|string',
            'assignees'         => 'array',
            'assignees.*'       => 'integer|exists:employees,id',
            'checklist'         => 'array',
            'checklist.*'       => 'string|max:255',
        ]);

        return DB::transaction(function () use ($data, $workOrderId) {
            $task = WorkOrderTask::create([
                'work_order_id'      => $workOrderId,
                'work_order_step_id' => $data['work_order_step_id'] ?? null,
                'title'             => $data['title'],
                'priority'          => $data['priority'] ?? 'normal',
                'estimated_minutes' => $data['estimated_minutes'] ?? null,
                'due_at'            => $data['due_at'] ?? null,
                'description'       => $data['description'] ?? null,
                'status'            => 'pending',
                'sequence_index'    => (int) (WorkOrderTask::where('work_order_id',$workOrderId)->max('sequence_index') + 1),
            ]);

            // assignments
            if (!empty($data['assignees'])) {
                foreach ($data['assignees'] as $empId) {
                    WorkOrderTaskAssignment::create([
                        'work_order_task_id'     => $task->id,
                        'employee_id' => $empId,
                        'role'        => 'worker',
                    ]);
                }
            }

            // checklist
            if (!empty($data['checklist'])) {
                foreach ($data['checklist'] as $label) {
                    TaskChecklistItem::create(['work_order_task_id' => $task->id, 'label' => $label]);
                }
            }

            return response()->json(['success'=>true,'message'=>'Task created','id'=>$task->id]);
        });
    }

    /** Update a task */
    public function update(Request $r, WorkOrderTask $task)
    {
        abort_unless($task->workOrder->company_id == $this->companyId($r), 404);

        $data = $r->validate([
            'title'             => 'required|string|max:255',
            'priority'          => 'nullable|in:low,normal,high,urgent',
            'work_order_step_id' => 'nullable|exists:work_order_steps,id',
            'estimated_minutes' => 'nullable|integer|min:0',
            'due_at'            => 'nullable|date',
            'description'       => 'nullable|string',
            'assignees'         => 'array',
            'assignees.*'       => 'integer|exists:employees,id',
            'checklist'         => 'array',
            'checklist.*'       => 'string|max:255',
        ]);

        return DB::transaction(function () use ($data, $task) {
            $task->update([
                'title'             => $data['title'],
                'priority'          => $data['priority'] ?? $task->priority,
                'work_order_step_id' => $data['work_order_step_id'] ?? null,
                'estimated_minutes' => $data['estimated_minutes'] ?? null,
                'due_at'            => $data['due_at'] ?? null,
                'description'       => $data['description'] ?? null,
            ]);

            // resync assignments
            WorkOrderTaskAssignment::where('work_order_task_id', $task->id)->delete();
            if (!empty($data['assignees'])) {
                foreach ($data['assignees'] as $empId) {
                    WorkOrderTaskAssignment::create([
                        'work_order_task_id'     => $task->id,
                        'employee_id' => $empId,
                        'role'        => 'worker',
                    ]);
                }
            }

            // reset checklist (simple approach)
            TaskChecklistItem::where('work_order_task_id', $task->id)->delete();
            if (!empty($data['checklist'])) {
                foreach ($data['checklist'] as $label) {
                    TaskChecklistItem::create(['work_order_task_id' => $task->id, 'label' => $label]);
                }
            }

            return response()->json(['success'=>true,'message'=>'Task updated']);
        });
    }

    /** Delete a task */
    public function destroy(Request $r, WorkOrderTask $task)
    {
        abort_unless($task->workOrder->company_id == $this->companyId($r), 404);

        $task->delete();
        return response()->json(['success'=>true,'message'=>'Task deleted']);
    }

    /** Start timer on a task for an employee */
    public function start(Request $r, WorkOrderTask $task)
    {
        abort_unless($task->workOrder->company_id == $this->companyId($r), 404);

        $data = $r->validate(['employee_id' => 'required|exists:employees,id']);

        // Close any running timer for this employee on this task
        WorkOrderTaskTimeLog::where('work_order_task_id',$task->id)
            ->where('employee_id',$data['employee_id'])
            ->whereNull('ended_at')
            ->update([
                'ended_at' => now(),
                'minutes'  => DB::raw('TIMESTAMPDIFF(MINUTE, started_at, NOW())'),
            ]);

        WorkOrderTaskTimeLog::create([
            'work_order_task_id'     => $task->id,
            'employee_id' => $data['employee_id'],
            'started_at'  => now(),
        ]);

        if ($task->status === 'pending') {
            $task->update(['status'=>'in_progress', 'started_at'=>now()]);
        }

        return response()->json(['success'=>true,'message'=>'Timer started']);
    }

    /** Stop timer and recompute actual minutes */
    public function stop(Request $r, WorkOrderTask $task)
    {
        abort_unless($task->workOrder->company_id == $this->companyId($r), 404);

        $data = $r->validate(['employee_id' => 'required|exists:employees,id']);

        $log = WorkOrderTaskTimeLog::where('work_order_task_id',$task->id)
            ->where('employee_id',$data['employee_id'])
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        if (!$log) {
            return response()->json(['success'=>false,'message'=>'No running timer'], 422);
        }

        $log->update([
            'ended_at' => now(),
            'minutes'  => $log->started_at->diffInMinutes(now()),
        ]);

        $task->update(['actual_minutes' => $task->timeLogs()->sum('minutes')]);

        return response()->json(['success'=>true,'message'=>'Timer stopped']);
    }

    /** Complete task (requires all required checklist items checked) */
    public function complete(Request $r, WorkOrderTask $task)
    {
        abort_unless($task->workOrder->company_id == $this->companyId($r), 404);

        $openRequired = $task->checklistItems()->where('is_required', true)->where('is_checked', false)->count();
        if ($openRequired > 0) {
            return response()->json(['success'=>false,'message'=>'Complete required checklist items first.'], 422);
        }

        $task->update(['status'=>'completed', 'completed_at'=>now()]);
        return response()->json(['success'=>true,'message'=>'Task completed']);
    }
}
