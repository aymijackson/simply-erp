<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'StockIssue' => \Modules\Inventory\Models\StockIssue::class,
            'StockEntry' => \Modules\Inventory\Models\StockEntry::class,
        ]);
    }
}
