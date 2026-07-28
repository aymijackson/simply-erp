<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as LaravelController;
use Illuminate\Database\Eloquent\Model;

class BaseController extends LaravelController
{
    protected function audit(
        string $module,
        string $action,
        ?string $description = null,
        ?Model $subject = null,
        array $meta = []
    ): void {
        $user = auth()->user();
        if (!$user || !method_exists($user, 'logActivity')) return;

        $user->logActivity($module, $action, $description, $subject, $meta);
    }

    /**
     * ERP-grade diff: only logs changed keys
     */
    protected function auditDiff(Model $model, array $incoming, array $onlyKeys = []): array
    {
        $before = [];
        $after  = [];

        $keys = $onlyKeys ?: array_keys($incoming);

        foreach ($keys as $k) {
            $old = data_get($model, $k);
            $new = $incoming[$k] ?? null;

            // normalise empty string vs null
            $oldN = ($old === '') ? null : $old;
            $newN = ($new === '') ? null : $new;

            if ($oldN != $newN) {
                $before[$k] = $old;
                $after[$k]  = $new;
            }
        }

        return [
            'before' => $before ?: null,
            'after'  => $after ?: null,
        ];
    }
}
