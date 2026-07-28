<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\SettingGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        $modules = SettingGroup::query()
            ->select('module')
            ->where('is_active', 1)
            ->distinct()
            ->orderBy('module')
            ->pluck('module')
            ->values();

        $groups = SettingGroup::query()
            ->where('is_active', 1)
            ->orderBy('module')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id','code','name','module']);

        return view('settings.index', compact('modules','groups'));
    }

    public function datatable(Request $request)
    {
        $q = Setting::query()
            ->with('group:id,code,name,module')
            ->orderBy('id', 'desc');

        if ($request->filled('module')) {
            $module = trim((string)$request->module);
            $q->whereHas('group', fn($x)=>$x->where('module',$module));
        }

        if ($request->filled('setting_group_id')) {
            $q->where('setting_group_id', (int)$request->setting_group_id);
        }

        if ($request->filled('scope')) {
            $q->where('scope', $request->scope);
        }

        if ($request->filled('key')) {
            $q->where('key', 'like', '%'.trim((string)$request->key).'%');
        }

        $rows = $q->paginate((int)($request->length ?? 10));

        $data = $rows->map(function($s){
            $val = $s->is_sensitive ? '******' : (string)($s->value ?? '');

            if ($s->value_type === 'bool') {
                $val = ((string)$s->value === '1' || (string)$s->value === 'true') ? 'Yes' : 'No';
            }

            if ($s->value_type === 'file' && !$s->is_sensitive) {
                $val = $s->value ? basename($s->value) : '';
            }

            return [
                'id'     => $s->id,
                'group'  => e(($s->group?->module ?? '-').' / '.($s->group?->name ?? '-')),
                'key'    => e($s->key),
                'label'  => e($s->label ?? ''),
                'type'   => e($s->value_type),
                'scope'  => e($s->scope).($s->scope_id ? ' #'.$s->scope_id : ''),
                'value'  => e(mb_strimwidth($val, 0, 80, '…')),
                'active' => $s->is_active ? '<span class="badge badge-success">ACTIVE</span>' : '<span class="badge badge-secondary">DISABLED</span>',
                'actions'=> view('settings.partials.actions', ['s'=>$s])->render(),
            ];
        })->values();

        return response()->json([
            'draw' => (int)($request->draw ?? 1),
            'recordsTotal' => $rows->total(),
            'recordsFiltered' => $rows->total(),
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateSetting($request, null);

        return DB::transaction(function() use ($data){
            $s = Setting::create($data);
            return response()->json(['message'=>'Setting created.','id'=>$s->id]);
        });
    }

    public function update(Request $request, Setting $setting)
    {
        $data = $this->validateSetting($request, $setting->id);

        return DB::transaction(function() use ($setting, $data){
            $setting->update($data);
            return response()->json(['message'=>'Setting updated.']);
        });
    }

    public function destroy(Setting $setting)
    {
        if ($setting->value_type === 'file' && $setting->value) {
            // Optional: delete file
            // Storage::disk('public')->delete($setting->value);
        }

        $setting->delete();
        return response()->json(['message'=>'Deleted.']);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || !count($ids)) {
            return response()->json(['message'=>'No rows selected.'], 422);
        }

        Setting::whereIn('id', array_map('intval', $ids))->delete();
        return response()->json(['message'=>'Selected settings deleted.']);
    }

    public function upload(Request $request)
    {
        $v = Validator::make($request->all(), [
            'file' => ['required','file','max:4096'], // 4MB
        ])->validate();

        $path = $request->file('file')->store('settings', 'public');

        return response()->json([
            'message' => 'Uploaded.',
            'path'    => $path,
            'url'     => asset('storage/'.$path),
        ]);
    }

    private function validateSetting(Request $request, ?int $ignoreId): array
    {
        $rules = [
            'setting_group_id' => ['required','integer','exists:setting_groups,id'],
            'key'              => ['required','string','max:120'],
            'label'            => ['nullable','string','max:160'],
            'description'      => ['nullable','string','max:255'],

            'value_type'       => ['required','in:string,text,int,decimal,bool,json,date,datetime,file,email,phone,url'],
            'value'            => ['nullable'], // typed by UI

            'scope'            => ['required','in:global,company,location,user'],
            'scope_id'         => ['nullable','integer','min:1'],

            'is_sensitive'     => ['nullable','boolean'],
            'is_required'      => ['nullable','boolean'],
            'is_active'        => ['nullable','boolean'],
            'sort_order'       => ['nullable','integer'],
        ];

        $data = Validator::make($request->all(), $rules)->validate();

        // enforce scope_id rule
        if ($data['scope'] === 'global') {
            $data['scope_id'] = null;
        } else {
            if (empty($data['scope_id'])) {
                abort(response()->json(['message'=>'scope_id is required for non-global scope.'], 422));
            }
        }

        // uniqueness: (scope,scope_id,key)
        $exists = Setting::query()
            ->where('scope', $data['scope'])
            ->where(function($x) use ($data){
                if ($data['scope_id'] === null) $x->whereNull('scope_id');
                else $x->where('scope_id', $data['scope_id']);
            })
            ->where('key', $data['key'])
            ->when($ignoreId, fn($x)=>$x->where('id','!=',$ignoreId))
            ->exists();

        if ($exists) {
            abort(response()->json(['message'=>'This key already exists for the selected scope.'], 422));
        }

        // normalize booleans
        $data['is_sensitive'] = !empty($data['is_sensitive']) ? 1 : 0;
        $data['is_required']  = !empty($data['is_required']) ? 1 : 0;
        $data['is_active']    = array_key_exists('is_active',$data) ? (int)!!$data['is_active'] : 1;

        return $data;
    }
    
    public function show(\App\Models\Setting $setting, Request $request)
    {
        $setting->load(['group']); // assumes Setting belongsTo SettingGroup as "group"
    
        // Optional: if you store JSON values as string, decode safely for display
        $displayValue = $setting->value;
    
        if (in_array($setting->type, ['json','array'], true)) {
            $decoded = json_decode((string)$setting->value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $displayValue = $decoded;
            }
        }
    
        // If the request expects JSON (datatable/details modal), return JSON
        if ($request->expectsJson()) {
            return response()->json([
                'id'          => $setting->id,
                'group_id'    => $setting->group_id,
                'group'       => $setting->group ? [
                    'id'     => $setting->group->id,
                    'module' => $setting->group->module,
                    'code'   => $setting->group->code,
                    'name'   => $setting->group->name,
                ] : null,
    
                'key'         => $setting->key,
                'label'       => $setting->label,
                'type'        => $setting->type,
                'value'       => $displayValue,
                'raw_value'   => $setting->value,
    
                'is_required' => (bool) $setting->is_required,
                'is_active'   => (bool) $setting->is_active,
                'sort_order'  => (int)  ($setting->sort_order ?? 0),
    
                'help_text'   => $setting->help_text,
                'created_at'  => optional($setting->created_at)->format('Y-m-d H:i:s'),
                'updated_at'  => optional($setting->updated_at)->format('Y-m-d H:i:s'),
            ]);
        }
    
        // Normal page view
        return view('settings.show', [
            'setting'      => $setting,
            'displayValue' => $displayValue,
        ]);
    }

}
