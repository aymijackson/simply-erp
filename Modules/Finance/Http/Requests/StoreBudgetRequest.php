<?php
// File: Modules/Finance/Http/Requests/StoreBudgetRequest.php

namespace Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance.budgets.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required','string','max:255'],
            'start_date'  => ['required','date'],
            'end_date'    => ['required','date','after_or_equal:start_date'],
            'period_type' => ['required','in:monthly,quarterly,annual'],
            'currency_code' => ['nullable','string','size:3'],
            'notes'       => ['nullable','string'],
            'account_ids' => ['array'],          // optional on create
            'account_ids.*' => ['integer'],
        ];
    }
}