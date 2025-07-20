<?php

namespace Modules\CRM\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CRM\Models\Activity;
use Modules\HRM\Models\Employee;

class ActivityController extends Controller
{
    public function index()
    {
        $employees = Employee::all();
        return view('crm.activities.index', compact('employees'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'activity_type' => 'required|in:call,meeting,email,task',
            'due_date' => 'required|date',
            'status' => 'required|in:pending,completed,overdue',
            'owner_id' => 'required|exists:employees,id',
            'related_type' => 'nullable|string',
            'related_id' => 'nullable|integer',
        ]);

        Activity::create($data);

        return response()->json(['message' => 'Activity created successfully']);
    }

    public function update(Request $request, $id)
    {
        $activity = Activity::findOrFail($id);

        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'activity_type' => 'required|in:call,meeting,email,task',
            'due_date' => 'required|date',
            'status' => 'required|in:pending,completed,overdue',
            'owner_id' => 'required|exists:employees,id',
            'related_type' => 'nullable|string',
            'related_id' => 'nullable|integer',
        ]);

        $activity->update($data);

        return response()->json(['message' => 'Activity updated successfully']);
    }

    public function destroy($id)
    {
        Activity::findOrFail($id)->delete();
        return response()->json(['message' => 'Activity deleted']);
    }

    public function bulkDelete(Request $request)
    {
        Activity::whereIn('id', $request->ids)->delete();
        return response()->json(['message' => 'Selected activities deleted']);
    }

    public function datatable()
    {
        $activities = Activity::with(['owner'])->get();

        return datatables()->of($activities)
            ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->addColumn('owner', fn($row) => $row->owner?->first_name . ' ' . $row->owner?->last_name)
            ->addColumn('due_date', fn($row) => $row->due_date ? date('d-m-Y', strtotime($row->due_date)) : date('d-m-Y'))
            ->addColumn('related_to', function ($row) {
                return $row->related_type ? class_basename($row->related_type) . ' #' . $row->related_id : '-';
            })
            ->addColumn('actions', function($row){
                $record = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                return '<button class="btn btn-sm btn-info edit-activity" data-record="' . $record . '">Edit</button>
                        <button class="btn btn-sm btn-danger delete-activity" data-id="'.$row->id.'">Delete</button>';
            })
            ->rawColumns(['checkbox', 'actions'])
            ->make(true);
    }
}
