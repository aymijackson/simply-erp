<?php

namespace Modules\Production\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * GL posting for the manufacturing cycle. Mirrors the shape of
 * Modules\Finance\Services\Posting\* (draft/posted journal on
 * finance_journal_entries + finance_journal_entry_lines), since Manufacturing
 * previously had no Finance integration at all.
 *
 * Material issue/return:  DR/CR WIP <-> Raw Materials Inventory (inventory_asset_account_id)
 * Work Order completion:  DR Finished Goods, CR WIP (material) + CR Operating Expenses (labour/overhead)
 */
class WorkOrderPostingService
{
    public static function postMaterialIssue(int $companyId, int $workOrderId, float $qty, float $unitCost, string $reference): ?int
    {
        $amount = round($qty * $unitCost, 2);
        if ($amount <= 0) {
            return null;
        }

        $wipId = self::resolveAccount($companyId, 'wip_account_id', 'Work in Progress');
        $rawId = self::resolveAccount($companyId, 'inventory_asset_account_id', 'Inventory Asset');

        return self::postTwoLineEntry($companyId, $workOrderId, 'wo_material_issue', $reference, 'Material issued to WIP', $wipId, $rawId, $amount);
    }

    public static function postMaterialReturn(int $companyId, int $workOrderId, float $qty, float $unitCost, string $reference): ?int
    {
        $amount = round($qty * $unitCost, 2);
        if ($amount <= 0) {
            return null;
        }

        $wipId = self::resolveAccount($companyId, 'wip_account_id', 'Work in Progress');
        $rawId = self::resolveAccount($companyId, 'inventory_asset_account_id', 'Inventory Asset');

        return self::postTwoLineEntry($companyId, $workOrderId, 'wo_material_return', $reference, 'Material returned from WIP', $rawId, $wipId, $amount);
    }

    public static function postCompletion(int $companyId, int $workOrderId, float $materialCost, float $extraCost, string $reference): ?int
    {
        $materialCost = round($materialCost, 2);
        $extraCost = round($extraCost, 2);
        $total = round($materialCost + $extraCost, 2);

        if ($total <= 0) {
            return null;
        }

        $fgId = self::resolveAccount($companyId, 'finished_goods_account_id', 'Finished Goods');
        $wipId = self::resolveAccount($companyId, 'wip_account_id', 'Work in Progress');
        $opexId = $extraCost > 0 ? self::resolveOperatingExpensesAccount($companyId) : null;

        return DB::transaction(function () use ($companyId, $workOrderId, $materialCost, $extraCost, $total, $reference, $fgId, $wipId, $opexId) {
            $jeId = DB::table('finance_journal_entries')->insertGetId([
                'company_id'  => $companyId,
                'entry_date'  => now()->toDateString(),
                'reference'   => $reference,
                'memo'        => 'Work Order completion - move WIP to Finished Goods',
                'status'      => 'posted',
                'source_type' => 'work_order_completion',
                'source_id'   => $workOrderId,
                'posted_at'   => now(),
                'posted_by'   => Auth::id(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            $lines = [[
                'journal_entry_id' => $jeId,
                'account_id'       => $fgId,
                'description'      => 'Finished goods received',
                'debit'            => $total,
                'credit'           => 0,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]];

            if ($materialCost > 0) {
                $lines[] = [
                    'journal_entry_id' => $jeId,
                    'account_id'       => $wipId,
                    'description'      => 'Material cost cleared from WIP',
                    'debit'            => 0,
                    'credit'           => $materialCost,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ];
            }

            if ($extraCost > 0) {
                $lines[] = [
                    'journal_entry_id' => $jeId,
                    'account_id'       => $opexId,
                    'description'      => 'Labour/overhead absorbed into product cost',
                    'debit'            => 0,
                    'credit'           => $extraCost,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ];
            }

            DB::table('finance_journal_entry_lines')->insert($lines);

            DB::table('work_orders')->where('id', $workOrderId)->update([
                'completion_journal_entry_id' => $jeId,
            ]);

            return $jeId;
        });
    }

    private static function postTwoLineEntry(
        int $companyId,
        int $workOrderId,
        string $sourceType,
        string $reference,
        string $memo,
        int $debitAccountId,
        int $creditAccountId,
        float $amount
    ): int {
        return DB::transaction(function () use ($companyId, $workOrderId, $sourceType, $reference, $memo, $debitAccountId, $creditAccountId, $amount) {
            $jeId = DB::table('finance_journal_entries')->insertGetId([
                'company_id'  => $companyId,
                'entry_date'  => now()->toDateString(),
                'reference'   => $reference,
                'memo'        => $memo,
                'status'      => 'posted',
                'source_type' => $sourceType,
                'source_id'   => $workOrderId,
                'posted_at'   => now(),
                'posted_by'   => Auth::id(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            DB::table('finance_journal_entry_lines')->insert([
                [
                    'journal_entry_id' => $jeId,
                    'account_id'       => $debitAccountId,
                    'debit'            => $amount,
                    'credit'           => 0,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ],
                [
                    'journal_entry_id' => $jeId,
                    'account_id'       => $creditAccountId,
                    'debit'            => 0,
                    'credit'           => $amount,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ],
            ]);

            return $jeId;
        });
    }

    private static function resolveAccount(int $companyId, string $column, string $label): int
    {
        $id = (int) (DB::table('finance_account_mappings')->where('company_id', $companyId)->value($column) ?? 0);

        if ($id <= 0) {
            throw new \RuntimeException("{$label} account is not configured. Set it under Finance > Account Mappings.");
        }

        return $id;
    }

    private static function resolveOperatingExpensesAccount(int $companyId): int
    {
        $id = (int) (DB::table('finance_accounts')
            ->where('company_id', $companyId)
            ->where('code', '6000')
            ->value('id') ?? 0);

        if ($id <= 0) {
            throw new \RuntimeException('Operating Expenses account (code 6000) was not found for this company.');
        }

        return $id;
    }
}
