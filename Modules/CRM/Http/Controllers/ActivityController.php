<?php

namespace Modules\CRM\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CRM\Models\Activity;
use Modules\HRM\Models\Employee;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ActivityController extends Controller
{
    public function index()
    {
        // for filters + modal selects
        $employees = Employee::select('id','first_name','last_name')->orderBy('first_name')->get();

        return view('crm.activities.index', compact('employees'));
    }

    public function datatable(Request $request)
    {
        $q = Activity::query()
            ->with([
                'owner:id,first_name,last_name',
                'creator:id,name',
                'updater:id,name',
            ])
            ->select('activities.*')
            ->latest('activities.id');

        // Filters (optional but useful)
        if ($request->filled('owner_id')) $q->where('owner_id', $request->owner_id);
        if ($request->filled('status')) $q->where('status', $request->status);
        if ($request->filled('activity_type')) $q->where('activity_type', $request->activity_type);

        if ($request->filled('due_from')) $q->whereDate('due_date', '>=', $request->due_from);
        if ($request->filled('due_to'))   $q->whereDate('due_date', '<=', $request->due_to);

        return DataTables::of($q)
            ->addColumn('checkbox', fn($row) =>
                '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')

            ->addColumn('owner', fn($row) =>
                trim(($row->owner?->first_name ?? '').' '.($row->owner?->last_name ?? '')) ?: '—')

            ->addColumn('due_date', fn($row) =>
                $row->due_date ? $row->due_date->format('d-m-Y') : '—')

            ->addColumn('created_by', fn($row) => $row->creator?->name ?? '—')
            ->addColumn('updated_by', fn($row) => $row->updater?->name ?? '—')

            ->addColumn('related_to', function ($row) {
                return $row->related_type ? class_basename($row->related_type).' #'.$row->related_id : '—';
            })

            ->addColumn('actions', function ($row) {
                $record = htmlspecialchars(json_encode([
                    'id' => $row->id,
                    'subject' => $row->subject,
                    'description' => $row->description,
                    'activity_type' => $row->activity_type,
                    'due_date' => optional($row->due_date)->format('Y-m-d'),
                    'status' => $row->status,
                    'owner_id' => $row->owner_id,
                    'related_type' => $row->related_type,
                    'related_id' => $row->related_id,
                ]), ENT_QUOTES, 'UTF-8');

                $btn = '';

                if (auth()->user()->can('crm.activities.edit')) {
                    $btn .= '<button class="btn btn-sm btn-info edit-activity" data-record="'.$record.'">Edit</button> ';
                }
                if (auth()->user()->can('crm.activities.delete')) {
                    $btn .= '<button class="btn btn-sm btn-danger delete-activity" data-id="'.$row->id.'">Delete</button>';
                }

                return $btn ?: '—';
            })

            ->rawColumns(['checkbox','actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject'       => ['required','string','max:255'],
            'description'   => ['nullable','string'],
            'activity_type' => ['required', Rule::in(['call','meeting','email','task'])],
            'due_date'      => ['required','date'],
            'status'        => ['required', Rule::in(['pending','completed','overdue'])],
            'owner_id'      => ['required','exists:employees,id'],
            'related_type'  => ['nullable','string'],
            'related_id'    => ['nullable','integer'],
        ]);

        $activity = Activity::create($data); // model booted() fills created_by/updated_by

        $this->audit(
            action: 'created',
            description: 'Created activity: '.$activity->subject,
            subject: $activity,
            meta: [
                'id' => $activity->id,
                'subject' => $activity->subject,
                'activity_type' => $activity->activity_type,
                'status' => $activity->status,
                'due_date' => optional($activity->due_date)->toDateString(),
                'owner_id' => $activity->owner_id,
                'related_type' => $activity->related_type,
                'related_id' => $activity->related_id,
                'created_by' => $activity->created_by,
            ]
        );

        return response()->json(['message' => 'Activity created successfully', 'data' => $activity]);
    }

    public function update(Request $request, Activity $activity)
    {
        $data = $request->validate([
            'subject'       => ['required','string','max:255'],
            'description'   => ['nullable','string'],
            'activity_type' => ['required', Rule::in(['call','meeting','email','task'])],
            'due_date'      => ['required','date'],
            'status'        => ['required', Rule::in(['pending','completed','overdue'])],
            'owner_id'      => ['required','exists:employees,id'],
            'related_type'  => ['nullable','string'],
            'related_id'    => ['nullable','integer'],
        ]);

        $before = $activity->only([
            'subject','description','activity_type','due_date','status','owner_id','related_type','related_id',
            'created_by','updated_by','status_changed_at'
        ]);

        $activity->update($data); // model sets updated_by + status_changed_at when status changes

        $after = $activity->fresh()->only([
            'subject','description','activity_type','due_date','status','owner_id','related_type','related_id',
            'created_by','updated_by','status_changed_at'
        ]);

        $this->audit(
            action: 'updated',
            description: 'Updated activity: '.$activity->subject,
            subject: $activity,
            meta: [
                'id' => $activity->id,
                'before' => $before,
                'after' => $after,
            ]
        );

        return response()->json(['message' => 'Activity updated successfully', 'data' => $activity->fresh()]);
    }

    public function destroy(Activity $activity)
    {
        $meta = $activity->only([
            'id','subject','activity_type','status','due_date','owner_id','related_type','related_id','created_by','updated_by'
        ]);

        $activity->delete();

        $this->audit(
            action: 'deleted',
            description: 'Deleted activity: '.$meta['subject'],
            subject: null,
            meta: $meta
        );

        return response()->json(['message' => 'Activity deleted']);
    }

    public function bulkDelete(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required','array','min:1'],
            'ids.*' => ['integer','exists:activities,id'],
        ]);

        $rows = Activity::whereIn('id', $data['ids'])->get(['id','subject','status','activity_type','due_date']);

        Activity::whereIn('id', $data['ids'])->delete();

        $this->audit(
            action: 'bulk_deleted',
            description: 'Bulk deleted activities',
            subject: null,
            meta: [
                'count' => $rows->count(),
                'ids' => $rows->pluck('id')->all(),
                'items' => $rows->map(fn($r) => $r->toArray())->all(),
            ]
        );

        return response()->json(['message' => 'Selected activities deleted']);
    }

    private function audit(string $action, ?string $description = null, $subject = null, array $meta = []): void
    {
        $module = 'crm.activities';

        auth()->user()?->audit(
            module: $module,
            action: $action,
            description: $description,
            subject: $subject,
            meta: $meta
        );
    }
}
