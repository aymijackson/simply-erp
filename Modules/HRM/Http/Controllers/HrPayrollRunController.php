<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Modules\HRM\Models\HrPayrollRun;
use Modules\HRM\Models\HrPayslip;
use Modules\HRM\Models\HrPayslipLine;
use Modules\HRM\Models\HrContract;
use Modules\HRM\Models\Employee;
use Yajra\DataTables\Facades\DataTables;

/**
 * HrPayrollRunController
 *
 * ── PERMISSION MAP ───────────────────────────────────────────────────────────
 * index, datatable, show           hrm.payroll_runs.view
 * store                            hrm.payroll_runs.create
 * approve                          hrm.payroll_runs.approve
 * post                             hrm.payroll_runs.post
 * destroy                          hrm.payroll_runs.delete
 *
 * payslipDatatable, payslipShow    hrm.payroll_runs.view
 * payslipUpdate                    hrm.payroll_runs.edit_payslip
 * ────────────────────────────────────────────────────────────────────────────
 */
class HrPayrollRunController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:hrm.payroll_runs.view',        ['only' => ['index','datatable','show','payslipDatatable']]);
        $this->middleware('permission:hrm.payroll_runs.create',      ['only' => ['store']]);
        $this->middleware('permission:hrm.payroll_runs.approve',     ['only' => ['approve']]);
        $this->middleware('permission:hrm.payroll_runs.post',        ['only' => ['post']]);
        $this->middleware('permission:hrm.payroll_runs.delete',      ['only' => ['destroy']]);
        $this->middleware('permission:hrm.payroll_runs.edit_payslip',['only' => ['payslipUpdate']]);
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function index()
    {
        return view('hrm.payroll_runs.index');
    }

    // ── Datatable ─────────────────────────────────────────────────────────────

    public function datatable()
    {
        $q = HrPayrollRun::withCount('payslips')->orderByDesc('period_year')->orderByDesc('period_month');

        return DataTables::eloquent($q)
            ->addColumn('period', fn($r) => $r->period_label)
            ->addColumn('status_badge', fn($r) => match($r->status) {
                'approved' => '<span class="badge bg-primary">Approved</span>',
                'posted'   => '<span class="badge bg-info text-dark">Posted</span>',
                'paid'     => '<span class="badge bg-success">Paid</span>',
                default    => '<span class="badge bg-secondary">Draft</span>',
            })
            ->addColumn('total_net_fmt', fn($r) => number_format($r->total_net, 2))
            ->addColumn('actions', fn($r) =>
                '<a href="/admin/hrm/payroll-runs/'.$r->id.'" class="btn btn-xs btn-outline-primary">
                    <i class="fas fa-eye"></i></a>
                '.($r->status === 'draft' ? '
                 <button class="btn btn-xs btn-success btn-approve-run" data-id="'.$r->id.'">
                    <i class="fas fa-check"></i> Approve</button>' : '').'
                '.($r->status === 'approved' ? '
                 <button class="btn btn-xs btn-info btn-post-run" data-id="'.$r->id.'">
                    <i class="fas fa-upload"></i> Post GL</button>' : '').'
                '.($r->status === 'draft' ? '
                 <button class="btn btn-xs btn-danger btn-delete-run" data-id="'.$r->id.'">
                    <i class="fas fa-trash"></i></button>' : ''))
            ->rawColumns(['status_badge','actions'])
            ->make(true);
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function show(HrPayrollRun $hrPayrollRun)
    {
        $hrPayrollRun->load(['payslips.employee','payslips.lines','approver','poster']);
        return view('hrm.payroll_runs.show', ['run' => $hrPayrollRun]);
    }

    // ── Payslips datatable (within a run) ─────────────────────────────────────

    public function payslipDatatable(HrPayrollRun $hrPayrollRun)
    {
        $q = HrPayslip::with('employee')->where('payroll_run_id', $hrPayrollRun->id);

        return DataTables::eloquent($q)
            ->addColumn('employee_name',   fn($r) => $r->employee?->full_name ?? '-')
            ->addColumn('gross_fmt',       fn($r) => number_format((float)$r->basic_salary + (float)$r->total_allowances, 2))
            ->addColumn('deductions_fmt',  fn($r) => number_format($r->total_deductions, 2))
            ->addColumn('net_fmt',         fn($r) => number_format($r->net_salary, 2))
            ->addColumn('status_badge', fn($r) => match($r->status) {
                'approved' => '<span class="badge bg-primary">Approved</span>',
                'paid'     => '<span class="badge bg-success">Paid</span>',
                default    => '<span class="badge bg-secondary">Draft</span>',
            })
            ->addColumn('actions', fn($r) =>
                '<button class="btn btn-xs btn-warning btn-edit-payslip"
                    data-id="'.$r->id.'" data-record="'.e(json_encode([
                        'id'               => $r->id,
                        'employee_name'    => $r->employee?->full_name,
                        'basic_salary'     => $r->basic_salary,
                        'total_allowances' => $r->total_allowances,
                        'total_deductions' => $r->total_deductions,
                        'net_salary'       => $r->net_salary,
                        'notes'            => $r->notes,
                    ])).'">
                    <i class="fas fa-pencil-alt"></i></button>')
            ->rawColumns(['status_badge','actions'])
            ->make(true);
    }

    // ── Store (create run + generate payslips from active contracts) ───────────

    public function store(Request $request)
    {
        $v = $request->validate([
            'period_month'  => ['required','integer','min:1','max:12'],
            'period_year'   => ['required','integer','min:2000','max:2100'],
            'pay_date'      => ['required','date'],
            'employee_ids'  => ['nullable','array'],
            'employee_ids.*'=> ['integer','exists:employees,id'],
        ]);

        // Prevent duplicate run
        $exists = HrPayrollRun::where('company_id', auth()->user()->company_id ?? 1)
            ->where('period_month', $v['period_month'])
            ->where('period_year', $v['period_year'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'A payroll run already exists for this period.'], 422);
        }

        $run = DB::transaction(function () use ($v) {
            // Generate run number
            $runNo = 'PR-'.str_pad($v['period_year'], 4, '0', STR_PAD_LEFT)
                    .'-'.str_pad($v['period_month'], 2, '0', STR_PAD_LEFT);

            $run = HrPayrollRun::create([
                'company_id'   => auth()->user()->company_id ?? 1,
                'run_no'       => $runNo,
                'period_month' => $v['period_month'],
                'period_year'  => $v['period_year'],
                'pay_date'     => $v['pay_date'],
                'status'       => 'draft',
                'created_by'   => auth()->id(),
                'updated_by'   => auth()->id(),
            ]);

            // Get active contracts
            $contracts = HrContract::with('employee')
                ->where('status', 'active')
                ->when(!empty($v['employee_ids']), fn($q) => $q->whereIn('employee_id', $v['employee_ids']))
                ->get();

            $totalGross = 0;
            $totalDeductions = 0;
            $totalNet = 0;

            foreach ($contracts as $contract) {
                $gross = (float) $contract->basic_salary;
                $net   = $gross; // deductions handled via payslip lines

                $payslip = HrPayslip::create([
                    'payroll_run_id'  => $run->id,
                    'employee_id'     => $contract->employee_id,
                    'contract_id'     => $contract->id,
                    'basic_salary'    => $gross,
                    'total_allowances'=> 0,
                    'total_deductions'=> 0,
                    'net_salary'      => $net,
                    'status'          => 'draft',
                    'created_by'      => auth()->id(),
                    'updated_by'      => auth()->id(),
                ]);

                // Seed a BASIC line
                HrPayslipLine::create([
                    'payslip_id'  => $payslip->id,
                    'type'        => 'allowance',
                    'code'        => 'BASIC',
                    'description' => 'Basic Salary',
                    'amount'      => $gross,
                ]);

                $totalGross      += $gross;
                $totalNet        += $net;
            }

            $run->update([
                'total_gross'      => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_net'        => $totalNet,
            ]);

            return $run;
        });

        return response()->json([
            'message'  => "Payroll run created with {$run->payslips()->count()} payslip(s).",
            'run'      => $run,
            'run_id'   => $run->id,
        ], 201);
    }

    // ── Approve ───────────────────────────────────────────────────────────────

    public function approve(HrPayrollRun $hrPayrollRun)
    {
        if ($hrPayrollRun->status !== 'draft') {
            return response()->json(['message' => 'Only draft runs can be approved.'], 422);
        }

        $hrPayrollRun->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'updated_by'  => auth()->id(),
        ]);

        HrPayslip::where('payroll_run_id', $hrPayrollRun->id)->update(['status' => 'approved']);

        return response()->json(['message' => 'Payroll run approved.']);
    }

    // ── Post to GL ────────────────────────────────────────────────────────────

    public function post(HrPayrollRun $hrPayrollRun)
    {
        if ($hrPayrollRun->status !== 'approved') {
            return response()->json(['message' => 'Only approved runs can be posted to GL.'], 422);
        }

        // GL posting stub — wire to your JournalEntriesController or service
        // The journal entry should debit Salaries Expense and credit Salaries Payable
        // journal_entry_id is stored on the run for reference
        // Example (requires FinanceJournalService):
        //
        // $journalService = app(FinanceJournalService::class);
        // $je = $journalService->createPayrollJournal($hrPayrollRun);
        // $hrPayrollRun->journal_entry_id = $je->id;

        $hrPayrollRun->update([
            'status'     => 'posted',
            'posted_by'  => auth()->id(),
            'posted_at'  => now(),
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Payroll run posted to GL. Wire journal_entry_id via FinanceJournalService.',
        ]);
    }

    // ── Update payslip ────────────────────────────────────────────────────────

    public function payslipUpdate(Request $request, HrPayrollRun $hrPayrollRun, HrPayslip $hrPayslip)
    {
        abort_unless((int) $hrPayslip->payroll_run_id === (int) $hrPayrollRun->id, 403);

        if ($hrPayrollRun->status !== 'draft') {
            return response()->json(['message' => 'Only draft payslips can be edited.'], 422);
        }

        $v = $request->validate([
            'basic_salary'     => ['required','numeric','min:0'],
            'total_allowances' => ['nullable','numeric','min:0'],
            'total_deductions' => ['nullable','numeric','min:0'],
            'notes'            => ['nullable','string'],
        ]);

        $net = (float)$v['basic_salary'] + (float)($v['total_allowances'] ?? 0) - (float)($v['total_deductions'] ?? 0);
        $hrPayslip->update([...$v, 'net_salary' => $net, 'updated_by' => auth()->id()]);

        // Recalculate run totals
        $this->recalculateRunTotals($hrPayrollRun);

        return response()->json(['message' => 'Payslip updated.']);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function destroy(HrPayrollRun $hrPayrollRun)
    {
        if ($hrPayrollRun->status !== 'draft') {
            return response()->json(['message' => 'Only draft runs can be deleted.'], 422);
        }

        DB::transaction(function () use ($hrPayrollRun) {
            HrPayslipLine::whereIn('payslip_id', $hrPayrollRun->payslips()->pluck('id'))->delete();
            $hrPayrollRun->payslips()->delete();
            $hrPayrollRun->delete();
        });

        return response()->json(['message' => 'Payroll run deleted.']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function recalculateRunTotals(HrPayrollRun $run): void
    {
        $totals = HrPayslip::where('payroll_run_id', $run->id)
            ->selectRaw('SUM(basic_salary + total_allowances) as gross, SUM(total_deductions) as deductions, SUM(net_salary) as net')
            ->first();

        $run->update([
            'total_gross'      => $totals->gross      ?? 0,
            'total_deductions' => $totals->deductions  ?? 0,
            'total_net'        => $totals->net         ?? 0,
        ]);
    }
}