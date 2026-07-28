<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Modules\CRM\Models\Customer;

class CustomerSegmentationController extends BaseController
{
    public function index()
    {
        // thresholds (you can move this to settings/parameters table later)
        $cfg = [
            'hot_pipeline_min'           => 50000,
            'at_risk_open_tickets_min'   => 2,
            'dormant_days_min'           => 60,
            'warm_interactions_30d_min'  => 2,
            'new_days_max'               => 14,
        ];

        return view('crm.analytics.customer-segmentation.index', compact('cfg'));
    }

    private function baseQuery(Request $request)
    {
        $cfg = $this->getCfg($request);

        // IMPORTANT: match your polymorphic class string exactly
        $customerClass = \Modules\CRM\Models\Customer::class;

        $interactionsSub = DB::table('interactions')
            ->selectRaw("
                interactable_id as customer_id,
                MAX(interaction_date) as last_interaction_at,
                SUM(CASE WHEN interaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as interactions_30d,
                SUM(CASE WHEN interaction_date >= DATE_SUB(NOW(), INTERVAL 90 DAY) THEN 1 ELSE 0 END) as interactions_90d
            ")
            ->where('interactable_type', $customerClass)
            ->groupBy('interactable_id');

        $ticketsSub = DB::table('support_tickets')
            ->selectRaw("
                customer_id,
                SUM(CASE WHEN status IN ('open','pending') THEN 1 ELSE 0 END) as open_tickets,
                MAX(created_at) as last_ticket_at
            ")
            ->groupBy('customer_id');

        $oppSub = DB::table('opportunities')
            ->selectRaw("
                customer_id,
                COALESCE(SUM(value),0) as pipeline_value,
                MAX(created_at) as last_opportunity_at
            ")
            ->groupBy('customer_id');

        $notesSub = DB::table('notes')
            ->selectRaw("
                notable_id as customer_id,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY) THEN 1 ELSE 0 END) as notes_90d
            ")
            ->where('notable_type', $customerClass)
            ->groupBy('notable_id');

        $q = DB::table('customers as c')
            ->leftJoinSub($interactionsSub, 'i', fn($j) => $j->on('i.customer_id', '=', 'c.id'))
            ->leftJoinSub($ticketsSub, 't', fn($j) => $j->on('t.customer_id', '=', 'c.id'))
            ->leftJoinSub($oppSub, 'o', fn($j) => $j->on('o.customer_id', '=', 'c.id'))
            ->leftJoinSub($notesSub, 'n', fn($j) => $j->on('n.customer_id', '=', 'c.id'))
            ->selectRaw("
                c.id,
                c.name as customer_name,
                c.email,
                c.phone,

                COALESCE(o.pipeline_value,0) as pipeline_value,
                COALESCE(t.open_tickets,0) as open_tickets,
                COALESCE(i.interactions_30d,0) as interactions_30d,
                COALESCE(i.interactions_90d,0) as interactions_90d,

                i.last_interaction_at,
                t.last_ticket_at,
                o.last_opportunity_at,

                CASE
                    WHEN COALESCE(o.pipeline_value,0) >= ? THEN 'Hot / High Value'
                    WHEN COALESCE(t.open_tickets,0) >= ? THEN 'At Risk'
                    WHEN i.last_interaction_at IS NOT NULL
                         AND DATEDIFF(NOW(), i.last_interaction_at) <= ?
                         AND COALESCE(i.interactions_30d,0) >= ? THEN 'Warm'
                    WHEN c.created_at IS NOT NULL
                         AND DATEDIFF(NOW(), c.created_at) <= ? THEN 'New'
                    WHEN i.last_interaction_at IS NULL
                         OR DATEDIFF(NOW(), i.last_interaction_at) >= ? THEN 'Dormant'
                    ELSE 'Normal'
                END as segment,

                CASE
                    WHEN i.last_interaction_at IS NULL THEN NULL
                    ELSE DATEDIFF(NOW(), i.last_interaction_at)
                END as days_since_interaction
            ", [
                $cfg['hot_pipeline_min'],
                $cfg['at_risk_open_tickets_min'],
                $cfg['dormant_days_min'],
                $cfg['warm_interactions_30d_min'],
                $cfg['new_days_max'],
                $cfg['dormant_days_min'],
            ]);

        // Filters (from your UI)
        if ($request->filled('segment')) {
            // segment is computed, so use HAVING
            $q->having('segment', '=', $request->segment);
        }
        if ($request->filled('min_pipeline')) {
            $q->having('pipeline_value', '>=', (float) $request->min_pipeline);
        }
        if ($request->filled('min_open_tickets')) {
            $q->having('open_tickets', '>=', (int) $request->min_open_tickets);
        }
        if ($request->filled('min_interactions_30d')) {
            $q->having('interactions_30d', '>=', (int) $request->min_interactions_30d);
        }

        return [$q, $cfg];
    }

    private function getCfg(Request $request): array
    {
        // allow overrides from request (flexible/choosable like you wanted)
        return [
            'hot_pipeline_min'           => (float)($request->input('hot_pipeline_min', 50000)),
            'at_risk_open_tickets_min'   => (int)($request->input('at_risk_open_tickets_min', 2)),
            'dormant_days_min'           => (int)($request->input('dormant_days_min', 60)),
            'warm_interactions_30d_min'  => (int)($request->input('warm_interactions_30d_min', 2)),
            'new_days_max'               => (int)($request->input('new_days_max', 14)),
        ];
    }

    public function summary(Request $request)
    {
        [$q, $cfg] = $this->baseQuery($request);

        // Wrap it so we can aggregate counts by computed segment
        $rows = DB::query()
            ->fromSub($q, 'x')
            ->selectRaw("
                segment,
                COUNT(*) as cnt,
                SUM(pipeline_value) as pipeline_sum,
                SUM(open_tickets) as open_tickets_sum
            ")
            ->groupBy('segment')
            ->get();

        $total = $rows->sum('cnt');

        $hot = (int)($rows->firstWhere('segment', 'Hot / High Value')->cnt ?? 0);
        $risk = (int)($rows->firstWhere('segment', 'At Risk')->cnt ?? 0);
        $dormant = (int)($rows->firstWhere('segment', 'Dormant')->cnt ?? 0);

        return response()->json([
            'total_customers' => $total,
            'hot' => $hot,
            'at_risk' => $risk,
            'dormant' => $dormant,
            'segments' => $rows,
            'cfg' => $cfg,
        ]);
    }

    public function datatable(Request $request)
    {
        [$q, $cfg] = $this->baseQuery($request);

        // Audit (after you increased audit_logs.url to TEXT)
        $this->audit(
            module: 'crm',
            action: 'analytics.customer_segmentation.filtered',
            description: 'Filtered Customer Segmentation',
            subject: null,
            meta: [
                'filters' => $request->only(['segment','min_pipeline','min_open_tickets','min_interactions_30d']),
                'cfg' => $cfg,
            ]
        );

        return DataTables::of($q)
            ->addColumn('actions', function ($row) {
                $view = route('admin.customers.index', $row->id);
                return '<a class="btn btn-sm btn-secondary" href="'.$view.'">View</a>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }
}
