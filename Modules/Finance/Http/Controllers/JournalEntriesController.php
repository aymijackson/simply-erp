<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Finance\Models\FinanceJournalEntryLine;

class JournalEntriesController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id ?? 1;
        return view('finance.journal_entries.index', compact('companyId'));
    }

    public function datatable(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $canAudit  = auth()->user()->can('finance.journals.audit');
    
        $q = DB::table('finance_journal_entries as je')
            ->leftJoin('users as pu', 'pu.id', '=', 'je.posted_by')
            ->leftJoin('users as ru', 'ru.id', '=', 'je.reversed_by')
            ->where('je.company_id', $companyId)
            ->select([
                'je.id',
                'je.entry_no',
                'je.entry_date',
                'je.reference',
                'je.memo',
                'je.status',
                'je.source_type',
                'je.source_id',
                'je.posted_at',
                'je.posted_by',
                'je.reversed_at',
                'je.reversed_by',
                'je.reversal_of_id',
    
                'pu.name as posted_by_name',
                'pu.email as posted_by_email',
    
                'ru.name as reversed_by_name',
                'ru.email as reversed_by_email',
            ]);
    
        if ($request->filled('status')) {
            $q->where('je.status', $request->status);
        }
    
        if ($request->filled('date_from')) {
            $q->where('je.entry_date', '>=', $request->date_from);
        }
    
        if ($request->filled('date_to')) {
            $q->where('je.entry_date', '<=', $request->date_to);
        }
    
        if ($request->filled('q')) {
            $term = trim((string) $request->q);
    
            $q->where(function ($x) use ($term, $canAudit) {
                $x->where('je.entry_no', 'like', "%{$term}%")
                  ->orWhere('je.reference', 'like', "%{$term}%")
                  ->orWhere('je.memo', 'like', "%{$term}%");
    
                if ($canAudit) {
                    $x->orWhere('pu.name', 'like', "%{$term}%")
                      ->orWhere('pu.email', 'like', "%{$term}%")
                      ->orWhere('ru.name', 'like', "%{$term}%")
                      ->orWhere('ru.email', 'like', "%{$term}%");
                }
            });
        }
    
        $start  = (int) ($request->start ?? 0);
        $length = (int) ($request->length ?? 10);
        $draw   = (int) ($request->draw ?? 1);
    
        $recordsTotal = (clone $q)->count();
    
        $orderColIndex = $request->input('order.0.column');
        $orderDir      = $request->input('order.0.dir', 'desc');
    
        $columns = [
            0 => 'je.id',
            1 => 'je.entry_no',
            2 => 'je.entry_date',
            3 => 'je.reference',
            4 => 'je.status',
        ];
    
        if ($orderColIndex !== null && isset($columns[(int) $orderColIndex])) {
            $q->orderBy($columns[(int) $orderColIndex], $orderDir === 'asc' ? 'asc' : 'desc');
        } else {
            $q->orderBy('je.id', 'desc');
        }
    
        $rows = $q->offset($start)->limit($length)->get();
    
        $data = collect($rows)->map(function ($r) use ($canAudit) {
            $tot = DB::table('finance_journal_entry_lines')
                ->where('journal_entry_id', $r->id)
                ->selectRaw('SUM(debit) as dr, SUM(credit) as cr')
                ->first();
    
            $dr = (float) ($tot->dr ?? 0);
            $cr = (float) ($tot->cr ?? 0);
    
            $postedByLabel = null;
            if ($canAudit && $r->posted_by) {
                $postedByLabel = trim(
                    ($r->posted_by_name ?? 'User#'.$r->posted_by) .
                    ($r->posted_at ? ' on ' . \Carbon\Carbon::parse($r->posted_at)->format('Y-m-d H:i:s') : '')
                );
            }
    
            $reversedByLabel = null;
            if ($canAudit && $r->reversed_by) {
                $reversedByLabel = trim(
                    ($r->reversed_by_name ?? 'User#'.$r->reversed_by) .
                    ($r->reversed_at ? ' on ' . \Carbon\Carbon::parse($r->reversed_at)->format('Y-m-d H:i:s') : '')
                );
            }
    
            $json = [
                'id'            => $r->id,
                'entry_no'      => $r->entry_no,
                'entry_date'    => $r->entry_date,
                'reference'     => $r->reference,
                'memo'          => $r->memo,
                'status'        => $r->status,
                'source_type'   => $r->source_type,
                'source_id'     => $r->source_id,
                'reversal_of_id'=> $r->reversal_of_id,
                'total_debit'   => $dr,
                'total_credit'  => $cr,
            ];
    
            
    
            return [
                'id' => $r->id,
                'entry_no' => e($r->entry_no ?? ('JE-'.$r->id)),
                'entry_date' => e(optional($r->entry_date)->format ? $r->entry_date->format('Y-m-d') : (string) $r->entry_date),
                'reference' => e($r->reference ?? ''),
                'total' => number_format($dr, 2),
                'status' => $this->badge($r->status),
                'audit' => $canAudit ? $this->renderAuditCell($r) : '',
                'actions' => view('finance.journal_entries.partials.actions', ['json' => $json])->render(),
            ];
        })->values();
    
        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => $data,
        ]);
    }

    public function show($id)
    {
        $companyId = auth()->user()->company_id ?? 1;
    
        $entry = DB::table('finance_journal_entries')
            ->where('company_id',$companyId)
            ->where('id',$id)
            ->first();
    
        if(!$entry){
            return response()->json(['error'=>'Entry not found'],404);
        }
    
        $lines = DB::table('finance_journal_entry_lines as l')
            ->join('finance_accounts as a','a.id','=','l.account_id')
            ->where('l.journal_entry_id',$id)
            ->select(
                'a.code',
                'a.name',
                'l.debit',
                'l.credit'
            )
            ->get();
    
        return response()->json([
            'entry'=>$entry,
            'lines'=>$lines
        ]);
    }

    public function lines($id)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $entry = DB::table('finance_journal_entries')
            ->where('company_id', $companyId)
            ->where('id', $id)
            ->first();

        if (!$entry) {
            return response()->json(['message' => 'Entry not found'], 404);
        }

        $lines = DB::table('finance_journal_entry_lines as l')
            ->leftJoin('finance_accounts as a', 'a.id', '=', 'l.account_id')
            ->leftJoin('finance_bank_accounts as b', 'b.id', '=', 'l.bank_account_id')
            ->where('l.journal_entry_id', $id)
            ->select([
                'l.account_id',
                'l.debit',
                'l.credit',
                'l.currency_code',
                'l.fx_rate',
                'l.bank_account_id',
                DB::raw("CONCAT(a.code, ' - ', a.name) as account_label"),
                'b.name as bank_account_label',
            ])
            ->get();

        return response()->json(['lines' => $lines]);
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $data = $this->validateEntry($request);

        return DB::transaction(function() use ($companyId, $data){

            $entryNo = $data['entry_no'] ?: $this->generateEntryNo($companyId, $data['entry_date']);

            $je = FinanceJournalEntry::create([
                'company_id' => $companyId,
                'period_id' => $data['period_id'] ?? null,
                'entry_no' => $entryNo,
                'entry_date' => $data['entry_date'],
                'reference' => $data['reference'] ?? null,
                'memo' => $data['memo'] ?? null,
                'status' => 'draft',
                'source_type' => null,
                'source_id' => null,
            ]);

            $lines = $this->normalizeLines($data['lines'], $je->id);
            FinanceJournalEntryLine::insert($lines);

            return response()->json(['message'=>'Journal entry created.', 'id'=>$je->id]);
        });
    }

    public function update(Request $request, FinanceJournalEntry $entry)
    {
        $companyId = auth()->user()->company_id ?? 1;
        abort_if($entry->company_id != $companyId, 403);

        if ($entry->status !== 'draft') {
            return response()->json(['message'=>'Only draft journal entries can be edited.'], 422);
        }

        $data = $this->validateEntry($request);

        return DB::transaction(function() use ($entry, $data){

            $entryNo = $data['entry_no'] ?: $entry->entry_no;

            $entry->update([
                'period_id' => $data['period_id'] ?? $entry->period_id,
                'entry_no' => $entryNo,
                'entry_date' => $data['entry_date'],
                'reference' => $data['reference'] ?? null,
                'memo' => $data['memo'] ?? null,
            ]);

            $entry->lines()->delete();
            $lines = $this->normalizeLines($data['lines'], $entry->id);
            FinanceJournalEntryLine::insert($lines);

            return response()->json(['message'=>'Journal entry updated.']);
        });
    }

    public function destroy(FinanceJournalEntry $entry)
    {
        $companyId = auth()->user()->company_id ?? 1;
        abort_if($entry->company_id != $companyId, 403);

        if ($entry->status !== 'draft') {
            return response()->json(['message'=>'Only draft entries can be deleted.'], 422);
        }

        return DB::transaction(function() use ($entry){
            $entry->lines()->delete();
            $entry->delete();
            return response()->json(['message'=>'Deleted.']);
        });
    }

    public function post(FinanceJournalEntry $entry)
    {
        $companyId = auth()->user()->company_id ?? 1;
        abort_if($entry->company_id != $companyId, 403);

        if ($entry->status !== 'draft') {
            return response()->json(['message'=>'Only draft entries can be posted.'], 422);
        }

        $tot = $entry->lines()
            ->selectRaw('SUM(debit) as dr, SUM(credit) as cr')
            ->first();

        $dr = (float)($tot->dr ?? 0);
        $cr = (float)($tot->cr ?? 0);

        if ($dr <= 0 || $cr <= 0 || abs($dr - $cr) > 0.005) {
            return response()->json(['message'=>'Entry must be balanced (total debit = total credit) and > 0.'], 422);
        }

        // “Posting must succeed fully”
        return DB::transaction(function() use ($entry){
            $entry->update([
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => auth()->id(),
            ]);
            return response()->json(['message'=>'Posted.']);
        });
    }

    public function reverse(Request $request, FinanceJournalEntry $entry)
    {
        $companyId = auth()->user()->company_id ?? 1;
        abort_if($entry->company_id != $companyId, 403);

        if ($entry->status !== 'posted') {
            return response()->json(['message'=>'Only posted entries can be reversed.'], 422);
        }

        return DB::transaction(function() use ($companyId, $entry){

            $newNo = $this->generateEntryNo($companyId, now()->toDateString(), 'REV');

            $rev = FinanceJournalEntry::create([
                'company_id' => $companyId,
                'period_id' => $entry->period_id,
                'entry_no' => $newNo,
                'entry_date' => now()->toDateString(),
                'reference' => 'Reversal of '.$entry->entry_no,
                'memo' => $entry->memo,
                'status' => 'posted',
                'source_type' => 'reversal',
                'source_id' => $entry->id,
                'posted_at' => now(),
                'posted_by' => auth()->id(),
                'reversal_of_id' => $entry->id,
            ]);

            $lines = $entry->lines()->get()->map(function($l) use ($rev){
                return [
                    'journal_entry_id' => $rev->id,
                    'account_id' => $l->account_id,
                    'description' => $l->description,
                    'debit' => $l->credit,   // swap
                    'credit' => $l->debit,   // swap
                    'memo' => $l->memo,
                    'currency_code' => $l->currency_code,
                    'fx_rate' => $l->fx_rate,
                    'party_type' => $l->party_type,
                    'party_id' => $l->party_id,
                    'bank_account_id' => $l->bank_account_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            FinanceJournalEntryLine::insert($lines);

            $entry->update([
                'status' => 'reversed',
                'reversed_at' => now(),
                'reversed_by' => auth()->id(),
            ]);

            return response()->json(['message'=>'Reversed (reversal JE created).', 'reversal_id'=>$rev->id]);
        });
    }

    public function void(FinanceJournalEntry $entry)
    {
        $companyId = auth()->user()->company_id ?? 1;
        abort_if($entry->company_id != $companyId, 403);

        if (!in_array($entry->status, ['draft','posted'], true)) {
            return response()->json(['message'=>'Only draft/posted entries can be voided.'], 422);
        }

        return DB::transaction(function() use ($entry){
            $entry->update(['status'=>'voided']);
            return response()->json(['message'=>'Voided.']);
        });
    }

    /** Lookups */
    public function glAccounts(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $term = trim((string)$request->get('q',''));

        $q = DB::table('finance_accounts')
            ->where('company_id', $companyId)
            ->where('is_active', 1)
            ->orderBy('code')
            ->select(['id','code','name']);

        if ($term !== '') {
            $q->where(function($x) use ($term){
                $x->where('code','like',"%{$term}%")->orWhere('name','like',"%{$term}%");
            });
        }

        $rows = $q->limit(30)->get();

        return response()->json([
            'results' => $rows->map(fn($r)=>[
                'id'=>$r->id,
                'text'=>$r->code.' - '.$r->name,
            ])->values()
        ]);
    }

    public function bankAccounts(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $term = trim((string)$request->get('q',''));

        $q = DB::table('finance_bank_accounts')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->where('is_active', 1)
            ->orderBy('name')
            ->select(['id','name','type','currency_code']);

        if ($term !== '') {
            $q->where('name','like',"%{$term}%");
        }

        $rows = $q->limit(30)->get();

        return response()->json([
            'results' => $rows->map(fn($r)=>[
                'id'=>$r->id,
                'text'=>$r->name.' ('.$r->type.')'.($r->currency_code ? ' - '.$r->currency_code : ''),
            ])->values()
        ]);
    }

    public function currencies(Request $request)
    {
        $term = trim((string)$request->get('q',''));

        $q = DB::table('currencies')
            ->where('is_active', 1)
            ->orderBy('code')
            ->select(['code','name']);

        if ($term !== '') {
            $q->where(function($x) use ($term){
                $x->where('code','like',"%{$term}%")->orWhere('name','like',"%{$term}%");
            });
        }

        $rows = $q->limit(50)->get();

        return response()->json([
            'results' => $rows->map(fn($r)=>[
                'id'=>$r->code,
                'text'=>$r->code.' - '.$r->name,
            ])->values()
        ]);
    }

    /** Helpers */

    private function validateEntry(Request $request): array
    {
        $rules = [
            'period_id' => ['nullable','integer'],
            'entry_no' => ['nullable','string','max:50'],
            'entry_date' => ['required','date'],
            'reference' => ['nullable','string','max:100'],
            'memo' => ['nullable','string'],
            'lines' => ['required','array','min:2'],

            'lines.*.account_id' => ['required','integer','exists:finance_accounts,id'],
            'lines.*.description' => ['nullable','string','max:255'],
            'lines.*.debit' => ['nullable','numeric','min:0'],
            'lines.*.credit' => ['nullable','numeric','min:0'],
            'lines.*.memo' => ['nullable','string','max:255'],
            'lines.*.currency_code' => ['nullable','string','max:3'],
            'lines.*.fx_rate' => ['nullable','numeric','min:0'],
            'lines.*.party_type' => ['nullable','string','max:50'],
            'lines.*.party_id' => ['nullable','integer'],
            'lines.*.bank_account_id' => ['nullable','integer','exists:finance_bank_accounts,id'],
        ];

        $data = Validator::make($request->all(), $rules)->validate();

        // balance enforcement
        $dr = 0; $cr = 0;
        foreach ($data['lines'] as $ln) {
            $d = (float)($ln['debit'] ?? 0);
            $c = (float)($ln['credit'] ?? 0);
            if ($d > 0 && $c > 0) {
                abort(response()->json(['message'=>'A line cannot have both debit and credit.'], 422));
            }
            $dr += $d; $cr += $c;
        }

        if ($dr <= 0 || $cr <= 0 || abs($dr - $cr) > 0.005) {
            abort(response()->json(['message'=>'Journal entry must balance (total debit = total credit) and be > 0.'], 422));
        }

        return $data;
    }

    private function normalizeLines(array $lines, int $journalEntryId): array
    {
        return collect($lines)->map(function($l) use ($journalEntryId){
            return [
                'journal_entry_id' => $journalEntryId,
                'account_id' => (int)$l['account_id'],
                'description' => $l['description'] ?? null,
                'debit' => (float)($l['debit'] ?? 0),
                'credit' => (float)($l['credit'] ?? 0),
                'memo' => $l['memo'] ?? null,
                'currency_code' => !empty($l['currency_code']) ? strtoupper($l['currency_code']) : null,
                'fx_rate' => !empty($l['fx_rate']) ? (float)$l['fx_rate'] : null,
                'party_type' => $l['party_type'] ?? null,
                'party_id' => !empty($l['party_id']) ? (int)$l['party_id'] : null,
                'bank_account_id' => !empty($l['bank_account_id']) ? (int)$l['bank_account_id'] : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();
    }

    private function badge(?string $status): string
    {
        $status = $status ?: 'draft';
        return match($status){
            'posted' => '<span class="badge bg-success">POSTED</span>',
            'reversed' => '<span class="badge bg-warning text-dark">REVERSED</span>',
            'voided' => '<span class="badge bg-secondary">VOIDED</span>',
            default => '<span class="badge bg-info text-dark">DRAFT</span>',
        };
    }

    /**
     * Auto entry number (replace later with a real numbering settings table).
     * Example: JE-202602-00023 or REV-202602-00004
     */
    private function generateEntryNo(int $companyId, string $date, string $prefix = 'JE'): string
    {
        $ym = date('Ym', strtotime($date));

        // NOTE: simple sequence based on count in month; swap with settings/series table later.
        $count = DB::table('finance_journal_entries')
            ->where('company_id', $companyId)
            ->where('entry_no', 'like', $prefix.'-'.$ym.'-%')
            ->count();

        $seq = str_pad((string)($count + 1), 5, '0', STR_PAD_LEFT);

        return $prefix.'-'.$ym.'-'.$seq;
    }
    
    protected function renderAuditCell($r): string
    {
        $parts = [];
    
        if ($r->posted_by) {
            $postedName = e($r->posted_by_name ?? ('User#' . $r->posted_by));
            $postedAt   = $r->posted_at ? \Carbon\Carbon::parse($r->posted_at)->format('d M Y, H:i') : null;
    
            $parts[] = '
                <div class="mb-1">
                    <span class="badge bg-success me-1">Posted</span>
                    <span class="text-dark">'.$postedName.'</span>
                    '.($postedAt ? '<div class="text-muted small">'.$postedAt.'</div>' : '').'
                </div>
            ';
        }
    
        if ($r->reversed_by) {
            $reversedName = e($r->reversed_by_name ?? ('User#' . $r->reversed_by));
            $reversedAt   = $r->reversed_at ? \Carbon\Carbon::parse($r->reversed_at)->format('d M Y, H:i') : null;
    
            $parts[] = '
                <div class="mb-1">
                    <span class="badge bg-warning text-dark me-1">Reversed</span>
                    <span class="text-dark">'.$reversedName.'</span>
                    '.($reversedAt ? '<div class="text-muted small">'.$reversedAt.'</div>' : '').'
                </div>
            ';
        }
    
        if (empty($parts)) {
            return '<span class="text-muted small">—</span>';
        }
    
        return '<div class="audit-cell">'.implode('', $parts).'</div>';
    }

}