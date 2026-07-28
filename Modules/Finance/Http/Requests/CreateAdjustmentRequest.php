<?php
// File: Modules/Finance/Http/Requests/CreateAdjustmentRequest.php

namespace Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance.bank_reconciliation.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['required','in:bank_fee,interest,suspense'],
            'entry_date' => ['required','date'],
            'offset_account_id' => ['required','integer'],
            'memo' => ['nullable','string'],
            'amount' => ['required','numeric','min:0.01'],
        ];
    }
}