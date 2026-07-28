<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FiscalPeriodLockService
{
    /**
     * Throw if date falls in a closed period, or if no period exists for date.
     */
    public static function assertOpen(int $companyId, string $date, ?string $context = null): void
    {
        $period = DB::table('finance_fiscal_periods')
            ->where('company_id', $companyId)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();

        if (!$period) {
            throw ValidationException::withMessages([
                'period' => [sprintf(
                    'No fiscal period found for %s. Please create fiscal periods before posting.%s',
                    $date,
                    $context ? " Context: {$context}" : ''
                )]
            ]);
        }

        if ((int)$period->is_closed === 1) {
            throw ValidationException::withMessages([
                'period' => [sprintf(
                    'Posting blocked. Fiscal period %s (%s to %s) is CLOSED.%s',
                    $period->name ?? ('#'.$period->id),
                    $period->start_date,
                    $period->end_date,
                    $context ? " Context: {$context}" : ''
                )]
            ]);
        }
    }
}
