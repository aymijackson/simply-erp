<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\CRM\Models\Customer;

class CustomersAnalyticsController extends BaseController
{
    public function index()
    {
        return view('crm.customers.analytics');
    }

    private function applyFilters($q, Request $r): void
    {
        // Generic search (name/email/phone)
        if ($r->filled('q')) {
            $term = trim($r->q);
            $q->where(function ($w) use ($term) {
                $w->where('name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%")
                  ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        // Created date range
        if ($r->filled('date_from')) $q->whereDate('created_at', '>=', $r->date_from);
        if ($r->filled('date_to'))   $q->whereDate('created_at', '<=', $r->date_to);

        // If you have status or industry columns later, you can extend here.
        // e.g. if ($r->filled('status')) $q->where('status', $r->status);
    }

    public function kpis(Request $request)
    {
        $q = Customer::query();
        $this->applyFilters($q, $request);

        $total = (clone $q)->count();

        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $createdToday = (clone $q)->whereDate('created_at', $today)->count();
        $createdThisMonth = (clone $q)->whereDate('created_at', '>=', $monthStart)->count();

        $withEmail = (clone $q)->whereNotNull('email')->where('email', '!=', '')->count();
        $withPhone = (clone $q)->whereNotNull('phone')->where('phone', '!=', '')->count();

        // “Completeness score” (simple)
        // email + phone + address (if exists)
        $hasAddressCol = DB::getSchemaBuilder()->hasColumn('customers', 'address');
        $withAddress = $hasAddressCol
            ? (clone $q)->whereNotNull('address')->where('address', '!=', '')->count()
            : 0;

        $this->audit(
            module: 'crm',
            action: 'customers.analytics_viewed_kpis',
            description: 'Viewed customers KPIs',
            subject: null,
            meta: ['filters' => $request->all()]
        );

        return response()->json([
            'total' => (int) $total,
            'created_today' => (int) $createdToday,
            'created_this_month' => (int) $createdThisMonth,
            'with_email' => (int) $withEmail,
            'with_phone' => (int) $withPhone,
            'with_address' => (int) $withAddress,
        ]);
    }

    public function charts(Request $request)
    {
        $base = Customer::query();
        $this->applyFilters($base, $request);

        // 1) Trend: customers created per day
        $trend = (clone $base)
            ->select(DB::raw("DATE(created_at) as d"), DB::raw("COUNT(*) as c"))
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        // 2) “Top customers” by activity (if tables exist)
        // Uses: notes, interactions, activities, opportunities, support_tickets
        $tables = [
            'notes' => DB::getSchemaBuilder()->hasTable('notes'),
            'interactions' => DB::getSchemaBuilder()->hasTable('interactions'),
            'activities' => DB::getSchemaBuilder()->hasTable('activities'),
            'opportunities' => DB::getSchemaBuilder()->hasTable('opportunities'),
            'support_tickets' => DB::getSchemaBuilder()->hasTable('support_tickets'),
        ];

        // Aggregate counts using left joins only if tables exist
        $top = (clone $base)->select([
            'customers.id',
            'customers.name',
            'customers.email',
            'customers.phone',
            DB::raw('0 as notes_count'),
            DB::raw('0 as interactions_count'),
            DB::raw('0 as activities_count'),
            DB::raw('0 as opportunities_count'),
            DB::raw('0 as tickets_count'),
            DB::raw('0 as engagement_score'),
        ]);

        if ($tables['notes']) {
            $top->leftJoin('notes', function ($j) {
                $j->on('notes.notable_id', '=', 'customers.id')
                  ->where('notes.notable_type', '=', 'Modules\\CRM\\Models\\Customer');
            });
            $top->addSelect(DB::raw('COUNT(DISTINCT notes.id) as notes_count'));
        }

        if ($tables['interactions']) {
            $top->leftJoin('interactions', function ($j) {
                $j->on('interactions.interactable_id', '=', 'customers.id')
                  ->where('interactions.interactable_type', '=', 'Modules\\CRM\\Models\\Customer');
            });
            $top->addSelect(DB::raw('COUNT(DISTINCT interactions.id) as interactions_count'));
        }

        if ($tables['activities']) {
            $top->leftJoin('activities', function ($j) {
                $j->on('activities.related_id', '=', 'customers.id')
                  ->where('activities.related_type', '=', 'Modules\\CRM\\Models\\Customer');
            });
            $top->addSelect(DB::raw('COUNT(DISTINCT activities.id) as activities_count'));
        }

        if ($tables['opportunities']) {
            $top->leftJoin('opportunities', 'opportunities.customer_id', '=', 'customers.id');
            $top->addSelect(DB::raw('COUNT(DISTINCT opportunities.id) as opportunities_count'));
        }

        if ($tables['support_tickets']) {
            $top->leftJoin('support_tickets', 'support_tickets.customer_id', '=', 'customers.id');
            $top->addSelect(DB::raw('COUNT(DISTINCT support_tickets.id) as tickets_count'));
        }

        // Grouping for aggregates
        $top->groupBy('customers.id', 'customers.name', 'customers.email', 'customers.phone');

        // Engagement score = sum of counts (computed in PHP)
        $topRows = $top->orderByDesc('customers.id')->limit(200)->get()->map(function($r){
            $r->notes_count = (int) ($r->notes_count ?? 0);
            $r->interactions_count = (int) ($r->interactions_count ?? 0);
            $r->activities_count = (int) ($r->activities_count ?? 0);
            $r->opportunities_count = (int) ($r->opportunities_count ?? 0);
            $r->tickets_count = (int) ($r->tickets_count ?? 0);

            $r->engagement_score =
                $r->notes_count +
                $r->interactions_count +
                $r->activities_count +
                $r->opportunities_count +
                $r->tickets_count;

            return $r;
        })->sortByDesc('engagement_score')->take(10)->values();

        // 3) Data quality distribution (email/phone)
        $quality = [
            'complete' => (clone $base)->whereNotNull('email')->where('email','!=','')
                                       ->whereNotNull('phone')->where('phone','!=','')->count(),
            'email_only' => (clone $base)->whereNotNull('email')->where('email','!=','')
                                         ->where(function($w){ $w->whereNull('phone')->orWhere('phone','=',''); })->count(),
            'phone_only' => (clone $base)->whereNotNull('phone')->where('phone','!=','')
                                         ->where(function($w){ $w->whereNull('email')->orWhere('email','=',''); })->count(),
            'missing_both' => (clone $base)->where(function($w){
                $w->whereNull('email')->orWhere('email','=','');
            })->where(function($w){
                $w->whereNull('phone')->orWhere('phone','=','');
            })->count(),
        ];

        $this->audit(
            module: 'crm',
            action: 'customers.analytics_viewed_charts',
            description: 'Viewed customers charts',
            subject: null,
            meta: ['filters' => $request->all()]
        );

        return response()->json([
            'trend' => $trend,
            'top_customers' => $topRows,
            'quality' => $quality,
            'tables_used' => $tables,
        ]);
    }
}
