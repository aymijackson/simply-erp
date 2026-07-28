<?php
// File: Modules/Finance/Http/Requests/StoreBankReconciliationRequest.php

namespace Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance.bank_reconciliation.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'bank_account_id' => ['required','integer'],
            'period_start' => ['required','date'],
            'period_end' => ['required','date','after_or_equal:period_start'],
            'statement_opening_balance' => ['required','numeric'],
            'statement_closing_balance' => ['required','numeric'],
            'notes' => ['nullable','string'],
        ];
    }
}