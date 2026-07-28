<?php

namespace App\Livewire\Forms;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    // ✅ NEW: platform selector (erp | admin)
    #[Validate('required|in:erp,admin')]
    public string $platform = 'erp';

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only(['email', 'password']), $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        // ✅ NEW: enforce access after successful login
        $user = Auth::user();

        // You will create these columns (can_access_erp, can_access_admin)
        if ($this->platform === 'admin' && ! $user?->can_access_admin) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey()); // optional: treat as failed attempt

            throw ValidationException::withMessages([
                'form.platform' => __('You do not have access to the Admin Platform.'),
            ]);
        }

        if ($this->platform === 'erp' && ! $user?->can_access_erp) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'form.platform' => __('You do not have access to the ERP platform.'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}
