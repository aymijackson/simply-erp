<?php
// File: Modules/Finance/Http/Requests/SaveBudgetGridRequest.php

namespace Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveBudgetGridRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance.budgets.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'rows' => ['required','array'],
            'rows.*.account_id' => ['required','integer'],
            'rows.*.amounts' => ['required','array'],
            // amounts keys are period_start dates => numeric values
        ];
    }
}