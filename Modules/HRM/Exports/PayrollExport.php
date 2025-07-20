<?php

namespace Modules\HRM\Exports;

use Modules\HRM\Models\Payroll;
use Maatwebsite\Excel\Facades\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeading;


class PayrollExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Payroll::with('employee')->get()->map(function ($payroll) {
            return [
                'ID' => $payroll->id,
                'Employee' => $payroll->employee->first_name . ' ' . $payroll->employee->last_name,
                'Month' => $payroll->pay_date,
                'Basic Salary' => $payroll->basic_salary,
                'Allowances' => $payroll->total_allowances,
                'Deductions' => $payroll->total_deductions,
                'Net Salary' => $payroll->net_salary,
                'Status' => $payroll->payment_status,
            ];
        });
    }

    public function headings(): array
    {
        return ['ID', 'Employee', 'Month', 'Basic Salary', 'Allowances', 'Deductions', 'Net Salary', 'Status'];
    }
}
