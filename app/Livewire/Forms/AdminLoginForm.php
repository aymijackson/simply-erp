<?php

namespace App\Livewire\Forms;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

class AdminLoginForm extends Form
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        if (! Auth::guard('admin')->attempt(
            ['email' => $this->email, 'password' => $this->password],
            $this->remember
        )) {
            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);
        }
    }
}
