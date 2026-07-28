<?php
// File: Modules/Finance/Http/Requests/ImportStatementCsvRequest.php

namespace Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportStatementCsvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance.bank_reconciliation.import') ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => ['required','file','mimes:csv,txt','max:5120'],
            // expected headers: date, description, amount, reference(optional), fit_id(optional)
        ];
    }
}