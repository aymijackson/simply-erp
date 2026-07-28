<?php
// File: Modules/Finance/Services/BankStatementCsvImporter.php

namespace Modules\Finance\Services;

use Illuminate\Http\UploadedFile;
use Modules\Finance\Models\BankReconciliation;
use Modules\Finance\Models\BankStatementLine;

class BankStatementCsvImporter
{
    /**
     * Expected headers (case-insensitive):
     * - date (YYYY-MM-DD or DD/MM/YYYY)
     * - description
     * - amount (signed or unsigned; if unsigned, use separate debit/credit columns not supported here)
     * - reference (optional)
     * - fit_id (optional)
     */
    public function import(BankReconciliation $recon, UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $fh = fopen($path, 'r');
        if (!$fh) throw new \RuntimeException('Unable to read uploaded file.');

        $header = fgetcsv($fh);
        if (!$header) throw new \RuntimeException('CSV header missing.');

        $map = [];
        foreach ($header as $i => $h) {
            $key = strtolower(trim((string)$h));
            $map[$key] = $i;
        }

        foreach (['date','description','amount'] as $required) {
            if (!array_key_exists($required, $map)) {
                throw new \RuntimeException("Missing required CSV column: {$required}");
            }
        }

        $created = 0;
        $skipped = 0;

        while (($row = fgetcsv($fh)) !== false) {
            $raw = $this->rowPayload($header, $row);

            $dateStr = trim((string)$row[$map['date']]);
            $date = $this->parseDate($dateStr);
            if (!$date) { $skipped++; continue; }

            $desc = trim((string)$row[$map['description']]);
            $amount = (float)trim((string)$row[$map['amount']]);

            $reference = isset($map['reference']) ? trim((string)$row[$map['reference']]) : null;
            $fitId = isset($map['fit_id']) ? trim((string)$row[$map['fit_id']]) : null;

            // Basic dup check if fit_id provided
            if ($fitId) {
                $exists = BankStatementLine::query()
                    ->where('company_id', $recon->company_id)
                    ->where('fit_id', $fitId)
                    ->whereNull('deleted_at')
                    ->exists();
                if ($exists) { $skipped++; continue; }
            }

            BankStatementLine::query()->create([
                'company_id' => $recon->company_id,
                'reconciliation_id' => $recon->id,
                'txn_date' => $date,
                'description' => $desc ?: '(no description)',
                'reference' => $reference ?: null,
                'amount' => $amount,
                'fit_id' => $fitId ?: null,
                'raw_payload' => $raw,
                'status' => 'unmatched',
            ]);

            $created++;
        }

        fclose($fh);

        return ['created' => $created, 'skipped' => $skipped];
    }

    private function rowPayload(array $header, array $row): array
    {
        $payload = [];
        foreach ($header as $i => $h) {
            $payload[(string)$h] = $row[$i] ?? null;
        }
        return $payload;
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') return null;

        // Try YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return $value;

        // Try DD/MM/YYYY
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        // Fallback strtotime
        $ts = strtotime($value);
        if ($ts === false) return null;
        return date('Y-m-d', $ts);
    }
}