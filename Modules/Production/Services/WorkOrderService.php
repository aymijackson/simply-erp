<?php

namespace Modules\Production\Services;

use Illuminate\Support\Facades\DB;
use Modules\Production\Services\WorkOrderPostingService;

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

            // 3) Material cost: the running total actually posted to WIP as materials were
            // issued (issued - returned, at the unit cost snapshotted on first issue). This is
            // the same number WorkOrderPostingService::postMaterialIssue/Return debited/credited
            // WIP with, so it's what has to be cleared out of WIP below - not a separate estimate.
            $materialCost = (float) DB::table('work_order_materials')
                ->where('work_order_id', $wo->id)
                ->selectRaw('SUM((issued_qty - returned_qty) * COALESCE(unit_cost, 0)) as total')
                ->value('total');

            $totalCost  = (float)$materialCost + (float)$extraCost;
            $qtyOutput  = (float)$wo->quantity_to_produce;
            $unitCostFG = $qtyOutput > 0 ? ($totalCost / $qtyOutput) : 0.0;

            $companyId = (int) ($wo->company_id ?? 1);

            $journalEntryId = WorkOrderPostingService::postCompletion(
                $companyId,
                $wo->id,
                $materialCost,
                $extraCost,
                'WO#'.$wo->id.' completion'
            );

            // 5) Mark WO completed
            DB::table('work_orders')->where('id', $wo->id)->update([
                'status'       => 'completed',
                'end_date'     => now(),
                'material_cost'=> round($materialCost, 2),
                'total_cost'   => round($totalCost, 2),
                'costs_materials' => round($materialCost, 2),
                'costs_total'  => round($totalCost, 2),
                'updated_at'   => now(),
            ]);

            return [
                'material_cost' => round($materialCost, 2),
                'extra_cost'    => round($extraCost, 2),
                'total_cost'    => round($totalCost, 2),
                'unit_cost'     => round($unitCostFG, 4),
                'journal_entry_id' => $journalEntryId,
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
