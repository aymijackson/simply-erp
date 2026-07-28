<?php

namespace Modules\Inventory\Services;

use Modules\Inventory\Models\{StockTransfer, StockTransferLine, StockTransaction};
use Modules\Inventory\Models\Product\{ProductVariant, Product};
use Illuminate\Support\Facades\DB;

class TransferService
{
    /* ---------- public API ---------- */

    public function create(array $hdr, array $lines): StockTransfer
    {
        return DB::transaction(function () use ($hdr, $lines) {
            $hdr += [
                'transfer_no' => $hdr['transfer_no'] ?? $this->nextNo(),
                'status'      => 'draft',
            ];

            $trf = StockTransfer::create($hdr);
            $trf->lines()->createMany($lines);

            return $trf->load('lines.variant');
        });
    }

    public function post(StockTransfer $trf): void
    {
        abort_if($trf->status !== 'draft', 400, 'Already posted');

        DB::transaction(function () use ($trf) {

            /* -------  A  check balances  -------- */
            foreach ($trf->lines as $l) {
                /** current on-hand in FROM store */
                $onHand = DB::table('v_stock_levels')
                            ->where([
                                'product_variant_id' => $l->product_variant_id,
                                'location_store_id'  => $trf->from_store_id,
                            ])
                            ->lockForUpdate()
                            ->value('qty_on_hand') ?? 0;

                if ($onHand < $l->qty) {
                    throw new \RuntimeException(
                        "Insufficient stock for SKU {$l->variant->sku} in {$trf->fromStore->name}. "
                      . "On-hand: {$onHand}, requested: {$l->qty}"
                    );
                }
            }

            /* -------  B  write both legs & update running balance  -------- */
            foreach ($trf->lines as $l) {
                // OUT
                $this->ledger('TRANSFER_OUT', $trf->from_store_id, $l, -1);
                // IN
                $this->ledger('TRANSFER_IN',  $trf->to_store_id,   $l, +1);
            }

            $trf->update([
                'status'      => 'posted',
                'posted_at'   => now(),
                'approved_by' => auth()->id(),
            ]);
        });
    }

    /* ---------- helpers ---------- */

    /**
     * Write one ledger row **and** adjust the global running quantity
     *
     * @param int $sign  –1 for OUT, +1 for IN
     */
    protected function ledger(string $type, int $storeId, StockTransferLine $l, int $sign): void
    {
        StockTransaction::create([
            'product_variant_id' => $l->product_variant_id,
            'location_store_id'  => $storeId,
            'tx_type'            => $type,
            'qty'                => $l->qty,        // always positive
            'unit_cost'          => $l->unit_cost,
            'txable_type'        => get_class($l->transfer),
            'txable_id'          => $l->transfer->id,
            'tx_date'            => now(),
        ]);

        // // ---- update global running balance (redundant column) ----
        // ProductVariant::whereKey($l->product_variant_id)
        //       ->lockForUpdate()
        //       ->increment('stock_quantity', $sign * $l->qty);
    }

    protected function nextNo(): string
    {
        $seq = StockTransfer::max('id') + 1;
        return 'TRF-' . date('Y') . '-' . sprintf('%05d', $seq);
    }
}
