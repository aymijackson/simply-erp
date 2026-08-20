<?php
namespace Modules\Production\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Production\Models\Routing;
use Modules\Production\Models\RoutingStep;
use Modules\Inventory\Models\Product\ProductVariant;
use Modules\Inventory\Models\Product\Product;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class RoutingController extends Controller
{
    public function select2(Request $r)
    {
        $term    = trim((string) $r->get('q', ''));
        $exclude = $r->integer('exclude');

        $rows = Routing::query()
            ->from('routings as r')
            ->leftJoin('product_variants as v', 'v.id', '=', 'r.product_variant_id')
            ->leftJoin('products as p', 'p.id', '=', 'v.product_id')
            ->when($exclude, fn ($q) => $q->where('r.id', '<>', $exclude))
            ->when($term !== '', function ($q) use ($term) {
                $like = '%'.$term.'%';
                $q->where(function ($w) use ($like) {
                    $w->where('r.name', 'like', $like)
                      ->orWhere('v.sku', 'like', $like)
                      ->orWhere('p.product_name', 'like', $like);
                });
            })
            ->orderBy('r.name')
            ->limit(20)
            ->get([
                'r.id',
                'r.name',
                DB::raw("COALESCE(v.sku,'') as sku"),
                DB::raw("COALESCE(p.product_name,'') as product_name"),
            ]);

        $out = $rows->map(function ($row) {
            $extra = array_filter([$row->sku ?: null, $row->product_name ?: null]);
            $label = '#'.$row->id.' — '.$row->name.(count($extra) ? ' ('.implode(' · ', $extra).')' : '');
            return ['id' => $row->id, 'text' => $label];
        });

        return response()->json($out);
    }
    
    public function index()
    {
        return view('production.routings.index');
    }

    public function show(Routing $routing)
    {
        return view('production.routings.show', [
            'routing' => $routing,
        ]);
    }

    public function datatable(Request $r)
{
    $q = Routing::with('product_variant');

    if ($r->filled('sku'))     $q->where('v.sku', 'like', '%'.$r->sku.'%');
    if ($r->filled('product')) $q->where('p.product_name', 'like', '%'.$r->product.'%');

    return DataTables::of($q)
        ->addIndexColumn()
        ->addColumn('checkbox', fn($row) =>
            '<input type="checkbox" class="row-check" value="'.$row->id.'">')
        // Map to keys your JS uses:
        ->addColumn('variant', fn($row) => $row->product_variant->sku ?: '—')
        ->addColumn('product', fn($row) => $row->product_variant->product->product_name ?: '—')
        ->editColumn('name', fn($row) => e($row->name))
        ->editColumn('description', fn($row) => e($row->description ?? ''))
        ->editColumn('created_at', fn($row) =>
            $row->created_at ? \Illuminate\Support\Carbon::parse($row->created_at)->format('Y-m-d H:i') : '—'
        )
        ->addColumn('actions', function ($row) {
            $data = e(json_encode($row));
            return <<<HTML
                <a class="btn btn-sm btn-primary view-routing" href="/admin/production/routings/{$row['id']}">
                    <i class="fas fa-eye"></i>
                </a>
                <button class="btn btn-sm btn-info edit-routing" data-record="{$data}">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger delete-routing" data-id="{$row->id}">
                    <i class="fas fa-trash"></i>
                </button>
            HTML;
        })
        ->rawColumns(['checkbox','actions'])
        ->make(true);
    }

    
    public function stepsDatatable(Request $r, Routing $routing)
    {
        $q = RoutingStep::query()
            ->where('routing_id', $routing->id)
            ->orderBy('sequence')
            ->select(['id','routing_id','step_name','instructions','sequence','created_at']);

        return DataTables::of($q)
            ->addIndexColumn()
            ->addColumn('checkbox', fn($row) =>
                '<input type="checkbox" class="row-check" value="'.$row->id.'">')
            ->addColumn('created_at', fn($row) =>
                $row->created_at ? \Illuminate\Support\Carbon::parse($row->created_at)->format('Y-m-d H:i') : '—'
            )
            ->addColumn('actions', function ($row) {
                $data = e(json_encode($row));
                return <<<HTML
                    <button class="btn btn-sm btn-info edit-step" data-record="{$data}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger del-step" data-id="{$row->id}">
                        <i class="fas fa-trash"></i>
                    </button>
                HTML;
            })
            ->rawColumns(['checkbox','actions'])
            ->make(true);
    }
    
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'product_variant_id' => 'required|exists:product_variants,id',
        ]);

        Routing::create($data);
        return response()->json(['message' => 'Routing added']);
    }

    
    public function storeStep(Request $r, Routing $routing)
    {
        $data = $r->validate([
            'step_name'    => ['required','string','max:255'],
            'instructions' => ['nullable','string'],
            'sequence'     => ['nullable','integer','min:0'],
        ]);

        // default sequence to next
        if (!isset($data['sequence'])) {
            $data['sequence'] = (int) RoutingStep::where('routing_id', $routing->id)->max('sequence') + 10;
        }

        $data['routing_id'] = $routing->id;
        $step = RoutingStep::create($data);

        return response()->json(['message'=>'Step created','id'=>$step->id]);
    }
    
    public function edit(Routing $routing)
    {
        return response()->json($routing->load('product_variant.product'));
    }

    public function update(Request $request, Routing $routing)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $routing->update($data);
        return response()->json(['message' => 'Routing updated']);
    }

    public function destroy(Routing $routing)
    {
        $routing->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function bulkDelete(Request $request)
    {
        Routing::whereIn('id', $request->ids ?? [])->delete();
        return response()->json(['message' => 'Bulk delete done']);
    }
}
