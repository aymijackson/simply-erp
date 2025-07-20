<?php

namespace Modules\Inventory\Services;

use Modules\Inventory\Models\{StockTransfer, StockTransferLine, StockTransaction};
use Modules\Inventory\Models\Product\{ProductVariant, Product};
use Illuminate\Support\Facades\DB;

class TransferService
{
    public function create(array $hdr, array $lines): StockTransfer
    {
        return DB::transaction(function () use ($hdr,$lines) {
            $hdr['transfer_no'] = $hdr['transfer_no'] ?? $this->nextNo();
            $hdr['status']      = 'draft';

            $trf = StockTransfer::create($hdr);
            foreach ($lines as $l) {
                $trf->lines()->create($l);
            }
            return $trf->load('lines.variant');
        });
    }

    public function post(StockTransfer $trf): void
    {
        abort_if($trf->status !== 'draft', 400, 'Already posted');

        DB::transaction(function () use ($trf) {
            foreach ($trf->lines as $l) {
                $this->ledger('TRANSFER_OUT', $trf->from_store_id, $l);
                $this->ledger('TRANSFER_IN',  $trf->to_store_id,   $l);
            }
            $trf->update(['status'=>'posted','posted_at'=>now(),'approved_by'=>auth()->id()]);
        });
    }

    /* -------------------- helpers -------------------- */

    protected function ledger(string $type, int $storeId, StockTransferLine $l): void
    {
        $sign = $type === 'TRANSFER_OUT' ? -1 : 1;

        StockTransaction::create([
            'product_variant_id' => $l->product_variant_id,
            'store_id'           => $storeId,
            'tx_type'            => $type,
            'qty'                => $sign * $l->qty,
            'unit_cost'          => $l->unit_cost,
            'posted_at'          => now(),
        ]);

        ProductVariant::whereKey($l->product_variant_id)
             ->lockForUpdate()
             ->increment('stock_quantity', $sign * $l->qty);
    }

    protected function nextNo(): string
    {
        $seq = StockTransfer::max('id') + 1;
        return 'TRF-'.date('Y').'-'.sprintf('%05d',$seq);
    }
}
