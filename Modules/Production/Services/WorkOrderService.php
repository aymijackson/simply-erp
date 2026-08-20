<?php

namespace Modules\Production\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Orchestrates WO lifecycle: release → start → complete → close.
 * Assumes table names:
 *   work_orders, bom_items, work_order_materials, work_order_steps, work_order_cost_lines, routing_steps
 * Adjust where needed.
 */
class WorkOrderService
{
    public function release(object $wo): void
    {
        if ($wo->status !== 'draft') {
            abort(422, 'Only draft Work Orders can be released.');
        }

        DB::transaction(function () use ($wo) {
            // --- Snapshot BOM -> work_order_materials ---
            $items = DB::table('bom_items')
                ->select('id as bom_item_id', 'product_variant_id', 'qty_per_parent')
                ->where('bom_header_id', $wo->bom_header_id)
                ->get();

            foreach ($items as $it) {
                DB::table('work_order_materials')->insert([
                    'work_order_id'      => $wo->id,
                    'bom_item_id'        => $it->bom_item_id,
                    'product_variant_id' => $it->product_variant_id,
                    'planned_qty'        => (float)$it->qty_per_parent * (float)$wo->quantity_to_produce,
                    'issued_qty'         => 0,
                    'returned_qty'       => 0,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
            }

            // --- Snapshot Routing -> work_order_steps ---
            $steps = DB::table('routing_steps')
                ->select('id as routing_step_id', 'sequence', 'step_name', 'instructions')
                ->where('routing_id', $wo->routing_id)
                ->orderBy('sequence')
                ->get();

            foreach ($steps as $st) {
                DB::table('work_order_steps')->insert([
                    'work_order_id'   => $wo->id,
                    'routing_step_id' => $st->routing_step_id,
                    'sequence'        => (int)$st->sequence,
                    'step_name'       => $st->step_name,
                    'instructions'    => $st->instructions,
                    'status'          => 'pending',
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            DB::table('work_orders')->where('id', $wo->id)->update([
                'status'     => 'released',
                'released_at'=> now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function start(object $wo): void
    {
        if (!in_array($wo->status, ['released', 'paused'], true)) {
            abort(422, 'Work Order must be released or paused to start.');
        }

        DB::table('work_orders')->where('id', $wo->id)->update([
            'status'    => 'in_progress',
            'start_date'=> DB::raw('COALESCE(start_date, NOW())'),
            'updated_at'=> now(),
        ]);
    }

    public function complete(object $wo): array
    {
        if ($wo->status !== 'in_progress') {
            abort(422, 'Work Order must be in progress to complete.');
        }

        return DB::transaction(function () use ($wo) {
            // 1) Ensure all steps done (optional strictness)
            $left = DB::table('work_order_steps')
                ->where('work_order_id', $wo->id)
                ->whereIn('status', ['pending','in_progress','blocked'])
                ->count();
            if ($left > 0) {
                abort(422, 'Not all routing steps are finished.');
            }

            // 2) Compute total extra cost (labour, logistics, fuel, etc.)
            $extraCost = (float) DB::table('work_order_cost_lines')
                ->where('work_order_id', $wo->id)
                ->sum(DB::raw('qty * rate'));

            // 3) Compute material cost:
            // Prefer stock_transactions linked to stock_issues that are linked to THIS WO if column exists.
            $materialCost = 0.0;
            $hasWOonIssues = Schema::hasColumn('stock_issues', 'work_order_id');

            if ($hasWOonIssues) {
                $materialCost = (float) DB::table('stock_transactions as t')
                    ->join('stock_issues as si', function ($j) {
                        $j->on('si.id', '=', 't.txable_id')
                          ->where('t.txable_type', '=', \Modules\Inventory\Models\StockIssue::class);
                    })
                    ->where('si.work_order_id', $wo->id)
                    ->where('t.tx_type', 'ISSUE')
                    ->sum(DB::raw('t.qty * t.unit_cost'));
            } else {
                // Fallback: estimate by planned items * last unit_cost (or variant price)
                $rows = DB::table('work_order_materials as m')
                    ->join('product_variants as v', 'v.id', '=', 'm.product_variant_id')
                    ->leftJoin('products as p', 'p.id', '=', 'v.product_id')
                    ->where('m.work_order_id', $wo->id)
                    ->select('m.product_variant_id', 'm.issued_qty', 'm.planned_qty', 'v.price')
                    ->get();

                foreach ($rows as $r) {
                    $qty = $r->issued_qty ?: $r->planned_qty;
                    $unit = (float) ($r->price ?? 0);
                    $materialCost += ((float)$qty) * $unit;
                }
            }

            $totalCost  = (float)$materialCost + (float)$extraCost;
            $qtyOutput  = (float)$wo->quantity_to_produce;
            $unitCostFG = $qtyOutput > 0 ? ($totalCost / $qtyOutput) : 0.0;

            // NOTE: Work Orders have no target store/warehouse column, and stock_entries.entry_type
            // has no "production receipt" value - there is currently no valid way to receive the
            // finished goods into inventory here. Rather than guess a store or write to columns
            // that don't exist (both of which previously made this a hard SQL failure on every
            // Work Order completion), this step is skipped until that's designed. See WorkOrderService.
            $receiptId = null;

            // 5) Mark WO completed
            DB::table('work_orders')->where('id', $wo->id)->update([
                'status'     => 'completed',
                'end_date'   => now(),
                'updated_at' => now(),
            ]);

            return [
                'material_cost' => round($materialCost, 2),
                'extra_cost'    => round($extraCost, 2),
                'total_cost'    => round($totalCost, 2),
                'unit_cost'     => round($unitCostFG, 4),
                'receipt_id'    => $receiptId,
            ];
        });
    }

    public function close(object $wo): void
    {
        if ($wo->status !== 'completed') {
            abort(422, 'Only completed Work Orders can be closed.');
        }
        DB::table('work_orders')->where('id', $wo->id)->update([
            'status'     => 'closed',
            'updated_at' => now(),
        ]);
    }
}
