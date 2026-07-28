<?php
namespace Modules\HRM\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HRM\Models\Training;
use Modules\HRM\Models\Employee;
use DataTables;
use Illuminate\Validation\Rule;

class TrainingController extends Controller
{
    public function index()
    {
        $employees = Employee::select('id', 'first_name', 'last_name')->get();
        return view('hrm.employees.trainings.index', compact('employees'));
    }

    public function datatable()
    {
        $trainings = Training::latest()->get();

        return datatables()->of($trainings)
            ->addColumn('actions', function ($row) {
                return '
                    <button class="btn btn-sm btn-info edit-training" data-record=\''.json_encode($row).'\'><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-danger delete-training" data-id="'.$row->id.'"><i class="fas fa-trash-alt"></i></button>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'trainer'     => 'nullable|string|max:255',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'location'    => 'nullable|string|max:255',
            'status'      => ['required', Rule::in(['scheduled', 'completed', 'cancelled'])],
        ]);

        $training = Training::create($validated);

        return response()->json(['message' => 'Training created successfully.', 'data' => $training]);
    }

    public function edit(Training $training)
    {
        return response()->json(['data' => $training]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        Training::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Selected trainings deleted successfully.']);
    }

    public function update(Request $request, $id)
    {
        $training = Training::findOrFail($id);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'trainer'     => 'nullable|string|max:255',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'location'    => 'nullable|string|max:255',
            'status'      => ['required', Rule::in(['scheduled', 'completed', 'cancelled'])],
        ]);

        $training->update($validated);

        return response()->json(['message' => 'Training updated successfully.', 'data' => $training]);
    }


    public function destroy(Training $training)
    {
        $training->delete();

        return response()->json(['message' => 'Training deleted successfully.']);
    }
}
