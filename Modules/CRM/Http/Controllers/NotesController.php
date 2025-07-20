<?php

namespace Modules\CRM\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CRM\Models\Lead;
use Modules\CRM\Models\Note;
use App\Models\Company;
use Modules\HRM\Models\Employee;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class NotesController extends Controller
{
    public function index(Request $request)
    {
        $employees = Employee::all();
        return view('crm.notes.index', compact('employees'));
    }

    public function fetchNotables(Request $request)
    {
        $request->validate([
            'type' => 'required|in:Modules\\CRM\\Models\\Customer,Modules\\CRM\\Models\\Lead,Modules\\CRM\\Models\\Opportunity'
        ]);

        $model = $request->type;

        $query = (new $model)->query();

        // Adjust label logic based on the model type
        switch ($model) {
            case 'Modules\\CRM\\Models\\Customer':
                $query->select('id', DB::raw("CONCAT(name, ' - ', email) AS label"));
                break;

            case 'Modules\\CRM\\Models\\Lead':
                $query->select('id', DB::raw("CONCAT(lead_name, ' - ', company) AS label"));
                break;

            case 'Modules\\CRM\\Models\\Opportunity':
                $query->select('id', DB::raw("CONCAT(title, ' (Value: ', value, ')') AS label"));
                break;
        }

        $data = $query->limit(50)->get();

        return response()->json($data);
    }


    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'author_id' => 'required|exists:employees,id',
            'notable_type' => 'required|string',
            'notable_id' => 'required|integer'
        ]);

        $note = Note::create($request->only([
            'notable_type', 'notable_id', 'subject', 'content', 'author_id'
        ]));

        return response()->json([
            'message' => 'Note created successfully.',
            'note'    => $note
        ]);
    }

    public function update(Request $request, $id)
    {
        $note = Note::findOrFail($id);

        $request->validate([
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'author_id' => 'required|exists:employees,id',
            'notable_type' => 'required|string',
            'notable_id' => 'required|integer'
        ]);

        $note->update($request->only(['subject', 'content', 'author_id', 'notable_type', 'notable_id']));

        return response()->json([
            'message' => 'Note updated successfully.',
            'note'    => $note
        ]);
    }

    public function destroy($id)
    {
        $note = Note::findOrFail($id);
        $note->delete();

        return response()->json([
            'message' => 'Note deleted successfully.'
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:notes,id'
        ]);

        Note::whereIn('id', $request->ids)->delete();

        return response()->json([
            'message' => 'Selected notes deleted successfully.'
        ]);
    }

    public function datatable()
{
    $notes = Note::with('notable')->get();

    return datatables()->of($notes)
        ->addColumn('checkbox', function ($note) {
            return '<input type="checkbox" class="row-checkbox" value="' . $note->id . '">';
        })
        ->addColumn('notable_type', function ($note) {
            return class_basename($note->notable_type);
        })
        ->addColumn('created_at', function ($note) {
            return $note->created_at ? $note->created_at->format('d-m-Y H:i a') : 'N/A';
        })
        ->addColumn('notable_value', function ($note) {
            if (!$note->notable) return '-';

            // Customize based on type
            switch (class_basename($note->notable_type)) {
                case 'Lead':
                    return $note->notable->lead_name ?? '-';
                case 'Customer':
                    return $note->notable->name ?? '-';
                case 'Opportunity':
                    return $note->notable->title ?? '-';
                default:
                    return '-';
            }
        })
        ->addColumn('actions', function ($note) {
            return '
                <button class="btn btn-sm btn-primary edit-note" data-record=\'' . json_encode($note) . '\'>
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger delete-note" data-id="' . $note->id . '">
                    <i class="fas fa-trash"></i>
                </button>
            ';
        })
        ->rawColumns(['actions', 'checkbox'])
        ->make(true);
}

}
