<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinanceLookupsController extends Controller
{
    public function suppliers(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $rows = DB::table('suppliers')
            ->when($q !== '', fn ($x) => $x->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name']);

        return response()->json([
            'results' => $rows->map(fn ($r) => ['id' => $r->id, 'text' => $r->name])->values(),
        ]);
    }

    public function apControlAccounts(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $q = trim((string) $request->get('q', ''));

        $rows = DB::table('finance_accounts as a')
            ->where('a.company_id', $companyId)
            ->when(Schema::hasColumn('finance_accounts', 'deleted_at'), fn ($x) => $x->whereNull('a.deleted_at'))
            ->when($q !== '', function ($x) use ($q) {
                $x->where(function ($w) use ($q) {
                    $w->where('a.code', 'like', "%{$q}%")
                      ->orWhere('a.name', 'like', "%{$q}%");
                });
            })
            ->orderBy('a.code')
            ->limit(30)
            ->get(['a.id', 'a.code', 'a.name']);

        return response()->json([
            'results' => $rows->map(fn ($r) => [
                'id' => $r->id,
                'text' => trim(($r->code ?? '') . ' - ' . ($r->name ?? '')),
            ])->values(),
        ]);
    }

    public function openSupplierBills(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $supplierId = (int) ($request->get('supplier_id') ?? 0);
        $q = trim((string) $request->get('q', ''));

        $rows = DB::table('finance_supplier_bills as b')
            ->where('b.company_id', $companyId)
            ->whereNull('b.deleted_at')
            ->where('b.status', 'posted')
            ->where('b.balance_due', '>', 0)
            ->when($supplierId > 0, fn ($x) => $x->where('b.supplier_id', $supplierId))
            ->when($q !== '', fn ($x) => $x->where('b.bill_no', 'like', "%{$q}%"))
            ->orderByDesc('b.due_date')
            ->limit(30)
            ->get(['b.id', 'b.bill_no', 'b.due_date', 'b.balance_due', 'b.currency_code'])
            ->map(function ($r) {
                $label = trim(
                    ($r->bill_no ?? ('BILL-' . $r->id))
                    . ' | Due ' . $r->due_date
                    . ' | Bal ' . number_format((float) $r->balance_due, 2)
                    . ' ' . $r->currency_code
                );

                return [
                    'id' => $r->id,
                    'text' => $label,
                    'balance_due' => (float) $r->balance_due,
                ];
            });

        return response()->json(['results' => $rows->values()]);
    }
}
