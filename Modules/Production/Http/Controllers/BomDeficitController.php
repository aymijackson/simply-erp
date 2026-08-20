<?php

namespace Modules\Production\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;
use Modules\Production\Models\{ BomDeficit, BomDeficitTransaction, BomHeader };
use Modules\Inventory\Models\Product\ProductVariant;

class BomDeficitController extends Controller
{
    private function companyId(Request $r): int
    {
        return (int) ($r->user()->company_id ?? 1);
    }

    /* =====================  PAGES  ===================== */

    public function index(Request $request)
    {
        $boms = BomHeader::where('company_id', $this->companyId($request))->select('id','bom_code','name')->orderBy('name')->get();
        return view('production.boms.deficits.index', compact('boms'));
    }

    public function txnsIndex(Request $request)
    {
        $boms = BomHeader::where('company_id', $this->companyId($request))->select('id','bom_code','name')->orderBy('name')->get();
        return view('production.boms.deficits.transactions.index', compact('boms'));
    }

    /* =====================  DATATABLES  ===================== */

    public function datatable(Request $r)
    {
        $q = DB::table('bom_deficits as d')
            ->join('bom_headers as b', 'b.id', '=', 'd.bom_id')
            ->join('product_variants as v', 'v.id', '=', 'd.product_variant_id')
            ->leftJoin('products as p', 'p.id', '=', 'v.product_id')
            ->where('b.company_id', $this->companyId($r))
            ->when($r->filled('bom_id'), fn($qq)=>$qq->where('d.bom_id', $r->bom_id))
            ->selectRaw("
                d.id, d.bom_id, d.product_variant_id,
                d.qty_borrowed_total, d.qty_repaid_total, d.qty_outstanding,
                d.last_txn_at, d.last_txn_id,
                b.bom_code, b.name as bom_name,
                v.sku as variant_sku,
                COALESCE(p.product_name, '') as product_name
            ");

        return DataTables::of($q)
            ->addColumn('bom', fn($r)=> "#{$r->bom_code} — {$r->bom_name}")
            ->addColumn('sku', fn($r)=> $r->variant_sku)
            ->addColumn('product', fn($r)=> $r->product_name)
            ->addColumn('borrowed', fn($r)=> number_format($r->qty_borrowed_total, 4))
            ->addColumn('repaid', fn($r)=> number_format($r->qty_repaid_total, 4))
            ->addColumn('outstanding', fn($r)=> '<span class="fw-bold">'.number_format($r->qty_outstanding, 4).'</span>')
            ->addColumn('actions', function($r){
                $data = e(json_encode([
                    'id' => $r->id, 'bom_id' => $r->bom_id, 'product_variant_id' => $r->product_variant_id,
                    'sku' => $r->variant_sku, 'product' => $r->product_name, 'outstanding' => $r->qty_outstanding
                ]));
                return '
                    <div class="btn-group" role="group">
                      <button class="btn btn-sm btn-success btn-repay" data-record="'.$data.'"><i class="fas fa-rotate-left"></i> Repay</button>
                      <button class="btn btn-sm btn-warning btn-writeoff" data-record="'.$data.'"><i class="fas fa-ban"></i> Write-off</button>
                      <button class="btn btn-sm btn-info btn-adjust" data-record="'.$data.'"><i class="fas fa-sliders-h"></i> Adjust</button>
                      <a class="btn btn-sm btn-secondary" href="'.route('admin.production.boms.deficits.transactions.index',['bom_id'=>$r->bom_id,'variant_id'=>$r->product_variant_id]).'">
                        <i class="fas fa-list"></i> Txns
                      </a>
                    </div>
                ';
            })
            ->rawColumns(['outstanding','actions'])
            ->make(true);
    }

    public function txnsDatatable(Request $r)
    {
        $q = DB::table('bom_deficit_transactions as t')
    ->join('bom_headers as b', 'b.id', '=', 't.bom_id')
    ->join('product_variants as v', 'v.id', '=', 't.product_variant_id')
    ->leftJoin('products as p', 'p.id', '=', 'v.product_id')
    ->leftJoin('bom_headers as sb', 'sb.id', '=', 't.source_bom_id') // ← add this
    ->where('b.company_id', $this->companyId($r))
    ->selectRaw("
        t.id, t.bom_id, t.product_variant_id, t.direction, t.qty, t.unit_cost, t.source_bom_id,
        t.ref_type, t.ref_id, t.note, t.created_at,
        b.bom_code, b.name as bom_name,
        v.sku as variant_sku, COALESCE(p.product_name,'') as product_name,
        sb.bom_code as source_bom_code, sb.name as source_bom_name   -- ← add these
    ")
    ->orderByDesc('t.id');

        return DataTables::of($q)
            ->addColumn('bom', fn($r)=>"#{$r->bom_code} — {$r->bom_name}")
            ->addColumn('source_bom', function($r){
                if ($r->source_bom_code || $r->source_bom_name) {
                    $code = $r->source_bom_code ? "#{$r->source_bom_code}" : '';
                    $name = $r->source_bom_name ? " — {$r->source_bom_name}" : '';
                    return $code.$name;
                }
                return $r->source_bom_id ? '#'.$r->source_bom_id : '—';
            })
            ->addColumn('sku', fn($r)=>$r->variant_sku)
            ->addColumn('product', fn($r)=>$r->product_name)
            ->addColumn('qty_fmt', fn($r)=>number_format($r->qty,4))
            ->addColumn('ext_cost', fn($r)=> is_null($r->unit_cost) ? '—' : number_format($r->unit_cost * $r->qty,2))
            ->addColumn('actions', function($r){
                return '<button class="btn btn-sm btn-danger del-txn" data-id="'.$r->id.'"><i class="fas fa-trash"></i></button>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /* =====================  COMMANDS  ===================== */

    public function storeTxn(Request $r)
    {
        // Allow stringified JSON or form-array
        if (is_string($r->input('lines'))) {
            $arr = json_decode($r->input('lines'), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($arr)) {
                $r->merge(['lines' => $arr]);
            }
        }

        $data = $r->validate([
            'bom_id'                => ['required','integer','exists:bom_headers,id'],
            'product_variant_id'    => ['required','integer','exists:product_variants,id'],
            'direction'             => ['required','in:repay,writeoff,adjust'],
            // repay & writeoff must be positive; adjust can be signed
            'qty'                   => ['required','numeric'],
            'unit_cost'             => ['nullable','numeric','min:0'],
            'note'                  => ['nullable','string','max:1000'],
        ]);

        $bom = BomHeader::findOrFail($data['bom_id']);
        abort_unless($bom->company_id == $this->companyId($r), 404);

        $bomId  = (int) $data['bom_id'];
        $varId  = (int) $data['product_variant_id'];
        $dir    = $data['direction'];
        $qty    = (float) $data['qty'];
        $uCost  = $data['unit_cost'] ?? null;

        if (in_array($dir, ['repay','writeoff'], true) && $qty <= 0) {
            throw ValidationException::withMessages(['qty' => 'Quantity must be greater than zero.']);
        }
        if ($dir === 'adjust' && abs($qty) < 1e-9) {
            throw ValidationException::withMessages(['qty' => 'Adjustment cannot be zero.']);
        }

        return DB::transaction(function () use ($bomId,$varId,$dir,$qty,$uCost,$data) {

            $def = BomDeficit::where('bom_id',$bomId)
                ->where('product_variant_id',$varId)
                ->lockForUpdate()
                ->first();

            if (!$def) {
                // If no row exists yet, create baseline
                $def = BomDeficit::create([
                    'bom_id'             => $bomId,
                    'product_variant_id' => $varId,
                    'qty_borrowed_total' => 0,
                    'qty_repaid_total'   => 0,
                    'qty_outstanding'    => 0,
                ]);
            }

            // Validate against outstanding where applicable
            if (in_array($dir,['repay','writeoff'], true) && $qty > $def->qty_outstanding + 1e-9) {
                throw ValidationException::withMessages([
                    'qty' => 'Quantity exceeds outstanding.'
                ]);
            }

            // Insert txn
            $txn = BomDeficitTransaction::create([
                'bom_id'            => $bomId,
                'product_variant_id'=> $varId,
                'direction'         => $dir,
                'qty'               => $qty,
                'unit_cost'         => $uCost,
                'ref_type'          => 'manual',
                'ref_id'            => 0,
                'note'              => $data['note'] ?? null,
                'created_by'        => auth()->id(),
            ]);

            // Rollup updates
            if ($dir === 'repay') {
                $def->qty_repaid_total += $qty;
                $def->qty_outstanding  -= $qty;
            } elseif ($dir === 'writeoff') {
                $def->qty_outstanding  -= $qty; // do not touch repaid_total
            } else { // adjust (signed)
                $new = $def->qty_outstanding + $qty;
                if ($new < -1e-9) {
                    throw ValidationException::withMessages(['qty' => 'Adjustment would make outstanding negative.']);
                }
                $def->qty_outstanding = $new;
            }

            $def->last_txn_at = now();
            $def->last_txn_id = $txn->id;
            $def->save();

            return response()->json(['message' => 'Transaction recorded.']);
        });
    }

    public function destroyTxn(Request $r, BomDeficitTransaction $txn)
    {
        $bom = BomHeader::findOrFail($txn->bom_id);
        abort_unless($bom->company_id == $this->companyId($r), 404);

        // Only allow deleting the last transaction for that (bom,variant)
        $lastId = BomDeficit::where('bom_id',$txn->bom_id)
                    ->where('product_variant_id',$txn->product_variant_id)
                    ->value('last_txn_id');

        if ((int)$lastId !== (int)$txn->id) {
            return response()->json(['message'=>'Only the last transaction can be deleted'], 422);
        }

        return DB::transaction(function () use ($txn) {
            $def = BomDeficit::where('bom_id',$txn->bom_id)
                    ->where('product_variant_id',$txn->product_variant_id)
                    ->lockForUpdate()
                    ->firstOrFail();

            // Reverse rollup
            if ($txn->direction === 'repay') {
                $def->qty_repaid_total -= $txn->qty;
                $def->qty_outstanding  += $txn->qty;
            } elseif ($txn->direction === 'writeoff') {
                $def->qty_outstanding  += $txn->qty;
            } else { // adjust
                $def->qty_outstanding  -= $txn->qty;
            }

            // Find previous txn for last_txn_id
            $prev = BomDeficitTransaction::where('bom_id',$txn->bom_id)
                        ->where('product_variant_id',$txn->product_variant_id)
                        ->where('id','<',$txn->id)
                        ->orderByDesc('id')
                        ->first();

            $def->last_txn_id = $prev?->id;
            $def->last_txn_at = $prev?->created_at;
            $def->save();

            $txn->delete();

            return response()->json(['message'=>'Transaction deleted.']);
        });
    }
}
