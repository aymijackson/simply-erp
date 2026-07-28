<?php
// File: Modules/Finance/Http/Requests/MatchStatementLineRequest.php

namespace Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MatchStatementLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance.bank_reconciliation.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'journal_entry_line_id' => ['required','integer'],
        ];
    }
}