<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Modules\CRM\Models\Note;
use Modules\HRM\Models\Employee;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class NotesController extends BaseController
{
    public function index()
    {
        $employees = Employee::select('id','first_name','last_name')->orderBy('first_name')->get();
        return view('crm.notes.index', compact('employees'));
    }

    /**
     * Select2: fetch notables for Lead/Opportunity (Customer uses CustomerController@select2)
     * Expected params:
     *  - type: Modules\CRM\Models\Lead OR Modules\CRM\Models\Opportunity
     *  - q: search term
     */
    public function fetchNotables(Request $request)
{
    $request->validate([
        'type' => 'required|in:Modules\\CRM\\Models\\Lead,Modules\\CRM\\Models\\Opportunity'
    ]);

    $model = $request->type;
    $q = trim((string) $request->get('q', ''));

    $query = (new $model)->query();

    switch ($model) {

        case 'Modules\\CRM\\Models\\Lead':
            $query->select('id', DB::raw("CONCAT(lead_name, IFNULL(CONCAT(' - ', email), '')) AS text"))
                ->when($q !== '', function ($qq) use ($q) {
                    $qq->where('lead_name', 'like', "%{$q}%")
                       ->orWhere('email', 'like', "%{$q}%")
                       ->orWhere('phone', 'like', "%{$q}%");
                });
            break;

        case 'Modules\\CRM\\Models\\Opportunity':
            $query->select('id', DB::raw("CONCAT(title, ' (', FORMAT(value,2), ')') AS text"))
                ->when($q !== '', function ($qq) use ($q) {
                    $qq->where('title', 'like', "%{$q}%");
                });
            break;
    }

    return response()->json($query->limit(50)->get());
}




    public function datatable(Request $request)
{
    $query = Note::query()
        ->with(['notable', 'author'])
        ->select('notes.*');

    // Filters
    if ($request->filled('notable_type')) {
        $query->where('notable_type', $request->notable_type);
    }
    if ($request->filled('notable_id')) {
        $query->where('notable_id', $request->notable_id);
    }
    if ($request->filled('author_id')) {
        $query->where('author_id', $request->author_id);
    }

    return DataTables::eloquent($query)
        ->addColumn('checkbox', fn($n) => '<input type="checkbox" class="row-checkbox" value="'.$n->id.'">')

        // ✅ show last name only (Customer/Lead/Opportunity)
        ->addColumn('notable_type_short', fn($n) => class_basename($n->notable_type))

        // ✅ show notable name
        ->addColumn('notable_label', function ($n) {
            if (!$n->notable) return '—';

            return match (class_basename($n->notable_type)) {
                'Customer'    => $n->notable->name ?? '—',
                'Lead'        => $n->notable->lead_name ?? '—',
                'Opportunity' => $n->notable->title ?? '—',
                default       => '—',
            };
        })

        ->addColumn('author_name', function ($n) {
            $a = $n->author;
            if (!$a) return '—';
            $full = trim(($a->first_name ?? '').' '.($a->last_name ?? ''));
            return $full !== '' ? $full : ('Employee #'.$a->id);
        })

        // ✅ d-m-Y
        ->addColumn('created_at_formatted', fn($n) => optional($n->created_at)->format('d-m-Y h:i a'))

        ->addColumn('actions', function ($n) {

            $record = htmlspecialchars(json_encode([
                'id'              => $n->id,
                'subject'         => $n->subject,
                'content'         => $n->content,
                'author_id'       => $n->author_id,
                'notable_type'    => $n->notable_type,
                'notable_id'      => $n->notable_id,
                'notable_label'   => ($n->notable ? (
                    match (class_basename($n->notable_type)) {
                        'Customer'    => $n->notable->name ?? '',
                        'Lead'        => $n->notable->lead_name ?? '',
                        'Opportunity' => $n->notable->title ?? '',
                        default       => '',
                    }
                ) : ''),
            ]), ENT_QUOTES, 'UTF-8');

            $btnEdit = auth()->user()->can('crm.notes.update')
                ? '<button class="btn btn-sm btn-info edit-note" data-record="'.$record.'">Edit</button>'
                : '';

            $btnDel  = auth()->user()->can('crm.notes.delete')
                ? '<button class="btn btn-sm btn-danger delete-note" data-id="'.$n->id.'">Delete</button>'
                : '';

            return trim($btnEdit.' '.$btnDel);
        })

        ->rawColumns(['checkbox','actions'])
        ->make(true);
}



    public function store(Request $request)
    {
        $data = $request->validate([
            'subject'      => 'required|string|max:255',
            'content'      => 'required|string',
            'author_id'    => 'required|exists:employees,id',
            'notable_type' => 'required|in:Modules\\CRM\\Models\\Customer,Modules\\CRM\\Models\\Lead,Modules\\CRM\\Models\\Opportunity',
            'notable_id'   => 'required|integer',
        ]);

        // ensure notable exists
        $model = $data['notable_type'];
        abort_unless((new $model)->whereKey($data['notable_id'])->exists(), 422, 'Selected record not found.');

        $note = Note::create($data);

        $this->audit(
            module: 'crm',
            action: 'notes.created',
            description: 'Created note: '.$note->subject,
            subject: $note,
            meta: [
                'id' => $note->id,
                'subject' => $note->subject,
                'author_id' => $note->author_id,
                'notable_type' => $note->notable_type,
                'notable_id' => $note->notable_id,
            ]
        );

        return response()->json(['message' => 'Note created successfully.', 'note' => $note]);
    }

    public function update(Request $request, $id)
{
    $note = Note::findOrFail($id);

    $data = $request->validate([
        'subject'      => 'required|string|max:255',
        'content'      => 'required|string',
        'author_id'    => 'required|exists:employees,id',
        'notable_type' => 'required|in:Modules\\CRM\\Models\\Customer,Modules\\CRM\\Models\\Lead,Modules\\CRM\\Models\\Opportunity',
        'notable_id'   => 'required|integer',
    ]);

    // ensure notable exists
    $model = $data['notable_type'];
    abort_unless((new $model)->whereKey($data['notable_id'])->exists(), 422, 'Selected record not found.');

    $before = $note->only(['subject','content','author_id','notable_type','notable_id']);

    $note->update($data);

    $after = $note->fresh()->only(['subject','content','author_id','notable_type','notable_id']);

    $this->audit(
        module: 'crm',
        action: 'notes.updated',
        description: 'Updated note: '.$note->subject,
        subject: $note,
        meta: ['before' => $before, 'after' => $after]
    );

    return response()->json(['message' => 'Note updated successfully.']);
}



    public function destroy(Note $note)
    {
        $snapshot = [
            'id' => $note->id,
            'subject' => $note->subject,
            'author_id' => $note->author_id,
            'notable_type' => $note->notable_type,
            'notable_id' => $note->notable_id,
        ];

        $note->delete();

        $this->audit(
            module: 'crm',
            action: 'notes.deleted',
            description: 'Deleted note: '.$snapshot['subject'],
            subject: null,
            meta: ['deleted' => $snapshot]
        );

        return response()->json(['message' => 'Note deleted successfully.']);
    }

    public function bulkDelete(Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:notes,id',
        ]);

        $count = Note::whereIn('id', $data['ids'])->count();
        Note::whereIn('id', $data['ids'])->delete();

        $this->audit(
            module: 'crm',
            action: 'notes.bulk_deleted',
            description: "Bulk deleted {$count} note(s).",
            subject: null,
            meta: ['ids' => $data['ids'], 'count' => $count]
        );

        return response()->json(['message' => 'Selected notes deleted successfully.']);
    }
}
