<?php
// File: Modules/Finance/Providers/FinanceAuthServiceProvider.php

namespace Modules\Finance\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class FinanceAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Map these to your permission system (Spatie or your custom).
        // If you use Spatie, you can skip this and rely on ->can('permission-name').
        Gate::define('finance.bank_reconciliation.view', fn($user) => $user->hasPermissionTo('finance.bank_reconciliation.view'));
        Gate::define('finance.bank_reconciliation.create', fn($user) => $user->hasPermissionTo('finance.bank_reconciliation.create'));
        Gate::define('finance.bank_reconciliation.update', fn($user) => $user->hasPermissionTo('finance.bank_reconciliation.update'));
        Gate::define('finance.bank_reconciliation.close', fn($user) => $user->hasPermissionTo('finance.bank_reconciliation.close'));
        Gate::define('finance.bank_reconciliation.undo_close', fn($user) => $user->hasPermissionTo('finance.bank_reconciliation.undo_close'));
        Gate::define('finance.bank_reconciliation.import', fn($user) => $user->hasPermissionTo('finance.bank_reconciliation.import'));
    }
}