<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LookupsController extends Controller
{
  public function currencies(Request $request)
  {
    $q = trim((string)$request->get('q',''));

    $rows = DB::table('currencies')
      ->where('is_active', 1)
      ->when($q !== '', function($x) use ($q){
        $x->where(function($w) use ($q){
          $w->where('code','like',"%{$q}%")->orWhere('name','like',"%{$q}%");
        });
      })
      ->orderBy('code')
      ->limit(50)
      ->get(['code','name']);

    return response()->json([
      'results' => $rows->map(fn($r)=>[
        'id' => $r->code,
        'text' => $r->code.' - '.$r->name,
      ])->values()
    ]);
  }

    public function banks(Request $request)
    {
        $q = trim((string)$request->get('q',''));
        $country = strtoupper(trim((string)$request->get('country','NG')));
    
        $table = 'banks';
    
        // Build selectable columns safely
        $cols = ['id','name'];
        foreach (['code','short_name','swift_code','country_code'] as $c) {
            if (\Illuminate\Support\Facades\Schema::hasColumn($table, $c)) $cols[] = $c;
        }
    
        $query = DB::table($table)
            ->where('is_active', 1);
    
        if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'country_code')) {
            $query->where('country_code', $country);
        }
    
        if ($q !== '') {
            $query->where(function ($w) use ($q, $table) {
                $w->where('name', 'like', "%{$q}%");
    
                foreach (['code','short_name','swift_code'] as $c) {
                    if (\Illuminate\Support\Facades\Schema::hasColumn($table, $c)) {
                        $w->orWhere($c, 'like', "%{$q}%");
                    }
                }
            });
        }
    
        $rows = $query->orderBy('name')->limit(50)->get($cols);
    
        return response()->json([
            'results' => $rows->map(function ($r) {
                $code = $r->short_name ?? ($r->code ?? null);
    
                return [
                    'id' => $r->id,
                    'text' => $code ? ($r->name . ' (' . $code . ')') : $r->name,
                ];
            })->values()
        ]);
    }
}