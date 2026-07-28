<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HRM\Models\Payroll;
use Modules\HRM\Models\Employee;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;
use Modules\HRM\Exports\PayrollExport;
use Maatwebsite\Excel\Facades\Excel; 
use Barryvdh\DomPDF\Facade\Pdf;

class PayrollController extends Controller
{
    public function index()
    {
        $employees = Employee::all();
        return view('hrm.payrolls.index', compact('employees'));
    }

    public function generateMonthly(Request $request)
    {
        $data = $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $payDate = $data['month'] . '-01';
        $created = 0;
        $skipped = 0;

        $employees = Employee::where('is_active', true)->get();

        foreach ($employees as $employee) {
            $exists = Payroll::where('employee_id', $employee->id)
                ->where('pay_date', $payDate)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $basicSalary = $employee->activeContract?->basic_salary ?? 0;

            Payroll::create([
                'employee_id' => $employee->id,
                'pay_date' => $payDate,
                'basic_salary' => $basicSalary,
                'total_allowances' => 0,
                'total_deductions' => 0,
                'net_salary' => $basicSalary,
                'status' => 'pending',
                'is_paid' => false,
            ]);

            $created++;
        }

        return response()->json([
            'message' => "Generated {$created} payroll record(s) for {$data['month']}." . ($skipped ? " Skipped {$skipped} (already exist)." : ''),
            'created' => $created,
            'skipped' => $skipped,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'month' => 'required|date_format:Y-m',
            'basic_salary' => 'required|numeric|min:0',
            'allowances' => 'array',
            'allowances.*.type' => 'required|string',
            'allowances.*.amount' => 'required|numeric|min:0',
            'deductions' => 'array',
            'deductions.*.type' => 'required|string',
            'deductions.*.amount' => 'required|numeric|min:0',
        ]);

        $totalAllowances = collect($data['allowances'])->sum('amount');
        $totalDeductions = collect($data['deductions'])->sum('amount');
        $netSalary = $data['basic_salary'] + $totalAllowances - $totalDeductions;

        $payroll = Payroll::create([
            'employee_id' => $data['employee_id'],
            'pay_date' => $data['month'] . '-01',
            'basic_salary' => $data['basic_salary'],
            'total_allowances' => $totalAllowances,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
            'status' => 'pending',
            'is_paid' => false,
        ]);

        foreach ($data['allowances'] as $item) {
            $payroll->allowances()->create($item);
        }

        foreach ($data['deductions'] as $item) {
            $payroll->deductions()->create($item);
        }

        return response()->json(['message' => 'Payroll created successfully.']);
    }

    public function update(Request $request, Payroll $payroll)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'month' => 'required|date_format:Y-m',
            'basic_salary' => 'required|numeric|min:0',
            'allowances' => 'array',
            'allowances.*.type' => 'required|string',
            'allowances.*.amount' => 'required|numeric|min:0',
            'deductions' => 'array',
            'deductions.*.type' => 'required|string',
            'deductions.*.amount' => 'required|numeric|min:0',
        ]);

        $totalAllowances = collect($data['allowances'])->sum('amount');
        $totalDeductions = collect($data['deductions'])->sum('amount');
        $netSalary = $data['basic_salary'] + $totalAllowances - $totalDeductions;

        $payroll->update([
            'employee_id' => $data['employee_id'],
            'pay_date' => $data['month'] . '-01',
            'basic_salary' => $data['basic_salary'],
            'total_allowances' => $totalAllowances,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary
        ]);

        $payroll->allowances()->delete();
        $payroll->deductions()->delete();

        foreach ($data['allowances'] as $item) {
            $payroll->allowances()->create($item);
        }

        foreach ($data['deductions'] as $item) {
            $payroll->deductions()->create($item);
        }

        return response()->json(['message' => 'Payroll updated successfully.']);
    }

    public function destroy(Payroll $payroll)
    {
        $payroll->delete();
        return response()->json(['message' => 'Payroll deleted.']);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        Payroll::whereIn('id', $request->ids)->delete();
        return response()->json(['message' => 'Selected payrolls deleted.']);
    }

    public function togglePaidStatus(Payroll $payroll)
    {
        if (!$payroll) {
            return response()->json(['message' => 'Payroll record not found.'], 404);
        }
        $payroll->is_paid = $payroll->is_paid ? false : true;
        $payroll->status = $payroll->is_paid === true ? 'pending' : 'paid';
        $done = $payroll->save();

        $message = $payroll->is_paid === true ? 'Payroll status marked as un paid successfully.' : 'Payroll status marked as paid successfully.';

        if($done)
        {
            return response()->json([
                'message' => $message,
                'new_status' => $payroll->payment_status
            ]);
        }
        else
        {
            return response()->json(['message' => 'Failed to update payroll status.'], 500);
        }
    }


    public function datatable()
    {
        $query = Payroll::with(['employee', 'allowances', 'deductions'])->latest();

        return DataTables::of($query)
            ->addColumn('employee', function ($row) {
                return $row->employee->full_name ?? 'N/A';
            })
            ->addColumn('pay_date', function ($row) {
                return \Carbon\Carbon::parse($row->pay_date)->format('F Y');
            })
            ->addColumn('total_allowances', function ($row) {
                return number_format($row->allowances->sum('amount'), 2);
            })
            ->addColumn('total_deductions', function ($row) {
                return number_format($row->deductions->sum('amount'), 2);
            })
            ->addColumn('net_salary', function ($row) {
                $net = $row->basic_salary + $row->allowances->sum('amount') - $row->deductions->sum('amount');
                return number_format($net, 2);
            })
            ->addColumn('payment_status', function ($row) {
                return $row->is_paid ? 'paid' : 'unpaid';
            })
            ->addColumn('actions', function ($row) {
                $record = $row->toArray();
                $record['allowances'] = $row->allowances;
                $record['deductions'] = $row->deductions;
                $recordJson = htmlspecialchars(json_encode($record));

                return '
                    <button class="btn btn-sm btn-warning edit-payroll" data-record="' . $recordJson . '">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-payroll" data-id="' . $row->id . '">
                        <i class="fas fa-trash"></i>
                    </button>
                    <button class="btn btn-sm ' . ($row->is_paid ? 'btn-secondary' : 'btn-success') . ' toggle-paid" data-id="' . $row->id . '">
                        ' . ($row->is_paid ? 'Mark as Unpaid' : 'Mark as Paid') . '
                    </button>
                    <a href="' . route('admin.hrm.payroll.slip', $row->id) . '" class="btn btn-sm btn-info" target="_blank">
                        <i class="fas fa-file-pdf"></i> Payslip';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function export($type)
    {
        $filename = 'payroll_' . now()->format('Y_m_d_His');
        if ($type === 'excel') {
            return Excel::download(new PayrollExport, "$filename.xlsx");
        }

        if ($type === 'pdf') {
            $data = Payroll::with('employee')->get();
            $pdf = PDF::loadView('hrm::payrolls.pdf', compact('data'));
            return $pdf->download("$filename.pdf");
        }

        return back()->with('error', 'Invalid export type.');
    }


    public function slip($id)
    {
        $payroll = Payroll::with(['employee', 'allowances', 'deductions'])->findOrFail($id);

        $pdf = Pdf::loadView('hrm.payrolls.slip', compact('payroll'));

        return $pdf->stream('Payslip_' . $payroll->employee->first_name . '_' . $payroll->pay_date . '.pdf');
    }

}
