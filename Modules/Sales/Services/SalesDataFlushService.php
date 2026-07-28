<?php

namespace Modules\Sales\Services;

use App\Models\DataFlushLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesDataFlushService
{
    // ✅ Update these table names once if your schema differs
    private array $tables = [
        'orders'       => ['sales_orders', 'sales_order_lines', 'order_date', 'status', 'customer_id'],
        'deliveries'   => ['sales_deliveries', 'sales_delivery_lines', 'delivery_date', 'status', 'customer_id'],
        'invoices'     => ['sales_invoices', 'sales_invoice_lines', 'invoice_date', 'status', 'customer_id'],
        'payments'     => ['sales_payments', null, 'payment_date', 'status', 'customer_id'],
        'credit_notes' => ['sales_credit_notes', 'sales_credit_note_lines', 'credit_note_date', 'status', 'customer_id'],
        'allocations'  => ['sales_payment_allocations', null, null, null, null],
    ];

    public function preview(array $data): array
    {
        [$from, $to] = [$data['from'] ?? null, $data['to'] ?? null];
        $includePosted = (bool)($data['include_posted'] ?? false);
        $scope = $data['scope'];
        $mods = $data['modules'] ?? [];
        $customerId = $data['customer_id'] ?? null;

        $counts = [];

        foreach ($mods as $m) {
            if ($m === 'allocations') {
                $counts['allocations'] = (int)DB::table('sales_payment_allocations')->count();
                continue;
            }

            [$tbl, $_lines, $dateCol, $statusCol, $custCol] = $this->tables[$m];
            $q = DB::table($tbl);
            $this->applyScope($q, $scope, $includePosted, $from, $to, $customerId, $dateCol, $statusCol, $custCol);
            $counts[$m] = (int)$q->count();
        }

        return [
            'scope' => $scope,
            'include_posted' => $includePosted,
            'from' => $from,
            'to' => $to,
            'customer_id' => $customerId,
            'counts' => $counts,
            'total' => array_sum($counts),
        ];
    }

    public function run(array $data, Request $request): array
    {
        $preview = $this->preview($data);
        $deletedTotal = 0;

        [$from, $to] = [$data['from'] ?? null, $data['to'] ?? null];
        $includePosted = (bool)($data['include_posted'] ?? false);
        $scope = $data['scope'];
        $mods = $data['modules'] ?? [];
        $customerId = $data['customer_id'] ?? null;

        DB::transaction(function () use (
            $mods, $scope, $includePosted, $from, $to, $customerId, &$deletedTotal
        ) {
            /**
             * ✅ Best practice delete order:
             * allocations -> credit note lines -> credit notes
             * -> payment allocations -> payments
             * -> invoice lines -> invoices
             * -> delivery lines -> deliveries
             * -> order lines -> orders
             */

            // allocations (always safe to delete first)
            if (in_array('allocations', $mods, true)) {
                $deletedTotal += DB::table('sales_payment_allocations')->delete();
            }

            if (in_array('credit_notes', $mods, true)) {
                $ids = $this->idsForScope('sales_credit_notes', $scope, $includePosted, $from, $to, $customerId, 'credit_note_date', 'status', 'customer_id');
                if ($ids) {
                    $deletedTotal += DB::table('sales_credit_note_lines')->whereIn('sales_credit_note_id', $ids)->delete();
                    $deletedTotal += DB::table('sales_credit_notes')->whereIn('id', $ids)->delete();
                }
            }

            if (in_array('payments', $mods, true)) {
                $ids = $this->idsForScope('sales_payments', $scope, $includePosted, $from, $to, $customerId, 'payment_date', 'status', 'customer_id');
                if ($ids) {
                    $deletedTotal += DB::table('sales_payment_allocations')->whereIn('sales_payment_id', $ids)->delete();
                    $deletedTotal += DB::table('sales_payments')->whereIn('id', $ids)->delete();
                }
            }

            if (in_array('invoices', $mods, true)) {
                $ids = $this->idsForScope('sales_invoices', $scope, $includePosted, $from, $to, $customerId, 'invoice_date', 'status', 'customer_id');
                if ($ids) {
                    $deletedTotal += DB::table('sales_invoice_lines')->whereIn('sales_invoice_id', $ids)->delete();
                    $deletedTotal += DB::table('sales_invoices')->whereIn('id', $ids)->delete();
                }
            }

            if (in_array('deliveries', $mods, true)) {
                $ids = $this->idsForScope('sales_deliveries', $scope, $includePosted, $from, $to, $customerId, 'delivery_date', 'status', 'customer_id');
                if ($ids) {
                    $deletedTotal += DB::table('sales_delivery_lines')->whereIn('sales_delivery_id', $ids)->delete();
                    $deletedTotal += DB::table('sales_deliveries')->whereIn('id', $ids)->delete();
                }
            }

            if (in_array('orders', $mods, true)) {
                $ids = $this->idsForScope('sales_orders', $scope, $includePosted, $from, $to, $customerId, 'order_date', 'status', 'customer_id');
                if ($ids) {
                    $deletedTotal += DB::table('sales_order_lines')->whereIn('sales_order_id', $ids)->delete();
                    $deletedTotal += DB::table('sales_orders')->whereIn('id', $ids)->delete();
                }
            }
        });

        DataFlushLog::create([
            'module' => 'sales',
            'scope' => $data['scope'],
            'payload' => $preview,
            'performed_by' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => substr((string)$request->userAgent(), 0, 1000),
            'deleted_count' => $deletedTotal,
        ]);

        return [
            'preview' => $preview,
            'deleted_total' => $deletedTotal,
        ];
    }

    private function applyScope($q, string $scope, bool $includePosted, $from, $to, $customerId, ?string $dateCol, ?string $statusCol, ?string $custCol): void
    {
        if ($scope === 'draft_only' && $statusCol) {
            $q->where($statusCol, 'draft');
            return;
        }

        if ($scope === 'customer' && $customerId && $custCol) {
            $q->where($custCol, $customerId);
            if (!$includePosted && $statusCol) $q->where($statusCol, 'draft');
            return;
        }

        if ($scope === 'date_range' && $dateCol) {
            if ($from && $to) $q->whereBetween($dateCol, [$from, $to]);
            if (!$includePosted && $statusCol) $q->where($statusCol, 'draft');
            return;
        }

        if ($scope === 'full_reset') {
            if (!$includePosted && $statusCol) $q->where($statusCol, 'draft');
        }
    }

    private function idsForScope(string $table, string $scope, bool $includePosted, $from, $to, $customerId, string $dateCol, string $statusCol, string $custCol): array
    {
        $q = DB::table($table)->select('id');
        $this->applyScope($q, $scope, $includePosted, $from, $to, $customerId, $dateCol, $statusCol, $custCol);
        return $q->pluck('id')->all();
    }
}
