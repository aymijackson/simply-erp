<?php

namespace Modules\Finance\Services\Posting;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesInvoicePostingService
{
    public static function post(int $companyId, int $salesInvoiceId): int
    {
        return DB::transaction(function () use ($companyId, $salesInvoiceId) {

            // Idempotency: already posted?
            $existing = DB::table('finance_posting_links')
                ->where('company_id', $companyId)
                ->where('source_type', 'sales_invoice')
                ->where('source_id', $salesInvoiceId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return (int)$existing->journal_entry_id;
            }

            $si = DB::table('sales_invoices')
                //->where('company_id', $companyId)
                ->where('id', $salesInvoiceId)
                ->lockForUpdate()
                ->first();

            if (!$si) {
                throw ValidationException::withMessages(['invoice' => ['Sales invoice not found.']]);
            }

            if (empty($si->posted_at)) {
                throw ValidationException::withMessages(['invoice' => ['Invoice must be posted in Sales before Finance posting.']]);
            }

            // Period lock enforcement (hard rule)
            self::assertDatePostable($companyId, $si->invoice_date);

            $map = DB::table('finance_account_mappings')
    ->where('company_id', $companyId)
    ->first();

            if (!$map) {
                throw ValidationException::withMessages([
                    'mapping' => ['Finance account mappings not configured for company.']
                ]);
            }
            
            $arId  = $map->ar_account_id ?? null;
            $revId = $map->sales_revenue_account_id ?? null;
            $vatId = $map->vat_output_account_id ?? null;
            
            if (!$arId || !$revId) {
                throw ValidationException::withMessages([
                    'mapping' => ['Set AR and Sales Revenue accounts in Finance mappings.']
                ]);
            }

            $arId    = $map->ar_account_id ?? null;
            $revId   = $map->sales_revenue_account_id ?? null;
            $vatId   = $map->vat_output_account_id ?? null;

            if (!$arId || !$revId) {
                throw ValidationException::withMessages(['mapping' => ['Set AR and Sales Revenue accounts in Finance mappings.']]);
            }

            // Amounts
            $grandTotal = (float)($si->grand_total ?? 0);
            $subTotal   = (float)($si->sub_total ?? ($si->subtotal ?? 0));
            $vatTotal   = (float)($si->vat_total ?? ($si->tax_total ?? 0));

            // Fallback if you only store grand_total and vat_total
            if ($subTotal <= 0 && $grandTotal > 0) {
                $subTotal = max(0, $grandTotal - $vatTotal);
            }

            if (round($grandTotal, 2) <= 0) {
                throw ValidationException::withMessages(['invoice' => ['Invoice amount is zero; nothing to post.']]);
            }

            // Create JE
            $entryNo = 'SI-' . $salesInvoiceId . '-' . date('YmdHis');

            $jeId = DB::table('finance_journal_entries')->insertGetId([
                'company_id' => $companyId,
                'period_id'  => self::resolvePeriodId($companyId, $si->invoice_date), // ok if null in your schema
                'entry_no'   => $entryNo,
                'entry_date' => $si->invoice_date,
                'memo'       => 'Sales Invoice #' . ($si->invoice_no ?? $salesInvoiceId),
                'status'     => 'posted',
                'posted_at'  => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $lines = [];

            // DR AR = grand_total
            $lines[] = [
                'journal_entry_id' => $jeId,
                'account_id'       => $arId,
                'debit'            => $grandTotal,
                'credit'           => 0,
                'memo'             => 'Accounts Receivable',
                'created_at'       => now(),
                'updated_at'       => now(),
            ];

            // CR Revenue = sub_total
            if (round($subTotal, 2) > 0) {
                $lines[] = [
                    'journal_entry_id' => $jeId,
                    'account_id'       => $revId,
                    'debit'            => 0,
                    'credit'           => $subTotal,
                    'memo'             => 'Sales Revenue',
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ];
            }

            // CR VAT Output if applicable
            if (round($vatTotal, 2) > 0) {
                if (!$vatId) {
                    throw ValidationException::withMessages(['mapping' => ['Invoice has VAT but VAT Output account is not set.']]);
                }
                $lines[] = [
                    'journal_entry_id' => $jeId,
                    'account_id'       => $vatId,
                    'debit'            => 0,
                    'credit'           => $vatTotal,
                    'memo'             => 'VAT Output',
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ];
            }

            DB::table('finance_journal_entry_lines')->insert($lines);

            // Save link to prevent double posting
            DB::table('finance_posting_links')->insert([
                'company_id'       => $companyId,
                'source_type'      => 'sales_invoice',
                'source_id'        => $salesInvoiceId,
                'journal_entry_id' => $jeId,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            return (int)$jeId;
        });
    }

    private static function resolvePeriodId(int $companyId, string $date): ?int
    {
        $p = DB::table('finance_fiscal_periods')
            ->where('company_id', $companyId)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->orderBy('start_date')
            ->first();

        return $p?->id ? (int)$p->id : null;
    }

    private static function assertDatePostable(int $companyId, string $date): void
    {
        // Hard block if inside closed period
        $p = DB::table('finance_fiscal_periods')
            ->where('company_id', $companyId)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();

        if ($p && (int)($p->is_closed ?? 0) === 1) {
            throw ValidationException::withMessages([
                'period' => ['Posting blocked: fiscal period is closed for this date.']
            ]);
        }

        // Optional lock_date enforcement if you store finance_company_settings.lock_date
        $settings = DB::table('finance_company_settings')->where('company_id', $companyId)->first();
        $lockDate = $settings->lock_date ?? null;

        if ($lockDate && $date <= $lockDate) {
            throw ValidationException::withMessages([
                'lock_date' => ["Posting blocked: lock date is {$lockDate}."]
            ]);
        }
    }
}