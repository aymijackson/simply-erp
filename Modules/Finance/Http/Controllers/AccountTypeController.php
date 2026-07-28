<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\FinanceAccountType as AccountType;

class AccountTypeController extends Controller
{
    public function index()
    {
        return view('finance.accounts.types.index');
    }

    public function datatable()
    {
        $rows = AccountType::orderBy('name')->get();

        return response()->json([
            'data' => $rows
        ]);
    }

    public function show($id)
    {
        $row = AccountType::findOrFail($id);

        return response()->json([
            'ok' => true,
            'data' => $row
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:finance_account_types,code',
            'name' => 'required|string|max:150',
            'category' => 'required|in:asset,liability,equity,income,expense,cogs',
            'normal_balance' => 'required|in:debit,credit',
        ]);

        AccountType::create($data);

        return response()->json([
            'ok' => true,
            'message' => 'Account type created successfully.'
        ]);
    }

    public function update(Request $request, $id)
    {
        $row = AccountType::findOrFail($id);

        $data = $request->validate([
            'code' => 'required|string|max:50|unique:finance_account_types,code,' . $row->id,
            'name' => 'required|string|max:150',
            'category' => 'required|in:asset,liability,equity,income,expense,cogs',
            'normal_balance' => 'required|in:debit,credit',
        ]);

        $row->update($data);

        return response()->json([
            'ok' => true,
            'message' => 'Account type updated successfully.'
        ]);
    }

    public function destroy($id)
    {
        $row = AccountType::findOrFail($id);
        $row->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Account type deleted successfully.'
        ]);
    }
}