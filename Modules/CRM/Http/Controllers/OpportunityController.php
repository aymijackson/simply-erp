<?php

namespace Modules\CRM\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CRM\Models\Opportunity;
use Modules\HRM\Models\Employee;
use App\Http\Controllers\BaseController;

class OpportunityController extends BaseController
{
    public function index()
    {
        $employees = Employee::select('id','first_name','last_name')->orderBy('first_name')->get();

        $stages = [
            'prospecting'   => 'Prospecting',
            'qualification' => 'Qualification',
            'proposal'      => 'Proposal',
            'negotiation'   => 'Negotiation',
            'won'           => 'Won',
            'lost'          => 'Lost',
        ];

        return view('crm.opportunities.index', compact('employees','stages'));
    }

    public function datatable(Request $request)
    {
        $q = Opportunity::query()
            ->with(['customer:id,name,email', 'owner:id,first_name,last_name'])
            ->select('opportunities.*');

        if ($request->filled('customer_id')) $q->where('customer_id', $request->customer_id);
        if ($request->filled('stage'))       $q->where('stage', $request->stage);
        if ($request->filled('owner_id'))    $q->where('owner_id', $request->owner_id);

        return datatables()->eloquent($q)
            ->addColumn('checkbox', fn($o) =>
                '<input type="checkbox" class="row-checkbox" value="'.$o->id.'">')

            ->addColumn('customer_name', fn($o) => e($o->customer?->name ?? '—'))

            ->addColumn('value_fmt', fn($o) =>
                is_null($o->value) ? '—' : 'NGN '.number_format((float)$o->value, 2))

            ->addColumn('probability_fmt', fn($o) =>
                is_null($o->probability) ? '—' : ((int)$o->probability).'%')

            ->addColumn('close_date_fmt', fn($o) =>
                $o->close_date ? \Carbon\Carbon::parse($o->close_date)->format('d-m-Y') : '—')

            ->addColumn('owner_name', function ($o) {
                $fn = trim(($o->owner?->first_name ?? '').' '.($o->owner?->last_name ?? ''));
                return e($fn ?: '—');
            })

            ->addColumn('actions', function ($o) {
                $payload = [
                    'id' => $o->id,
                    'title' => $o->title,
                    'customer_id' => $o->customer_id,
                    'customer_name' => $o->customer?->name,
                    'value' => $o->value,
                    'stage' => $o->stage,
                    'probability' => $o->probability,
                    'close_date' => $o->close_date ? \Carbon\Carbon::parse($o->close_date)->format('Y-m-d') : null,
                    'owner_id' => $o->owner_id,
                    'notes' => $o->notes,
                ];
                $record = htmlspecialchars(json_encode($payload), ENT_QUOTES, 'UTF-8');

                return '
                    <button class="btn btn-sm btn-info edit-opportunity" data-record="'.$record.'">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-opportunity" data-id="'.$o->id.'">
                        <i class="fas fa-trash"></i>
                    </button>
                ';
            })

            ->rawColumns(['checkbox','actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'customer_id' => 'required|exists:customers,id',
            'value'       => 'required|numeric',
            'stage'       => 'required|string|max:255',
            'probability' => 'nullable|numeric|min:0|max:100',
            'close_date'  => 'nullable|date',
            'owner_id'    => 'required|exists:employees,id',
            'notes'       => 'nullable|string',
        ]);

        $opportunity = Opportunity::create($data);

        // AUDIT: created
        $this->audit(
            module: 'crm.opportunities',
            action: 'created',
            description: 'Created opportunity '.($opportunity->title ? "\"{$opportunity->title}\"" : '#'.$opportunity->id),
            subject: $opportunity,
            meta: [
                'id'          => $opportunity->id,
                'title'       => $opportunity->title,
                'customer_id' => $opportunity->customer_id,
                'value'       => $opportunity->value,
                'stage'       => $opportunity->stage,
                'probability' => $opportunity->probability,
                'close_date'  => $opportunity->close_date,
                'owner_id'    => $opportunity->owner_id,
            ]
        );

        return response()->json(['message' => 'Opportunity created successfully', 'data' => $opportunity]);
    }

    public function update(Request $request, $id)
    {
        $opportunity = Opportunity::findOrFail($id);

        // BEFORE snapshot
        $before = [
            'title'       => $opportunity->title,
            'customer_id' => $opportunity->customer_id,
            'value'       => $opportunity->value,
            'stage'       => $opportunity->stage,
            'probability' => $opportunity->probability,
            'close_date'  => $opportunity->close_date,
            'owner_id'    => $opportunity->owner_id,
            'notes'       => $opportunity->notes,
        ];

        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'customer_id' => 'required|exists:customers,id',
            'value'       => 'required|numeric',
            'stage'       => 'required|string|max:255',
            'probability' => 'nullable|numeric|min:0|max:100',
            'close_date'  => 'nullable|date',
            'owner_id'    => 'required|exists:employees,id',
            'notes'       => 'nullable|string',
        ]);

        $opportunity->update($data);

        // AFTER snapshot
        $after = [
            'title'       => $opportunity->title,
            'customer_id' => $opportunity->customer_id,
            'value'       => $opportunity->value,
            'stage'       => $opportunity->stage,
            'probability' => $opportunity->probability,
            'close_date'  => $opportunity->close_date,
            'owner_id'    => $opportunity->owner_id,
            'notes'       => $opportunity->notes,
        ];

        // only log actual changes
        $changes = [];
        foreach ($after as $k => $v) {
            if (($before[$k] ?? null) != $v) $changes[] = $k;
        }

        // AUDIT: updated (with before/after)
        $this->audit(
            module: 'crm.opportunities',
            action: 'updated',
            description: 'Updated opportunity '.($opportunity->title ? "\"{$opportunity->title}\"" : '#'.$opportunity->id),
            subject: $opportunity,
            meta: [
                'changes' => $changes,
                'before'  => $before,
                'after'   => $after,
            ]
        );

        return response()->json(['message' => 'Opportunity updated successfully', 'data' => $opportunity]);
    }

    public function destroy($id)
    {
        $opportunity = Opportunity::findOrFail($id);

        $before = [
            'id'          => $opportunity->id,
            'title'       => $opportunity->title,
            'customer_id' => $opportunity->customer_id,
            'value'       => $opportunity->value,
            'stage'       => $opportunity->stage,
            'probability' => $opportunity->probability,
            'close_date'  => $opportunity->close_date,
            'owner_id'    => $opportunity->owner_id,
        ];

        $opportunity->delete();

        // AUDIT: deleted
        $this->audit(
            module: 'crm.opportunities',
            action: 'deleted',
            description: 'Deleted opportunity '.($before['title'] ? "\"{$before['title']}\"" : '#'.$before['id']),
            subject: Opportunity::class,
            meta: [
                'before' => $before,
            ]
        );

        return response()->json(['message' => 'Opportunity deleted']);
    }

    public function bulkDelete(Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:opportunities,id',
        ]);

        $rows = Opportunity::whereIn('id', $data['ids'])
            ->get(['id','title','customer_id','value','stage','probability','close_date','owner_id'])
            ->toArray();

        Opportunity::whereIn('id', $data['ids'])->delete();

        // AUDIT: bulk deleted
        $this->audit(
            module: 'crm.opportunities',
            action: 'deleted',
            description: 'Bulk deleted opportunities ('.count($data['ids']).')',
            subject: Opportunity::class,
            meta: [
                'ids'  => $data['ids'],
                'rows' => $rows,
            ]
        );

        return response()->json(['message' => 'Selected opportunities deleted']);
    }
}
