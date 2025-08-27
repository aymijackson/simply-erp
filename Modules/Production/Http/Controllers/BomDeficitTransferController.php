<?php
namespace Modules\Production\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Production\Models\BomHeader;
use Modules\Production\Models\BomItem;
use Modules\Production\Services\BomDeficitService;


class BomDeficitTransferController extends Controller
{
    /** Select2 source: variants that exist on this BOM, each with (avail) */
    public function itemsSelect2(Request $r, BomHeader $bom)
    {
        $q = trim((string)$r->q);

        $items = BomItem::query()
            ->with(['product_variant.product'])
            ->where('bom_header_id', $bom->id)
            ->when($q, fn($qq) =>
                $qq->whereHas('product_variant', function($v) use ($q){
                    $v->where('sku', 'like', "%{$q}%")
                      ->orWhereHas('product', fn($p) => $p->where('product_name', 'like', "%{$q}%"));
                })
            )
            ->limit(30)->get();

        $out = $items->map(function($i) use ($bom){
            $v = $i->product_variant;
            $p = $v->product;
            $avail = $this->availableOnBom($bom->id, $v->id);
            return [
                'id'   => $v->id,
                'text' => "{$v->sku} — ".($p->product_name ?? 'Product')."  (avail: ".number_format($avail,4).")",
                'meta' => ['available' => $avail],
            ];
        });

        return response()->json($out);
    }

    /** GET {bom}/available?variant_id= */
    public function available(Request $r, BomHeader $bom)
    {
        $r->validate(['variant_id' => ['required','integer']]);
        $qty = $this->availableOnBom($bom->id, (int)$r->variant_id);
        return response()->json(['qty_available' => (float)$qty]);
    }

    /** POST {bom}/transfer */
    public function transfer(Request $r, BomHeader $bom)
    {
        // Accept JSON-string 'lines' from the UI
        if (is_string($r->input('lines'))) {
            $decoded = json_decode($r->input('lines'), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $r->merge(['lines' => $decoded]);
            }
        }
    
        $data = $r->validate([
            'dest_bom_id'                 => ['required','integer','different:source_bom_id','exists:bom_headers,id'],
            'source_bom_id'               => ['required','integer','in:'.$bom->id],
            'lines'                       => ['required','array','min:1'],
            'lines.*.product_variant_id'  => ['required','integer','exists:product_variants,id'],
            'lines.*.qty'                 => ['required','numeric','min:0.0001'],
        ]);
    
        // check availability on SOURCE BOM
        $errs = [];
        foreach ($data['lines'] as $i => $ln) {
            $varId = (int)$ln['product_variant_id'];
            $qty   = (float)$ln['qty'];
            $avail = $this->availableOnBom($bom->id, $varId);
            if ($qty > $avail + 1e-9) {
                $sku = DB::table('product_variants')->where('id',$varId)->value('sku') ?? $varId;
                $errs["lines.$i.qty"] = "Insufficient on BOM for {$sku}: avail "
                    .number_format($avail,4)." < need ".number_format($qty,4);
            }
        }
        if ($errs) {
            throw ValidationException::withMessages($errs);
        }
    
        $destId = (int) $data['dest_bom_id'];
    
        // move qty + repay destination deficits in one transaction
        $groupRef = DB::transaction(function () use ($data, $bom, $destId) {
    
            $firstRefId = 0; // only used if you want to group any 'adjust' entries; safe to keep
    
            foreach ($data['lines'] as $idx => $ln) {
                $varId = (int)$ln['product_variant_id'];
                $qty   = (float)$ln['qty'];
    
                // 1) SOURCE: decrement recipe (lock)
                $src = DB::table('bom_items')
                    ->where('bom_header_id', $bom->id)
                    ->where('product_variant_id', $varId)
                    ->lockForUpdate()
                    ->first();
    
                if (!$src) {
                    $sku = DB::table('product_variants')->where('id',$varId)->value('sku') ?? $varId;
                    throw ValidationException::withMessages([
                        'lines' => "Source BOM does not contain item {$sku}.",
                    ]);
                }
    
                DB::table('bom_items')->where('id', $src->id)->update([
                    'qty_per_parent' => DB::raw('qty_per_parent - '.($qty)),
                    'updated_at'     => now(),
                ]);
    
                // 2) DEST: upsert recipe (lock)
                $destItem = DB::table('bom_items')
                    ->where('bom_header_id', $destId)
                    ->where('product_variant_id', $varId)
                    ->lockForUpdate()
                    ->first();
    
                if ($destItem) {
                    DB::table('bom_items')->where('id', $destItem->id)->update([
                        'qty_per_parent' => DB::raw('qty_per_parent + '.($qty)),
                        'updated_at'     => now(),
                    ]);
                } else {
                    DB::table('bom_items')->insert([
                        'bom_header_id'      => $destId,
                        'product_variant_id' => $varId,
                        'qty_per_parent'     => $qty,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);
                }
    
                // 3) DEST: repay any outstanding deficit up to $qty
                $uCost = $this->lastIssueCost($bom->id, $varId)
                       ?? (float) (DB::table('product_variants')->where('id',$varId)->value('price') ?? 0);
    
                app(BomDeficitService::class)->repayIfOutstanding(
                    bomId:       $destId,
                    variantId:   $varId,
                    qty:         $qty,
                    unitCost:    $uCost,
                    refType:     'bom_transfer',
                    refId:       $firstRefId ?: null, // harmless; lets you correlate if you later add adjust rows
                    note:        'Repay via transfer from BOM #'.$bom->id.' to BOM #'.$destId,
                    sourceBomId: $bom->id
                );
    
                // (Optional) If you want a visible audit trail of the movement itself
                // without affecting deficits, you can add 'adjust' rows here.
                // We leave them out to avoid double semantics.
            }
    
            return $firstRefId ?: 0;
        });
    
        return response()->json([
            'message' => 'Items transferred successfully (deficits repaid where applicable).',
            'ref_id'  => $groupRef,
        ]);
    }
    

    // ---------------- helpers ----------------

    /** Availability on a BOM for a variant:
     *   posted issues to this BOM  -  qty already lent out (borrow txns where source_bom_id = this BOM)
     */
    private function availableOnBom(int $bomId, int $variantId): float
    {
        $issued = (float) DB::table('stock_issue_lines as sil')
            ->join('stock_issues as si','si.id','=','sil.stock_issue_id')
            ->where('si.status','posted')
            ->where('si.bom_header_id',$bomId)
            ->where('sil.product_variant_id',$variantId)
            ->sum('sil.qty');

        $lentOut = (float) DB::table('bom_deficit_transactions')
            ->where('source_bom_id', $bomId)
            ->where('product_variant_id', $variantId)
            ->where('direction', 'borrow')   // these are transfers OUT from this BOM
            ->sum('qty');

        $avail = $issued - $lentOut;
        return max(0, round($avail, 4));
    }

    /** Last cost posted to this BOM for the variant (informational) */
    private function lastIssueCost(int $bomId, int $variantId): ?float
    {
        return DB::table('stock_issue_lines as sil')
            ->join('stock_issues as si','si.id','=','sil.stock_issue_id')
            ->where('si.status','posted')
            ->where('si.bom_header_id',$bomId)
            ->where('sil.product_variant_id',$variantId)
            ->orderByDesc('sil.id')
            ->value('sil.unit_cost');
    }
}
