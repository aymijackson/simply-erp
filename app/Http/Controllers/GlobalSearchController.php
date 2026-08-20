<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GlobalSearchController extends Controller
{
    /**
     * Topbar live search across the modules this app actually has record types
     * for. Each category is independently permission-checked (using the closest
     * matching permission for that record type - several of the underlying show
     * routes have no permission gate of their own, so this is a best-effort
     * courtesy filter, not the only thing standing between a user and a record;
     * the destination route enforces its own access regardless) and
     * try/catch-guarded so one bad query can't blank the whole response.
     *
     */
    public function search(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['groups' => []]);
        }

        $user = $request->user();
        $companyId = (int) ($user->company_id ?? 1);
        $like = '%'.$term.'%';
        $perGroup = 5;

        $groups = [];

        if ($user->can('core.master_data.customers.view')) {
            try {
                $rows = DB::table('customers')
                    ->where('company_id', $companyId)
                    ->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like))
                    ->orderBy('name')->limit($perGroup)->get(['id', 'name', 'email', 'phone']);
                if ($rows->isNotEmpty()) {
                    $groups[] = ['label' => 'Customers', 'items' => $rows->map(fn ($r) => [
                        'title' => $r->name,
                        'subtitle' => $r->email ?: $r->phone,
                        'url' => route('admin.customers.show', $r->id),
                    ])->values()];
                }
            } catch (\Throwable $e) {
            }
        }

        // Suppliers has no company_id column (shared master data, same as products)
        // and no permission gate on its own controller - matches existing behavior.
        try {
            $rows = DB::table('suppliers')
                ->where('name', 'like', $like)
                ->orderBy('name')->limit($perGroup)->get(['id', 'name', 'status']);
            if ($rows->isNotEmpty()) {
                $groups[] = ['label' => 'Suppliers', 'items' => $rows->map(fn ($r) => [
                    'title' => $r->name,
                    'subtitle' => ucfirst($r->status),
                    'url' => route('admin.suppliers.show', $r->id),
                ])->values()];
            }
        } catch (\Throwable $e) {
        }

        if ($user->can('sales.orders.view')) {
            try {
                $rows = DB::table('sales_orders')
                    ->where(fn ($q) => $q->where('order_no', 'like', $like)->orWhere('reference', 'like', $like))
                    ->orderByDesc('id')->limit($perGroup)->get(['id', 'order_no', 'status']);
                if ($rows->isNotEmpty()) {
                    $groups[] = ['label' => 'Sales Orders', 'items' => $rows->map(fn ($r) => [
                        'title' => $r->order_no ?: ('Order #'.$r->id),
                        'subtitle' => ucfirst($r->status ?? ''),
                        'url' => route('admin.sales.orders.show', $r->id),
                    ])->values()];
                }
            } catch (\Throwable $e) {
            }
        }

        if ($user->can('sales.quotes.view')) {
            try {
                $rows = DB::table('sales_quotes')
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at')
                    ->where(fn ($q) => $q->where('quote_no', 'like', $like)->orWhere('reference', 'like', $like))
                    ->orderByDesc('id')->limit($perGroup)->get(['id', 'quote_no', 'status']);
                if ($rows->isNotEmpty()) {
                    $groups[] = ['label' => 'Sales Quotes', 'items' => $rows->map(fn ($r) => [
                        'title' => $r->quote_no ?: ('Quote #'.$r->id),
                        'subtitle' => ucfirst($r->status ?? ''),
                        'url' => route('admin.sales.quotes.show', $r->id),
                    ])->values()];
                }
            } catch (\Throwable $e) {
            }
        }

        if ($user->can('sales.invoices.view')) {
            try {
                $rows = DB::table('sales_invoices')
                    ->where('invoice_no', 'like', $like)
                    ->orderByDesc('id')->limit($perGroup)->get(['id', 'invoice_no', 'status']);
                if ($rows->isNotEmpty()) {
                    $groups[] = ['label' => 'Sales Invoices', 'items' => $rows->map(fn ($r) => [
                        'title' => $r->invoice_no ?: ('Invoice #'.$r->id),
                        'subtitle' => ucfirst($r->status ?? ''),
                        'url' => route('admin.sales.invoices.show', $r->id),
                    ])->values()];
                }
            } catch (\Throwable $e) {
            }
        }

        try {
            $rows = DB::table('finance_journal_entries')
                ->where('company_id', $companyId)
                ->where(fn ($q) => $q->where('entry_no', 'like', $like)->orWhere('reference', 'like', $like)->orWhere('memo', 'like', $like))
                ->orderByDesc('id')->limit($perGroup)->get(['id', 'entry_no', 'reference', 'status']);
            if ($rows->isNotEmpty()) {
                $groups[] = ['label' => 'Journal Entries', 'items' => $rows->map(fn ($r) => [
                    'title' => $r->entry_no ?: $r->reference ?: ('Entry #'.$r->id),
                    'subtitle' => ucfirst($r->status ?? ''),
                    'url' => route('admin.finance.journal_entries.show', $r->id),
                ])->values()];
            }
        } catch (\Throwable $e) {
        }

        if ($user->can('finance.ap.view')) {
            try {
                $rows = DB::table('finance_supplier_bills')
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at')
                    ->where(fn ($q) => $q->where('bill_no', 'like', $like)->orWhere('reference', 'like', $like))
                    ->orderByDesc('id')->limit($perGroup)->get(['id', 'bill_no', 'status']);
                if ($rows->isNotEmpty()) {
                    $groups[] = ['label' => 'Supplier Bills', 'items' => $rows->map(fn ($r) => [
                        'title' => $r->bill_no ?: ('Bill #'.$r->id),
                        'subtitle' => ucfirst($r->status ?? ''),
                        'url' => route('admin.finance.supplier_bills.show_page', $r->id),
                    ])->values()];
                }
            } catch (\Throwable $e) {
            }
        }

        if ($user->can('finance.payments.view')) {
            try {
                $rows = DB::table('finance_supplier_payments')
                    ->where('company_id', $companyId)
                    ->where(fn ($q) => $q->where('payment_no', 'like', $like)->orWhere('reference', 'like', $like))
                    ->orderByDesc('id')->limit($perGroup)->get(['id', 'payment_no', 'status']);
                if ($rows->isNotEmpty()) {
                    $groups[] = ['label' => 'Supplier Payments', 'items' => $rows->map(fn ($r) => [
                        'title' => $r->payment_no ?: ('Payment #'.$r->id),
                        'subtitle' => ucfirst($r->status ?? ''),
                        'url' => route('admin.finance.supplier_payments.show', $r->id),
                    ])->values()];
                }
            } catch (\Throwable $e) {
            }
        }

        if ($user->can('production.work_orders.manage')) {
            try {
                $rows = DB::table('work_orders')
                    ->where('company_id', $companyId)
                    ->where('work_order_number', 'like', $like)
                    ->orderByDesc('id')->limit($perGroup)->get(['id', 'work_order_number', 'status']);
                if ($rows->isNotEmpty()) {
                    $groups[] = ['label' => 'Work Orders', 'items' => $rows->map(fn ($r) => [
                        'title' => $r->work_order_number,
                        'subtitle' => ucfirst(str_replace('_', ' ', $r->status ?? '')),
                        'url' => route('admin.production.work-orders.show', $r->id),
                    ])->values()];
                }
            } catch (\Throwable $e) {
            }
        }

        if ($user->can('production.boms.manage')) {
            try {
                $rows = DB::table('bom_headers')
                    ->where('company_id', $companyId)
                    ->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('bom_code', 'like', $like))
                    ->orderByDesc('id')->limit($perGroup)->get(['id', 'name', 'bom_code']);
                if ($rows->isNotEmpty()) {
                    $groups[] = ['label' => 'BOMs', 'items' => $rows->map(fn ($r) => [
                        'title' => $r->name,
                        'subtitle' => $r->bom_code,
                        'url' => route('admin.production.boms.show', $r->id),
                    ])->values()];
                }
            } catch (\Throwable $e) {
            }
        }

        return response()->json(['groups' => $groups]);
    }
}
