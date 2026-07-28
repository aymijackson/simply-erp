<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashFlowController extends Controller
{
    public function index()
    {
        return view('finance.cash_flow.index');
    }

    public function run(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $request->validate([
            'date_from' => ['required', 'date'],
            'date_to'   => ['required', 'date', 'after_or_equal:date_from'],
        ]);

        $from = $request->date_from;
        $to   = $request->date_to;

        $map = DB::table('finance_account_mappings')
            ->where('company_id', $companyId)
            ->first();

        if (!$map) {
            return response()->json([
                'ok' => false,
                'message' => 'Finance account mappings not configured.',
            ], 422);
        }

        $cashAccountIds = $this->getCashAccountIds($map);

        if (empty($cashAccountIds)) {
            return response()->json([
                'ok' => false,
                'message' => 'No cash/bank account mapped. Set default_bank_gl_account_id in finance_account_mappings.',
            ], 422);
        }

        $profit = $this->computeProfit($companyId, $from, $to);
        $wc = $this->computeWorkingCapital($companyId, $from, $to, $map);

        $classified = $this->computeCashflowMappedSections($companyId, $from, $to, $cashAccountIds);

        $nonCashTotal = (float)($classified['non_cash_total'] ?? 0);
        $investingTotal = (float)($classified['investing_total'] ?? 0);
        $financingTotal = (float)($classified['financing_total'] ?? 0);

        $netCashFromOps = $profit + $nonCashTotal + (float)$wc['working_capital_total'];
        $netChangeInCash = $netCashFromOps + $investingTotal + $financingTotal;

        $cashAccountsMovement = $this->getCashMovement($companyId, $cashAccountIds, $from, $to);

        $summary = [
            'profit' => round($profit, 2),
            'non_cash_total' => round($nonCashTotal, 2),
            'working_capital_total' => round((float)$wc['working_capital_total'], 2),
            'net_cash_from_ops' => round($netCashFromOps, 2),
            'net_cash_from_investing' => round($investingTotal, 2),
            'net_cash_from_financing' => round($financingTotal, 2),
            'net_change_in_cash' => round($netChangeInCash, 2),
            'cash_accounts_movement' => round($cashAccountsMovement, 2),
            'difference' => round($netChangeInCash - $cashAccountsMovement, 2),
        ];

        $details = [
            'working_capital' => $wc,
            'operating' => $classified['operating_items'] ?? [],
            'investing' => $classified['investing_items'] ?? [],
            'financing' => $classified['financing_items'] ?? [],
            'non_cash' => $classified['non_cash_items'] ?? [],
            'notes' => $classified['notes'] ?? [],
        ];

        return response()->json([
            'ok' => true,
            'meta' => [
                'date_from' => $from,
                'date_to' => $to,
            ],
            'summary' => $summary,
            'summary_formatted' => $this->formatArrayNumbers($summary),
            'details' => $details,
            'details_formatted' => [
                'working_capital' => $this->formatArrayNumbers($details['working_capital']),
                'operating' => $this->formatItems($details['operating']),
                'investing' => $this->formatItems($details['investing']),
                'financing' => $this->formatItems($details['financing']),
                'non_cash' => $this->formatItems($details['non_cash']),
                'notes' => $details['notes'],
            ],
        ]);
    }

    public function mappingsIndex()
    {
        return view('finance.cash_flow.mappings');
    }

    public function mappingsData()
    {
        $companyId = auth()->user()->company_id ?? 1;

        if (!$this->tableExists('finance_cashflow_mappings')) {
            return response()->json(['ok' => true, 'data' => []]);
        }

        $rows = DB::table('finance_cashflow_mappings as m')
            ->join('finance_accounts as a', 'a.id', '=', 'm.gl_account_id')
            ->where('m.company_id', $companyId)
            ->orderBy('a.code')
            ->get([
                'm.id',
                'm.gl_account_id',
                'm.section',
                'm.label',
                'm.is_active',
                'a.code as gl_code',
                'a.name as gl_name',
            ]);

        return response()->json(['ok' => true, 'data' => $rows]);
    }

    public function mappingsStore(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $request->validate([
            'gl_account_id' => ['required', 'integer', 'exists:finance_accounts,id'],
            'section' => ['required', 'in:operating,investing,financing,non_cash'],
            'label' => ['nullable', 'string', 'max:150'],
        ]);

        if (!$this->tableExists('finance_cashflow_mappings')) {
            return response()->json([
                'ok' => false,
                'message' => 'finance_cashflow_mappings table not found.',
            ], 422);
        }

        DB::table('finance_cashflow_mappings')->updateOrInsert(
            [
                'company_id' => $companyId,
                'gl_account_id' => (int)$request->gl_account_id,
            ],
            [
                'section' => $request->section,
                'label' => $request->label,
                'is_active' => 1,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json([
            'ok' => true,
            'message' => 'Mapping saved.',
        ]);
    }

    public function mappingsDelete($id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        if (!$this->tableExists('finance_cashflow_mappings')) {
            return response()->json([
                'ok' => false,
                'message' => 'finance_cashflow_mappings table not found.',
            ], 422);
        }

        DB::table('finance_cashflow_mappings')
            ->where('company_id', $companyId)
            ->where('id', (int)$id)
            ->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Mapping removed.',
        ]);
    }

    public function lookupGl(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $q = trim((string)$request->q);

        $rows = DB::table('finance_accounts as a')
            ->where('a.company_id', $companyId)
            ->whereNull('a.deleted_at')
            ->when($q !== '', function ($x) use ($q) {
                $x->where(function ($y) use ($q) {
                    $y->where('a.code', 'like', "%{$q}%")
                        ->orWhere('a.name', 'like', "%{$q}%");
                });
            })
            ->orderBy('a.code')
            ->limit(30)
            ->get([
                'a.id',
                DB::raw("CONCAT(a.code,' - ',a.name) as text"),
            ]);

        return response()->json(['results' => $rows]);
    }

    private function computeProfit(int $companyId, string $from, string $to): float
    {
        $rows = DB::table('finance_journal_entry_lines as l')
            ->join('finance_journal_entries as j', 'j.id', '=', 'l.journal_entry_id')
            ->join('finance_accounts as a', 'a.id', '=', 'l.account_id')
            ->join('finance_account_types as t', 't.id', '=', 'a.account_type_id')
            ->where('j.company_id', $companyId)
            ->where('j.status', 'posted')
            ->whereBetween('j.entry_date', [$from, $to])
            ->whereIn('t.category', ['income', 'expense'])
            ->get([
                't.category',
                'l.debit',
                'l.credit',
            ]);

        $income = 0.0;
        $expense = 0.0;

        foreach ($rows as $r) {
            if ($r->category === 'income') {
                $income += ((float)$r->credit - (float)$r->debit);
            }

            if ($r->category === 'expense') {
                $expense += ((float)$r->debit - (float)$r->credit);
            }
        }

        return round($income - $expense, 2);
    }

    private function computeWorkingCapital(int $companyId, string $from, string $to, object $map): array
    {
        $arId = !empty($map->ar_account_id) ? (int)$map->ar_account_id : null;
        $apId = !empty($map->ap_account_id) ? (int)$map->ap_account_id : null;
        $vatOutputId = !empty($map->vat_output_account_id) ? (int)$map->vat_output_account_id : null;
        $inventoryId = !empty($map->inventory_asset_account_id) ? (int)$map->inventory_asset_account_id : null;

        $arChangeGross = $this->balanceChange($companyId, $arId, $from, $to);
        $vatChangeRaw = $this->balanceChange($companyId, $vatOutputId, $from, $to);
        $inventoryChange = $this->balanceChange($companyId, $inventoryId, $from, $to);
        $apChange = $this->balanceChange($companyId, $apId, $from, $to);

        $vatOutputMovement = abs($vatChangeRaw);
        $arChangeNet = $arChangeGross - $vatOutputMovement;

        $workingCapitalTotal =
            (-1 * $arChangeNet) +
            (-1 * $inventoryChange) +
            ($apChange);

        return [
            'ar_account_id' => $arId,
            'ap_account_id' => $apId,
            'vat_account_id' => $vatOutputId,
            'inventory_account_id' => $inventoryId,
            'ar_change_gross' => round($arChangeGross, 2),
            'vat_change' => round($vatOutputMovement, 2),
            'ar_change' => round($arChangeNet, 2),
            'inventory_change' => round($inventoryChange, 2),
            'ap_change' => round($apChange, 2),
            'working_capital_total' => round($workingCapitalTotal, 2),
            'notes' => 'AR movement shown net of VAT using finance_account_mappings control accounts.',
        ];
    }

    /**
     * Intelligent classification using finance_cashflow_mappings.
     *
     * Logic:
     * - Find posted JEs in date range.
     * - For each JE, calculate net movement on mapped cash/bank accounts.
     * - If JE has cash impact, inspect the non-cash side.
     * - If non-cash side accounts are mapped in finance_cashflow_mappings,
     *   allocate the JE cash impact across those mapped accounts by weight.
     * - This makes equipment purchases land in INVESTING if the equipment account
     *   is mapped as investing.
     */
    private function computeCashflowMappedSections(int $companyId, string $from, string $to, array $cashAccountIds): array
    {
        $out = [
            'operating_items' => [],
            'investing_items' => [],
            'financing_items' => [],
            'non_cash_items' => [],
            'operating_total' => 0.0,
            'investing_total' => 0.0,
            'financing_total' => 0.0,
            'non_cash_total' => 0.0,
            'notes' => [],
        ];

        if (!$this->tableExists('finance_cashflow_mappings')) {
            $out['notes'][] = 'finance_cashflow_mappings table not found. Investing/Financing/Non-cash sections are empty.';
            return $out;
        }

        $mappingRows = DB::table('finance_cashflow_mappings')
            ->where('company_id', $companyId)
            ->where('is_active', 1)
            ->get([
                'gl_account_id',
                'section',
                'label',
            ]);

        if ($mappingRows->isEmpty()) {
            $out['notes'][] = 'No active cash flow mappings found. Investing/Financing/Non-cash sections are empty.';
            return $out;
        }

        $map = [];
        foreach ($mappingRows as $row) {
            $map[(int)$row->gl_account_id] = [
                'section' => $row->section,
                'label' => $row->label,
            ];
        }

        $rows = DB::table('finance_journal_entry_lines as l')
            ->join('finance_journal_entries as j', 'j.id', '=', 'l.journal_entry_id')
            ->join('finance_accounts as a', 'a.id', '=', 'l.account_id')
            ->where('j.company_id', $companyId)
            ->where('j.status', 'posted')
            ->whereBetween('j.entry_date', [$from, $to])
            ->get([
                'j.id as je_id',
                'j.entry_date',
                'l.account_id',
                'a.code',
                'a.name',
                'l.debit',
                'l.credit',
            ]);

        $entries = [];
        foreach ($rows as $r) {
            $entries[(int)$r->je_id][] = $r;
        }

        foreach ($entries as $jeId => $lines) {
            $cashNet = 0.0;
            $mappedCounterLines = [];
            $nonCashMappedCount = 0;

            foreach ($lines as $ln) {
                $accountId = (int)$ln->account_id;
                $net = (float)$ln->debit - (float)$ln->credit;

                if (in_array($accountId, $cashAccountIds, true)) {
                    $cashNet += $net;
                    continue;
                }

                if (isset($map[$accountId])) {
                    $weight = abs($net);

                    // if weight is zero for some strange reason, still keep a tiny weight
                    if ($weight <= 0) {
                        $weight = 1;
                    }

                    $mappedCounterLines[] = [
                        'account_id' => $accountId,
                        'section' => $map[$accountId]['section'],
                        'label' => $map[$accountId]['label'] ?: trim(($ln->code ?? '') . ' - ' . ($ln->name ?? '')),
                        'weight' => $weight,
                    ];

                    $nonCashMappedCount++;
                }
            }

            // No real cash impact => skip
            if (abs($cashNet) < 0.0001) {
                continue;
            }

            // No mapped counterpart accounts => skip JE for mapped sections
            if ($nonCashMappedCount === 0) {
                continue;
            }

            $totalWeight = array_sum(array_column($mappedCounterLines, 'weight'));
            if ($totalWeight <= 0) {
                $totalWeight = $nonCashMappedCount;
            }

            foreach ($mappedCounterLines as $m) {
                $allocated = $cashNet * ($m['weight'] / $totalWeight);

                $item = [
                    'label' => $m['label'],
                    'amount' => round($allocated, 2),
                ];

                switch ($m['section']) {
                    case 'operating':
                        $out['operating_items'][] = $item;
                        $out['operating_total'] += $allocated;
                        break;

                    case 'investing':
                        $out['investing_items'][] = $item;
                        $out['investing_total'] += $allocated;
                        break;

                    case 'financing':
                        $out['financing_items'][] = $item;
                        $out['financing_total'] += $allocated;
                        break;

                    case 'non_cash':
                    default:
                        $out['non_cash_items'][] = $item;
                        $out['non_cash_total'] += $allocated;
                        break;
                }
            }
        }

        // Merge duplicate labels for cleaner UI
        $out['operating_items'] = $this->mergeItemsByLabel($out['operating_items']);
        $out['investing_items'] = $this->mergeItemsByLabel($out['investing_items']);
        $out['financing_items'] = $this->mergeItemsByLabel($out['financing_items']);
        $out['non_cash_items'] = $this->mergeItemsByLabel($out['non_cash_items']);

        $out['operating_total'] = round($out['operating_total'], 2);
        $out['investing_total'] = round($out['investing_total'], 2);
        $out['financing_total'] = round($out['financing_total'], 2);
        $out['non_cash_total'] = round($out['non_cash_total'], 2);

        return $out;
    }

    private function mergeItemsByLabel(array $items): array
    {
        $merged = [];

        foreach ($items as $item) {
            $label = (string)($item['label'] ?? 'Unknown');
            $amount = (float)($item['amount'] ?? 0);

            if (!isset($merged[$label])) {
                $merged[$label] = [
                    'label' => $label,
                    'amount' => 0.0,
                ];
            }

            $merged[$label]['amount'] += $amount;
        }

        foreach ($merged as &$row) {
            $row['amount'] = round($row['amount'], 2);
        }

        return array_values($merged);
    }

    private function balanceChange(int $companyId, ?int $accountId, string $from, string $to): float
    {
        if (!$accountId) {
            return 0.0;
        }

        $opening = $this->balanceBefore($companyId, $accountId, $from);
        $closing = $this->balanceBefore(
            $companyId,
            $accountId,
            date('Y-m-d', strtotime($to . ' +1 day'))
        );

        return round($closing - $opening, 2);
    }

    private function balanceBefore(int $companyId, int $accountId, string $date): float
    {
        $row = DB::table('finance_journal_entry_lines as l')
            ->join('finance_journal_entries as j', 'j.id', '=', 'l.journal_entry_id')
            ->where('j.company_id', $companyId)
            ->where('j.status', 'posted')
            ->where('l.account_id', $accountId)
            ->where('j.entry_date', '<', $date)
            ->selectRaw('COALESCE(SUM(l.debit - l.credit), 0) as bal')
            ->first();

        return round((float)($row->bal ?? 0), 2);
    }

    private function getCashMovement(int $companyId, array $cashAccountIds, string $from, string $to): float
    {
        if (empty($cashAccountIds)) {
            return 0.0;
        }

        $row = DB::table('finance_journal_entry_lines as l')
            ->join('finance_journal_entries as j', 'j.id', '=', 'l.journal_entry_id')
            ->where('j.company_id', $companyId)
            ->where('j.status', 'posted')
            ->whereBetween('j.entry_date', [$from, $to])
            ->whereIn('l.account_id', $cashAccountIds)
            ->selectRaw('COALESCE(SUM(l.debit - l.credit), 0) as total')
            ->first();

        return round((float)($row->total ?? 0), 2);
    }

    private function getCashAccountIds(object $map): array
    {
        $ids = [];

        if (!empty($map->default_bank_gl_account_id)) {
            $ids[] = (int)$map->default_bank_gl_account_id;
        }

        if ($this->tableExists('finance_bank_accounts') && $this->columnExists('finance_bank_accounts', 'gl_account_id')) {
            $bankIds = DB::table('finance_bank_accounts')
                ->where('company_id', $map->company_id)
                ->when($this->columnExists('finance_bank_accounts', 'deleted_at'), function ($q) {
                    $q->whereNull('deleted_at');
                })
                ->whereNotNull('gl_account_id')
                ->pluck('gl_account_id')
                ->map(fn ($x) => (int)$x)
                ->all();

            $ids = array_merge($ids, $bankIds);
        }

        return array_values(array_unique(array_filter($ids)));
    }

    private function formatMoney($amount, bool $parenthesesForNegative = true): string
    {
        $amount = (float)$amount;

        if ($parenthesesForNegative && $amount < 0) {
            return '(' . number_format(abs($amount), 2) . ')';
        }

        return number_format($amount, 2);
    }

    private function formatArrayNumbers(array $data, bool $parenthesesForNegative = true): array
    {
        $formatted = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $formatted[$key] = $this->formatArrayNumbers($value, $parenthesesForNegative);
            } elseif (is_numeric($value)) {
                $formatted[$key] = $this->formatMoney((float)$value, $parenthesesForNegative);
            } else {
                $formatted[$key] = $value;
            }
        }

        return $formatted;
    }

    private function formatItems(array $items): array
    {
        return array_map(function ($item) {
            return [
                'label' => $item['label'] ?? '',
                'amount' => $item['amount'] ?? 0,
                'amount_formatted' => $this->formatMoney((float)($item['amount'] ?? 0), true),
            ];
        }, $items);
    }

    private function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            return DB::getSchemaBuilder()->hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }
}