<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ControlCenterController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id ?? 1;

        // Pre‑aggregate allocations (critical for performance)
        $allocations = DB::table('sales_payment_allocations')
            ->selectRaw('sales_invoice_id, SUM(amount_applied) as allocated')
            ->groupBy('sales_invoice_id');
        
        // Compute receivables using allocation-based accounting
        $receivables = DB::table('sales_invoices as si')
            ->leftJoinSub($allocations, 'a', 'a.sales_invoice_id', '=', 'si.id')
            ->whereIn('si.status', ['posted', 'part_paid'])
            ->selectRaw('SUM(si.grand_total - COALESCE(a.allocated, 0)) as receivables')
            ->value('receivables');
        
        // Build your finance array
        $finance = [
            'cash' => DB::table('finance_bank_accounts')->sum('opening_balance'),
        
            'receivables' => $receivables,
        
            'payables' => DB::table('finance_supplier_bills')
                ->whereIn('status', ['posted', 'part_paid'])
                ->sum('balance_due'),
        ];

        $sales = [
            'leads' => DB::table('leads')->count(),
            'opportunities' => DB::table('opportunities')
                ->whereNotIn('stage',['won','lost'])
                ->count()
        ];

        $projects = [
            'active' => DB::table('projects')
                ->whereIn('status',['active','in_progress'])
                ->count(),

            'late_milestones' => DB::table('project_milestones')
                ->whereDate('target_date','<',now())
                ->whereNotIn('status',['completed'])
                ->count()
        ];

        $operations = [
            'open_work_orders' => DB::table('work_orders')
                ->whereIn('status',['open','released','in_progress'])
                ->count(),

            'low_stock' => DB::table('v_stock_levels')
                ->join('product_variants', 'product_variants.id', '=', 'product_variant_id')
                ->whereColumn('qty_on_hand','<=','reorder_point')
                ->count()
        ];

        $support = [
            'open_tickets' => DB::table('support_tickets')
                ->whereNotIn('status',['closed','resolved'])
                ->count()
        ];

        $alerts = [
            'overdue_invoices' =>
                DB::table('sales_invoices')
                ->whereDate('due_date','<',now())
                ->whereIn('status',['posted','part_paid'])
                ->count(),

            'overdue_bills' =>
                DB::table('finance_supplier_bills')
                ->whereDate('due_date','<',now())
                ->whereIn('status',['posted','part_paid'])
                ->count(),
        ];

        return view('dashboard.control_center',compact(
            'finance',
            'sales',
            'projects',
            'operations',
            'support',
            'alerts'
        ));
    }
}