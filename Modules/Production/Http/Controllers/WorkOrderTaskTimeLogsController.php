<?php
// Modules/Production/Http/Controllers/WorkOrderWorkOrderTaskTimeLogsController.php
namespace Modules\Production\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Modules\Production\Models\{ WorkorderTask, WorkOrderTaskTimeLog };
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class WorkOrderTaskTimeLogsController extends Controller
{
    private function companyId(Request $r): int
    {
        return (int) ($r->user()->company_id ?? 1);
    }

    // List logs for a task
    public function datatable(Request $r, WorkorderTask $task)
    {
        abort_unless($task->workOrder->company_id == $this->companyId($r), 404);

        $q = WorkOrderTaskTimeLog::with('employee')->where('work_order_task_id', $task->id)->orderByDesc('started_at');

        return DataTables::of($q)
            ->addIndexColumn()
            ->addColumn('emp_name', fn($r) => e(optional($r->employee)->first_name.' '.optional($r->employee)->last_name))
            ->addColumn('started_at', fn($r) => optional($r->started_at)->format('d M Y H:i'))
            ->addColumn('ended_at',   fn($r) => $r->ended_at ? $r->ended_at->format('d M Y H:i') : '<span class="badge bg-warning">running</span>')
            ->addColumn('minutes',    fn($r) => number_format((int)$r->minutes))
            ->addColumn('note',       fn($r) => e($r->note ?? ''))
            ->addColumn('actions', function ($r) {
                $payload = e(json_encode([
                    'id'=>$r->id,
                    'employee_id'=>$r->employee_id,
                    'started_at'=>$r->started_at? $r->started_at->format('Y-m-d\TH:i') : null,
                    'ended_at'=>$r->ended_at? $r->ended_at->format('Y-m-d\TH:i') : null,
                    'note'=>$r->note,
                ]));
                return '<div class="btn-group btn-group-sm">
                    <button class="btn btn-warning edit-log" data-record=\''.$payload.'\'><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger del-log" data-id="'.$r->id.'"><i class="fas fa-trash"></i></button>
                </div>';
            })
            ->rawColumns(['ended_at','actions'])
            ->toJson();
    }

    private function parseDt(?string $v): ?Carbon
    {
        if (!$v) return null;
        $v = trim($v);

        // 1) exact ISO local (what your payload sends)
        try { return Carbon::createFromFormat('Y-m-d\TH:i', $v); } catch (\Throwable $e) {}
        try { return Carbon::createFromFormat('Y-m-d\TH:i:s', $v); } catch (\Throwable $e) {}

        // 2) space instead of T
        try { return Carbon::createFromFormat('Y-m-d H:i', $v); } catch (\Throwable $e) {}
        try { return Carbon::createFromFormat('Y-m-d H:i:s', $v); } catch (\Throwable $e) {}

        // 3) last resort (very tolerant)
        try { return Carbon::parse($v); } catch (\Throwable $e) {}

        return null;
    }

    // Create manual log (useful for adjustments/backfills)
    public function store(Request $r, WorkOrderTask $task)
{
    abort_unless($task->workOrder->company_id == $this->companyId($r), 404);

    $v = Validator::make($r->all(), [
        'employee_id' => ['required','exists:employees,id'],
        'started_at'  => ['required','string'],
        'ended_at'    => ['nullable','string'],
        'minutes'     => ['nullable','integer'],
        'note'        => ['nullable','string','max:255'],
    ]);

    $v->after(function($validator) use ($r) {
        $start = $this->parseDt($r->input('started_at'));
        if (!$start) {
            $validator->errors()->add('started_at', 'Invalid start datetime.');
            return;
        }

        $end = $r->filled('ended_at') ? $this->parseDt($r->input('ended_at')) : null;
        if ($r->filled('ended_at') && !$end) {
            $validator->errors()->add('ended_at', 'Invalid end datetime.');
            return;
        }

        if ($end) {
            $signed = $start->diffInMinutes($end, false); // negative or zero = invalid
            if ($signed <= 0) {
                $validator->errors()->add('ended_at', 'End must be after start.');
                return;
            }

            if ($r->filled('minutes')) {
                $m = (int) $r->input('minutes');
                if ($m <= 0) {
                    $validator->errors()->add('minutes', 'Minutes must be a positive integer.');
                } elseif ($m !== $signed) {
                    $validator->errors()->add('minutes', "Minutes must equal {$signed} for the given range.");
                }
            } else {
                $r->merge(['__computed_minutes' => $signed]);
            }
        } else {
            if ($r->filled('minutes') && (int)$r->input('minutes') > 0) {
                $validator->errors()->add('minutes', 'Do not set minutes for a running log without an end time.');
            }
        }
    });

    $data = $v->validate();

    $start   = $this->parseDt($data['started_at']);
    $end     = $data['ended_at'] ? $this->parseDt($data['ended_at']) : null;
    $minutes = (int) ($r->input('__computed_minutes', $data['minutes'] ?? 0));

    DB::transaction(function () use ($task, $data, $start, $end, $minutes) {
        WorkOrderTaskTimeLog::create([
            'work_order_task_id' => $task->id,
            'employee_id'        => $data['employee_id'],
            'started_at'         => $start,
            'ended_at'           => $end,
            'minutes'            => $minutes,
            'note'               => $data['note'] ?? null,
        ]);
        $task->update(['actual_minutes' => $task->timeLogs()->sum('minutes')]);
    });

    return response()->json(['success' => true, 'message' => 'Log added']);
}

    public function update(Request $r, WorkOrderTaskTimeLog $log)
    {
        abort_unless($log->task->workOrder->company_id == $this->companyId($r), 404);

        $data = $r->validate([
            'employee_id' => 'required|exists:employees,id',
            'started_at'  => 'required|date',
            'ended_at'    => 'nullable|date|after:started_at',
            'minutes'     => 'nullable|integer|min:0',
            'note'        => 'nullable|string|max:255',
        ]);

        $minutes = $data['minutes'] ?? (
            isset($data['ended_at'])
                ? now()->parse($data['ended_at'])->diffInMinutes(now()->parse($data['started_at']))
                : 0
        );

        DB::transaction(function () use ($log, $data, $minutes) {
            $log->update([
                'employee_id' => $data['employee_id'],
                'started_at'  => $data['started_at'],
                'ended_at'    => $data['ended_at'] ?? null,
                'minutes'     => $minutes,
                'note'        => $data['note'] ?? null,
            ]);
            $log->task->update(['actual_minutes' => $log->task->timeLogs()->sum('minutes')]);
        });

        return response()->json(['success'=>true,'message'=>'Log updated']);
    }

    public function destroy(Request $r, WorkOrderTaskTimeLog $log)
    {
        abort_unless($log->task->workOrder->company_id == $this->companyId($r), 404);

        $task = $log->task;
        DB::transaction(function () use ($log, $task) {
            $log->delete();
            $task->update(['actual_minutes' => $task->timeLogs()->sum('minutes')]);
        });
        return response()->json(['success'=>true,'message'=>'Log deleted']);
    }
}
