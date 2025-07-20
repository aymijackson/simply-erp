<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HRM\Models\Performance;
use Modules\HRM\Models\Employee;
use Yajra\DataTables\DataTables;

class PerformanceController extends Controller
{
    public function index(Request $request)
    {
        $employees = Employee::all();
        return view('hrm.employees.performances.index', compact('employees'));
    }

    public function datatable()
    {
        $performances = Performance::with('employee')->latest();

        return datatables()->of($performances)
            ->addColumn('employee', fn($row) => $row->employee->full_name)
            ->addColumn('reviewed_by', fn($row) => $row->reviewer->full_name ?? 'N/A')
            ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->addColumn('actions', function ($row) {
                $data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                return '
                    <button class="btn btn-sm btn-info edit-performance" data-record="'.$data.'"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-danger delete-performance" data-id="'.$row->id.'"><i class="fas fa-trash-alt"></i></button>
                ';
            })
            ->rawColumns(['checkbox', 'actions'])
            ->make(true);
    }


    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'goal_title' => 'required|string|max:255',
            'kpi_description' => 'nullable|string',
            'review_period' => 'nullable|string|max:255',
            'score' => 'nullable|numeric|between:0,100',
            'rating' => 'nullable|in:Excellent,Good,Satisfactory,Needs Improvement',
            'comments' => 'nullable|string',
            'review_date' => 'required|date',
            'reviewed_by' => 'required|exists:employees,id',
        ]);

        $performance = Performance::updateOrCreate(
                $request->only([
                'employee_id', 'goal_title', 'kpi_description', 'review_period',
                'score', 'rating', 'comments', 'review_date', 'reviewed_by'
            ])
        );

        return response()->json(['success' => 'Saved successfully.', 'data' => $performance]);
    }

    public function update(Request $request, $id)
    {
        $performance = Performance::findOrFail($id);

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'goal_title' => 'required|string|max:255',
            'kpi_description' => 'nullable|string',
            'review_period' => 'nullable|string|max:255',
            'score' => 'nullable|numeric|between:0,100',
            'rating' => 'nullable|in:Excellent,Good,Satisfactory,Needs Improvement',
            'comments' => 'nullable|string',
            'review_date' => 'required|date',
            'reviewed_by' => 'required|exists:employees,id',
        ]);

        $performance->update($validated);

        return response()->json(['message' => 'Performance review updated successfully.']);
    }

    public function destroy($id)
    {
        Performance::findOrFail($id)->delete();
        return response()->json(['success' => 'Deleted successfully.']);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        Performance::whereIn('id', $request->ids)->delete();
        return response()->json(['message' => 'Selected performance reviews deleted successfully.']);
    }

}
