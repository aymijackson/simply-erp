<?php

namespace App\Http\Controllers;

use App\Models\SettingGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SettingGroupController extends Controller
{
    public function index()
    {
        return view('settings.groups.index');
    }

    public function datatable(Request $request)
    {
        $q = SettingGroup::query()->orderBy('module')->orderBy('sort_order')->orderBy('name');

        if ($request->filled('module')) {
            $q->where('module', trim((string)$request->module));
        }

        if ($request->filled('q')) {
            $term = trim((string)$request->q);
            $q->where(function($x) use ($term){
                $x->where('code','like',"%{$term}%")
                  ->orWhere('name','like',"%{$term}%")
                  ->orWhere('module','like',"%{$term}%");
            });
        }

        $rows = $q->paginate((int)($request->length ?? 10));

        $data = $rows->map(function($g){
            return [
                'id'          => $g->id,
                'module'      => e(strtoupper($g->module)),
                'code'        => e($g->code),
                'name'        => e($g->name),
                'sort_order'  => (int)($g->sort_order ?? 0),
                'active'      => $g->is_active
                    ? '<span class="badge badge-success">ACTIVE</span>'
                    : '<span class="badge badge-secondary">DISABLED</span>',
                'actions'     => view('settings.groups.partials.actions', ['g'=>$g])->render(),
            ];
        })->values();

        return response()->json([
            'draw'            => (int)($request->draw ?? 1),
            'recordsTotal'    => $rows->total(),
            'recordsFiltered' => $rows->total(),
            'data'            => $data,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateGroup($request, null);

        return DB::transaction(function() use ($data){
            $g = SettingGroup::create($data);
            return response()->json(['message'=>'Group created.','id'=>$g->id]);
        });
    }

    public function update(Request $request, SettingGroup $group)
    {
        $data = $this->validateGroup($request, $group->id);

        return DB::transaction(function() use ($group, $data){
            $group->update($data);
            return response()->json(['message'=>'Group updated.']);
        });
    }

    public function destroy(SettingGroup $group)
    {
        // prevent delete if it has settings
        if ($group->settings()->exists()) {
            return response()->json(['message'=>'Cannot delete a group that has settings. Move settings first.'], 422);
        }

        $group->delete();
        return response()->json(['message'=>'Group deleted.']);
    }

    private function validateGroup(Request $request, ?int $ignoreId): array
    {
        $data = Validator::make($request->all(), [
            'module'       => ['required','string','max:60'],
            'code'         => ['required','string','max:60'],
            'name'         => ['required','string','max:120'],
            'description'  => ['nullable','string','max:255'],
            'sort_order'   => ['nullable','integer'],
            'is_active'    => ['nullable','boolean'],
        ])->validate();

        $data['module'] = strtolower(trim($data['module']));
        $data['code']   = strtolower(trim($data['code']));

        // Unique: (module, code)
        $exists = SettingGroup::query()
            ->where('module', $data['module'])
            ->where('code', $data['code'])
            ->when($ignoreId, fn($x)=>$x->where('id','!=',$ignoreId))
            ->exists();

        if ($exists) {
            abort(response()->json(['message'=>'This module + code already exists.'], 422));
        }

        $data['is_active'] = array_key_exists('is_active',$data) ? (int)!!$data['is_active'] : 1;
        $data['sort_order'] = (int)($data['sort_order'] ?? 0);

        return $data;
    }
}
