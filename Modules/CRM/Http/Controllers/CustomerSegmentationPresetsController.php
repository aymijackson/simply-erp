<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Modules\CRM\Models\CustomerSegmentPreset;
use Yajra\DataTables\Facades\DataTables;

class CustomerSegmentationPresetsController extends BaseController
{
    public function index()
    {
        return view('crm.customers.segmentation-presets.index');
    }

    public function datatable()
    {
        $q = CustomerSegmentPreset::query()->orderByDesc('is_default')->orderBy('name');

        return DataTables::of($q)
            ->addColumn('checkbox', fn($r) => '<input type="checkbox" class="row-checkbox" value="'.$r->id.'">')
            ->addColumn('risk_statuses_txt', fn($r) => implode(', ', $r->risk_statuses ?? []))
            ->addColumn('actions', function($r){
                $record = htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8');

                $btnEdit = auth()->user()->can('crm.customers.segmentation_presets.update')
                    ? '<button class="btn btn-sm btn-info edit-preset" data-record="'.$record.'">Edit</button>'
                    : '';

                $btnDel = auth()->user()->can('crm.customers.segmentation_presets.delete')
                    ? '<button class="btn btn-sm btn-danger delete-preset" data-id="'.$r->id.'">Delete</button>'
                    : '';

                return trim($btnEdit.' '.$btnDel);
            })
            ->rawColumns(['checkbox','actions'])
            ->make(true);
    }

    // Select2 for preset dropdown (searchable)
    public function select2(Request $request)
    {
        $q = trim($request->input('q',''));

        return CustomerSegmentPreset::query()
            ->where('is_active', 1)
            ->when($q !== '', fn($qq) => $qq->where('name','like',"%{$q}%"))
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->limit(25)
            ->get(['id','name','description'])
            ->map(fn($p) => [
                'id' => $p->id,
                'text' => $p->description ? "{$p->name} — {$p->description}" : $p->name,
            ]);
    }

    // Return full preset JSON for applying to the segmentation page
    public function show(CustomerSegmentPreset $preset)
    {
        return response()->json($preset);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:120|unique:crm_customer_segment_presets,name',
            'description'        => 'nullable|string|max:255',
            'high_value_min'     => 'required|numeric|min:0',
            'hot_recency_days'   => 'required|integer|min:1',
            'engaged_score_min'  => 'required|integer|min:0',
            'engaged_recency_days'=> 'required|integer|min:1',
            'dormant_days'       => 'required|integer|min:1',
            'risk_statuses'      => 'nullable|array',
            'risk_statuses.*'    => 'string|max:50',
            'is_default'         => 'nullable|boolean',
            'is_active'          => 'nullable|boolean',
        ]);

        // If default, unset old default
        if (!empty($data['is_default'])) {
            CustomerSegmentPreset::where('is_default', 1)->update(['is_default' => 0]);
        }

        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        $preset = CustomerSegmentPreset::create($data);

        $this->audit(
            module: 'crm',
            action: 'customers.segmentation_presets.created',
            description: 'Created segmentation preset: '.$preset->name,
            subject: $preset,
            meta: ['id'=>$preset->id]
        );

        return response()->json(['message' => 'Preset created successfully.']);
    }

    public function update(Request $request, CustomerSegmentPreset $preset)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:120|unique:crm_customer_segment_presets,name,'.$preset->id,
            'description'        => 'nullable|string|max:255',
            'high_value_min'     => 'required|numeric|min:0',
            'hot_recency_days'   => 'required|integer|min:1',
            'engaged_score_min'  => 'required|integer|min:0',
            'engaged_recency_days'=> 'required|integer|min:1',
            'dormant_days'       => 'required|integer|min:1',
            'risk_statuses'      => 'nullable|array',
            'risk_statuses.*'    => 'string|max:50',
            'is_default'         => 'nullable|boolean',
            'is_active'          => 'nullable|boolean',
        ]);

        $before = $preset->only(array_keys($data));

        if (!empty($data['is_default'])) {
            CustomerSegmentPreset::where('is_default', 1)
                ->where('id','!=',$preset->id)
                ->update(['is_default' => 0]);
        }

        $data['updated_by'] = auth()->id();
        $preset->update($data);

        $this->audit(
            module: 'crm',
            action: 'customers.segmentation_presets.updated',
            description: 'Updated segmentation preset: '.$preset->name,
            subject: $preset,
            meta: ['before'=>$before, 'after'=>$preset->fresh()->only(array_keys($data))]
        );

        return response()->json(['message' => 'Preset updated successfully.']);
    }

    public function destroy(CustomerSegmentPreset $preset)
    {
        $snap = $preset->only(['id','name','is_default','is_active']);
        $preset->delete();

        $this->audit(
            module: 'crm',
            action: 'customers.segmentation_presets.deleted',
            description: 'Deleted segmentation preset: '.$snap['name'],
            subject: null,
            meta: ['deleted'=>$snap]
        );

        return response()->json(['message' => 'Preset deleted successfully.']);
    }
}
