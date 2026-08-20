<?php
// Modules/Production/Http/Controllers/WorkOrderTaskDependenciesController.php
namespace Modules\Production\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Modules\Production\Models\{ WorkorderTask, WorkOrderTaskDependency };

class WorkOrderTaskDependenciesController extends Controller
{
    private function companyId(Request $r): int
    {
        return (int) ($r->user()->company_id ?? 1);
    }

    // List dependencies for a task (for a small modal DT)
    public function datatable(Request $r, WorkorderTask $task)
    {
        abort_unless($task->workOrder->company_id == $this->companyId($r), 404);

        $q = WorkOrderTaskDependency::with(['dependsOn'])
            ->where('work_order_task_id', $task->id);

        return DataTables::of($q)
            ->addIndexColumn()
            ->addColumn('depends_on_title', fn($r) => e(optional($r->dependsOn)->title ?: '—'))
            ->addColumn('actions', fn($r) =>
                '<button class="btn btn-sm btn-danger del-dep" data-id="'.$r->id.'"><i class="fas fa-trash"></i></button>')
            ->rawColumns(['actions'])
            ->toJson();
    }

    // Create dependency (task depends on depends_on_task_id)
    public function store(Request $r, WorkorderTask $task)
    {
        abort_unless($task->workOrder->company_id == $this->companyId($r), 404);

        $data = $r->validate([
            'depends_on_task_id' => 'required|different:task_id|exists:work_order_tasks,id',
        ]);
        $depId = (int) $data['depends_on_task_id'];

        if ($depId === $task->id) {
            return response()->json(['success'=>false,'message'=>'Task cannot depend on itself'], 422);
        }

        // Prevent duplicates
        $exists = WorkOrderTaskDependency::where('work_order_task_id',$task->id)->where('depends_on_task_id',$depId)->exists();
        if ($exists) return response()->json(['success'=>true,'message'=>'Already added']);

        // Optional: simple cycle check (prevent reverse)
        $reverse = WorkOrderTaskDependency::where('work_order_task_id',$depId)->where('depends_on_task_id',$task->id)->exists();
        if ($reverse) return response()->json(['success'=>false,'message'=>'Circular dependency detected'], 422);

        WorkOrderTaskDependency::create([
            'work_order_task_id' => $task->id,
            'depends_on_task_id' => $depId,
        ]);

        return response()->json(['success'=>true,'message'=>'Dependency added']);
    }

    public function destroy(Request $r, WorkOrderTaskDependency $dependency)
    {
        abort_unless($dependency->task->workOrder->company_id == $this->companyId($r), 404);

        $dependency->delete();
        return response()->json(['success'=>true,'message'=>'Dependency removed']);
    }
}
