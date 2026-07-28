<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CrmDashboardController extends BaseController
{
    public function index()
    {
        return view('crm.dashboard.index');
    }

    private function has(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }

    private function dateFilter(Request $r, string $col = 'created_at'): array
    {
        return [
            'from' => $r->filled('date_from') ? $r->date_from : null,
            'to'   => $r->filled('date_to') ? $r->date_to : null,
            'col'  => $col,
        ];
    }

    private function applyDate($q, array $f)
    {
        if ($f['from']) $q->whereDate($f['col'], '>=', $f['from']);
        if ($f['to'])   $q->whereDate($f['col'], '<=', $f['to']);
        return $q;
    }

    public function summary(Request $request)
    {
        $tables = [
            'customers'       => $this->has('customers'),
            'leads'           => $this->has('leads'),
            'opportunities'   => $this->has('opportunities'),
            'activities'      => $this->has('activities'),
            'interactions'    => $this->has('interactions'),
            'notes'           => $this->has('notes'),
            'support_tickets' => $this->has('support_tickets'),
        ];

        $df = $this->dateFilter($request, 'created_at');

        $counts = [
            'customers' => 0,
            'leads' => 0,
            'opportunities' => 0,
            'activities' => 0,
            'interactions' => 0,
            'notes' => 0,
            'tickets' => 0,
        ];

        if ($tables['customers']) {
            $q = DB::table('customers');
            $counts['customers'] = (int) $this->applyDate($q, $df)->count();
        }
        if ($tables['leads']) {
            $q = DB::table('leads');
            $counts['leads'] = (int) $this->applyDate($q, $df)->count();
        }
        if ($tables['opportunities']) {
            $q = DB::table('opportunities');
            $counts['opportunities'] = (int) $this->applyDate($q, $df)->count();
        }
        if ($tables['activities']) {
            $q = DB::table('activities');
            $counts['activities'] = (int) $this->applyDate($q, $df)->count();
        }
        if ($tables['interactions']) {
            $q = DB::table('interactions');
            $counts['interactions'] = (int) $this->applyDate($q, $df)->count();
        }
        if ($tables['notes']) {
            $q = DB::table('notes');
            $counts['notes'] = (int) $this->applyDate($q, $df)->count();
        }
        if ($tables['support_tickets']) {
            $q = DB::table('support_tickets');
            $counts['tickets'] = (int) $this->applyDate($q, $df)->count();
        }

        // Ticket health (if present)
        $ticketByStatus = [];
        if ($tables['support_tickets'] && $this->has('support_tickets')) {
            $tq = DB::table('support_tickets')->select('status', DB::raw('COUNT(*) c'))->groupBy('status');
            $this->applyDate($tq, $df);
            $ticketByStatus = $tq->pluck('c','status')->toArray();
        }

        // Opportunity pipeline (count & value by stage)
        $oppByStage = [];
        if ($tables['opportunities']) {
            $oq = DB::table('opportunities')
                ->select('stage', DB::raw('COUNT(*) c'), DB::raw('COALESCE(SUM(value),0) v'))
                ->groupBy('stage');
            $this->applyDate($oq, $df);
            $oppByStage = $oq->get();
        }

        // Audit
        $this->audit(
            module: 'crm',
            action: 'dashboard.viewed_summary',
            description: 'Viewed CRM executive dashboard summary',
            subject: null,
            meta: ['filters' => $request->all(), 'tables' => $tables]
        );

        return response()->json([
            'tables' => $tables,
            'counts' => $counts,
            'ticket_by_status' => $ticketByStatus,
            'opportunity_by_stage' => $oppByStage,
        ]);
    }

    public function charts(Request $request)
    {
        $tables = [
            'customers'       => $this->has('customers'),
            'leads'           => $this->has('leads'),
            'opportunities'   => $this->has('opportunities'),
            'activities'      => $this->has('activities'),
            'interactions'    => $this->has('interactions'),
            'notes'           => $this->has('notes'),
            'support_tickets' => $this->has('support_tickets'),
        ];

        $df = $this->dateFilter($request, 'created_at');

        // Trend helper
        $trend = function(string $table) use ($df) {
            $q = DB::table($table)
                ->select(DB::raw("DATE(created_at) d"), DB::raw("COUNT(*) c"))
                ->groupBy('d')->orderBy('d');
            $this->applyDate($q, $df);
            return $q->get();
        };

        $series = [
            'customers' => $tables['customers'] ? $trend('customers') : [],
            'leads' => $tables['leads'] ? $trend('leads') : [],
            'tickets' => $tables['support_tickets'] ? $trend('support_tickets') : [],
        ];

        // Top customers by engagement (notes+interactions+activities+opps+tickets)
        $topCustomers = [];
        if ($tables['customers']) {
            $base = DB::table('customers')->select([
                'customers.id','customers.name','customers.email','customers.phone',
                DB::raw('0 as notes_count'),
                DB::raw('0 as interactions_count'),
                DB::raw('0 as activities_count'),
                DB::raw('0 as opportunities_count'),
                DB::raw('0 as tickets_count'),
            ]);

            if ($tables['notes']) {
                $base->leftJoin('notes', function ($j) {
                    $j->on('notes.notable_id', '=', 'customers.id')
                      ->where('notes.notable_type', '=', 'Modules\\CRM\\Models\\Customer');
                });
                $base->addSelect(DB::raw('COUNT(DISTINCT notes.id) as notes_count'));
            }
            if ($tables['interactions']) {
                $base->leftJoin('interactions', function ($j) {
                    $j->on('interactions.interactable_id', '=', 'customers.id')
                      ->where('interactions.interactable_type', '=', 'Modules\\CRM\\Models\\Customer');
                });
                $base->addSelect(DB::raw('COUNT(DISTINCT interactions.id) as interactions_count'));
            }
            if ($tables['activities']) {
                $base->leftJoin('activities', function ($j) {
                    $j->on('activities.related_id', '=', 'customers.id')
                      ->where('activities.related_type', '=', 'Modules\\CRM\\Models\\Customer');
                });
                $base->addSelect(DB::raw('COUNT(DISTINCT activities.id) as activities_count'));
            }
            if ($tables['opportunities']) {
                $base->leftJoin('opportunities', 'opportunities.customer_id', '=', 'customers.id');
                $base->addSelect(DB::raw('COUNT(DISTINCT opportunities.id) as opportunities_count'));
            }
            if ($tables['support_tickets']) {
                $base->leftJoin('support_tickets', 'support_tickets.customer_id', '=', 'customers.id');
                $base->addSelect(DB::raw('COUNT(DISTINCT support_tickets.id) as tickets_count'));
            }

            $base->groupBy('customers.id','customers.name','customers.email','customers.phone');
            $this->applyDate($base, $df);

            $rows = $base->limit(250)->get()->map(function($r){
                $r->notes_count = (int)($r->notes_count ?? 0);
                $r->interactions_count = (int)($r->interactions_count ?? 0);
                $r->activities_count = (int)($r->activities_count ?? 0);
                $r->opportunities_count = (int)($r->opportunities_count ?? 0);
                $r->tickets_count = (int)($r->tickets_count ?? 0);
                $r->score = $r->notes_count + $r->interactions_count + $r->activities_count + $r->opportunities_count + $r->tickets_count;
                return $r;
            })->sortByDesc('score')->take(10)->values();

            $topCustomers = $rows;
        }

        // Audit
        $this->audit(
            module: 'crm',
            action: 'dashboard.viewed_charts',
            description: 'Viewed CRM executive dashboard charts',
            subject: null,
            meta: ['filters' => $request->all(), 'tables' => $tables]
        );

        return response()->json([
            'tables' => $tables,
            'series' => $series,
            'top_customers' => $topCustomers,
        ]);
    }
}
