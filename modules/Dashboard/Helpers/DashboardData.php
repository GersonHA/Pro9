<?php

namespace Modules\Dashboard\Helpers;

use App\Models\Tenant\Document;
use App\Models\Tenant\Configuration;
use App\Models\Tenant\DocumentPayment;
use App\Models\Tenant\SaleNote;
use App\Models\Tenant\SaleNotePayment;
use Carbon\Carbon;
use App\Models\Tenant\Person;
use App\Models\Tenant\Item;
use App\Models\Tenant\Purchase;
use App\Models\Tenant\Establishment;
use Modules\Inventory\Models\ItemWarehouse;
use Modules\Expense\Models\Expense;
use Modules\Dashboard\Traits\TotalsTrait;
use Illuminate\Support\Facades\DB;


class DashboardData
{

    use TotalsTrait;

    public function data($request)
    {
        $establishment_id = $request['establishment_id'];
        $period = $request['period'];
        $date_start = $request['date_start'];
        $date_end = $request['date_end'];
        $month_start = $request['month_start'];
        $month_end = $request['month_end'];

        $d_start = null;
        $d_end = null;

        switch ($period) {
            case 'month':
                $d_start = Carbon::parse($month_start.'-01')->format('Y-m-d');
                $d_end = Carbon::parse($month_start.'-01')->endOfMonth()->format('Y-m-d');
                break;
            case 'between_months':
                $d_start = Carbon::parse($month_start.'-01')->format('Y-m-d');
                $d_end = Carbon::parse($month_end.'-01')->endOfMonth()->format('Y-m-d');
                break;
            case 'date':
                $d_start = $date_start;
                $d_end = $date_start;
                break;
            case 'between_dates':
                $d_start = $date_start;
                $d_end = $date_end;
                break;
            case 'last_week':
                $d_start = $date_start;
                $d_end = $date_end;
                break;
        }

        // $customers = Person::whereType('customers')->orderBy('name')->take(100)->get()->transform(function($row) {
        //     return [
        //         'id' => $row->id,
        //         'description' => $row->number.' - '.$row->name,
        //         'name' => $row->name,
        //         'number' => $row->number,
        //         'identity_document_type_id' => $row->identity_document_type_id,
        //     ];
        // });

        return [
            'document' => $this->document_totals($establishment_id, $d_start, $d_end),
            'sale_note' => $this->sale_note_totals($establishment_id, $d_start, $d_end),
            'general' => $this->totals($establishment_id, $d_start, $d_end, $period, $month_start, $month_end),
            'balance' => $this->balance($establishment_id, $d_start, $d_end),
            'items' => $this->getItems(),
            // 'quantity' => Configuration::first()->quantity_documents,
        ];
    }

    private function resolveFilters(array $request = [])
    {
        $period = $request['period'] ?? 'last_week';
        $date_start = $request['date_start'] ?? Carbon::now()->subDays(7)->format('Y-m-d');
        $date_end = $request['date_end'] ?? Carbon::now()->format('Y-m-d');
        $month_start = $request['month_start'] ?? Carbon::now()->format('Y-m');
        $month_end = $request['month_end'] ?? Carbon::now()->format('Y-m');

        $d_start = null;
        $d_end = null;

        switch ($period) {
            case 'month':
                $d_start = Carbon::parse($month_start.'-01')->format('Y-m-d');
                $d_end = Carbon::parse($month_start.'-01')->endOfMonth()->format('Y-m-d');
                break;
            case 'between_months':
                $d_start = Carbon::parse($month_start.'-01')->format('Y-m-d');
                $d_end = Carbon::parse($month_end.'-01')->endOfMonth()->format('Y-m-d');
                break;
            case 'date':
                $d_start = $date_start;
                $d_end = $date_start;
                break;
            case 'between_dates':
            case 'last_week':
                $d_start = $date_start;
                $d_end = $date_end;
                break;
            case 'all':
                break;
            default:
                $d_start = $date_start;
                $d_end = $date_end;
                break;
        }

        return [
            'establishment_id' => $request['establishment_id'] ?? null,
            'period' => $period,
            'date_start' => $d_start,
            'date_end' => $d_end,
            'month_start' => $month_start,
            'month_end' => $month_end,
        ];
    }

    private function applyEstablishment($query, $establishment_id, $table = null)
    {
        if ($establishment_id) {
            $query->where(($table ? "{$table}." : '').'establishment_id', $establishment_id);
        }

        return $query;
    }

    private function applyDateRange($query, $date_start, $date_end, $column = 'date_of_issue')
    {
        if ($date_start && $date_end) {
            $query->whereBetween($column, [$date_start, $date_end]);
        }

        return $query;
    }

    public function globalData(array $request = [])
    {
        $filters = $this->resolveFilters($request);
        $monthly_kpis = $this->monthlyKpis(6, $filters);

        return array_merge([
            'total_cpe' => Configuration::first()->quantity_documents,
            'document_total_global' => $this->document_totals_globals($filters['date_start'], $filters['date_end']),
            'sale_note_total_global' => $this->sale_note_totals_global($filters['date_start'], $filters['date_end']),
        ], $monthly_kpis);
    }

    private function monthlyKpis($months = 6, array $filters = [])
    {
        $months_es = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Set', 'Oct', 'Nov', 'Dic'];
        $establishment_id = $filters['establishment_id'] ?? null;

        $labels = [];
        $trend = [
            'monthly_sales' => [],
            'average_ticket' => [],
            'accounts_receivable' => [],
            'net_utility' => [],
        ];

        // Caso 1: rango específico (no 'month') — comparar período actual vs anterior
        if (!empty($filters['date_start']) && !empty($filters['date_end']) && (($filters['period'] ?? null) !== 'month')) {
            $previous = $this->previousRange($filters['date_start'], $filters['date_end']);

            // 1 sola llamada agregada para ambos rangos (en vez de 2 × 14 queries)
            $ranges = [
                'previous' => [$previous['start'], $previous['end']],
                'current'  => [$filters['date_start'], $filters['date_end']],
            ];
            $aggregated = $this->kpisForRangesAggregated($ranges, $establishment_id);

            $current = $aggregated['current'];
            $previous_data = $aggregated['previous'];

            $trend_labels = ['Anterior', 'Actual'];
            foreach ($trend as $key => $serie) {
                $trend[$key][] = $previous_data[$key];
                $trend[$key][] = $current[$key];
            }

            return array_merge($current, ['trend' => array_merge(['labels' => $trend_labels], $trend)]);
        }

        // Caso 2: vista mensual - agregar TODOS los $months meses en 1 sola pasada por tabla
        $base = !empty($filters['date_start']) ? Carbon::parse($filters['date_start'])->startOfMonth() : Carbon::now()->startOfMonth();
        $start_global = $base->copy()->subMonths($months - 1)->startOfMonth();
        $end_global = $base->copy()->endOfMonth();

        $monthly = $this->kpisForRangeAggregatedByMonth($start_global, $end_global, $establishment_id);

        $current = $monthly[$base->format('Y-m')] ?? $this->emptyKpis();

        for ($i = $months - 1; $i >= 0; $i--) {
            $ref = $base->copy()->subMonths($i);
            $month_key = $ref->format('Y-m');
            $m = $monthly[$month_key] ?? $this->emptyKpis();

            $labels[] = $months_es[(int) $ref->format('n')];
            foreach ($trend as $key => $serie) {
                $trend[$key][] = $m[$key];
            }
        }

        return array_merge($current, ['trend' => array_merge(['labels' => $labels], $trend)]);
    }

    private function emptyKpis()
    {
        return [
            'monthly_sales' => 0.0,
            'average_ticket' => 0.0,
            'accounts_receivable' => 0.0,
            'net_utility' => 0.0,
            'income' => 0.0,
            'egress' => 0.0,
        ];
    }

    /**
     * Agrega KPIs de múltiples rangos no superpuestos en una sola pasada por tabla.
     * Devuelve ['previous' => kpis, 'current' => kpis].
     */
    private function kpisForRangesAggregated(array $ranges, $establishment_id = null)
    {
        $all_start = min(array_map(fn($r) => $r[0], $ranges));
        $all_end = max(array_map(fn($r) => $r[1], $ranges));
        $tenant = DB::connection('tenant');

        $docs_by_day = $tenant->table('documents')
            ->selectRaw("date_of_issue, document_type_id, currency_type_id,
                         CASE WHEN currency_type_id = 'PEN' THEN total ELSE total * exchange_rate_sale END as total_pen,
                         CASE WHEN document_type_id = '07' THEN (CASE WHEN currency_type_id = 'PEN' THEN total ELSE total * exchange_rate_sale END) ELSE 0 END as credit_pen")
            ->whereBetween('date_of_issue', [$all_start, $all_end])
            ->whereIn('state_type_id', ['01', '03', '05', '07', '13'])
            ->when($establishment_id, fn($q) => $q->where('establishment_id', $establishment_id))
            ->get();

        // 1 query agregada para pagos con JOIN
        $payments = $tenant->table('document_payments')
            ->join('documents', 'documents.id', '=', 'document_payments.document_id')
            ->whereBetween('documents.date_of_issue', [$all_start, $all_end])
            ->where('documents.establishment_id', $establishment_id)
            ->whereIn('documents.state_type_id', ['01', '03', '05', '07', '13'])
            ->whereIn('documents.document_type_id', ['01', '03', '08'])
            ->selectRaw("documents.date_of_issue, documents.currency_type_id, documents.exchange_rate_sale, document_payments.payment")
            ->get();

        $sn_by_day = $tenant->table('sale_notes')
            ->selectRaw("date_of_issue, currency_type_id, exchange_rate_sale,
                         CASE WHEN currency_type_id = 'PEN' THEN total ELSE total * exchange_rate_sale END as total_pen")
            ->whereBetween('date_of_issue', [$all_start, $all_end])
            ->where('changed', false)
            ->whereIn('state_type_id', ['01', '03', '05', '07', '13'])
            ->when($establishment_id, fn($q) => $q->where('establishment_id', $establishment_id))
            ->get();

        $sn_payments = $tenant->table('sale_note_payments')
            ->join('sale_notes', 'sale_notes.id', '=', 'sale_note_payments.sale_note_id')
            ->whereBetween('sale_notes.date_of_issue', [$all_start, $all_end])
            ->where('sale_notes.establishment_id', $establishment_id)
            ->where('sale_notes.changed', false)
            ->whereIn('sale_notes.state_type_id', ['01', '03', '05', '07', '13'])
            ->selectRaw("sale_notes.date_of_issue, sale_notes.currency_type_id, sale_notes.exchange_rate_sale, sale_note_payments.payment")
            ->get();

        $purchases = $tenant->table('purchases')
            ->selectRaw("date_of_issue, currency_type_id, exchange_rate_sale, total, total_perception")
            ->whereBetween('date_of_issue', [$all_start, $all_end])
            ->whereIn('state_type_id', ['01', '03', '05', '07', '13'])
            ->when($establishment_id, fn($q) => $q->where('establishment_id', $establishment_id))
            ->get();

        $expenses = $tenant->table('expenses')
            ->selectRaw("date_of_issue, currency_type_id, exchange_rate_sale, total")
            ->whereBetween('date_of_issue', [$all_start, $all_end])
            ->where('state_type_id', '05')
            ->when($establishment_id, fn($q) => $q->where('establishment_id', $establishment_id))
            ->get();

        $result = [];
        foreach ($ranges as $key => [$start, $end]) {
            $d_docs = $docs_by_day->whereBetween('date_of_issue', [$start, $end]);
            $d_payments = $payments->whereBetween('date_of_issue', [$start, $end]);
            $d_sn = $sn_by_day->whereBetween('date_of_issue', [$start, $end]);
            $d_sn_pay = $sn_payments->whereBetween('date_of_issue', [$start, $end]);
            $d_pur = $purchases->whereBetween('date_of_issue', [$start, $end]);
            $d_exp = $expenses->whereBetween('date_of_issue', [$start, $end]);

            $sales_total = (float) $d_docs->whereIn('document_type_id', ['01','03','08'])->sum('total_pen');
            $credit = (float) $d_docs->where('document_type_id', '07')->sum('credit_pen');
            $documents_total = $sales_total - $credit;
            $documents_count = $d_docs->whereIn('document_type_id', ['01','03','08'])->count();

            $doc_pay_pen = 0;
            foreach ($d_payments as $p) {
                $factor = $p->currency_type_id == 'PEN' ? 1 : $p->exchange_rate_sale;
                $doc_pay_pen += $p->payment * $factor;
            }

            $sn_total = (float) $d_sn->sum('total_pen');
            $sn_count = $d_sn->count();

            $sn_pay_pen = 0;
            foreach ($d_sn_pay as $p) {
                $factor = $p->currency_type_id == 'PEN' ? 1 : $p->exchange_rate_sale;
                $sn_pay_pen += $p->payment * $factor;
            }

            $purchase_total = 0;
            foreach ($d_pur as $pur) {
                $factor = $pur->currency_type_id == 'PEN' ? 1 : $pur->exchange_rate_sale;
                $purchase_total += ($pur->total + $pur->total_perception) * $factor;
            }

            $expense_total = 0;
            foreach ($d_exp as $exp) {
                $factor = $exp->currency_type_id == 'PEN' ? 1 : $exp->exchange_rate_sale;
                $expense_total += $exp->total * $factor;
            }

            $monthly_sales = $documents_total + $sn_total;
            $sales_count = $documents_count + $sn_count;
            $avg_ticket = $sales_count > 0 ? ($monthly_sales / $sales_count) : 0;
            $ar = max($monthly_sales - ($doc_pay_pen + $sn_pay_pen), 0);
            $nu = $monthly_sales - $purchase_total - $expense_total;

            $result[$key] = [
                'monthly_sales' => round($monthly_sales, 2),
                'average_ticket' => round($avg_ticket, 2),
                'accounts_receivable' => round($ar, 2),
                'net_utility' => round($nu, 2),
                'income' => round($monthly_sales, 2),
                'egress' => round($purchase_total + $expense_total, 2),
            ];
        }

        return $result;
    }

    /**
     * Agrega KPIs por mes en una sola pasada por tabla.
     * Devuelve array indexado por 'YYYY-MM'.
     */
    private function kpisForRangeAggregatedByMonth(Carbon $start, Carbon $end, $establishment_id = null)
    {
        $tenant = DB::connection('tenant');

        $docs = $tenant->table('documents')
            ->selectRaw("date_of_issue, document_type_id, currency_type_id,
                         CASE WHEN currency_type_id = 'PEN' THEN total ELSE total * exchange_rate_sale END as total_pen,
                         CASE WHEN document_type_id = '07' THEN (CASE WHEN currency_type_id = 'PEN' THEN total ELSE total * exchange_rate_sale END) ELSE 0 END as credit_pen")
            ->whereBetween('date_of_issue', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->whereIn('state_type_id', ['01', '03', '05', '07', '13'])
            ->when($establishment_id, fn($q) => $q->where('establishment_id', $establishment_id))
            ->get();

        $payments = $tenant->table('document_payments')
            ->join('documents', 'documents.id', '=', 'document_payments.document_id')
            ->whereBetween('documents.date_of_issue', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->where('documents.establishment_id', $establishment_id)
            ->whereIn('documents.state_type_id', ['01', '03', '05', '07', '13'])
            ->whereIn('documents.document_type_id', ['01', '03', '08'])
            ->selectRaw("documents.date_of_issue, documents.currency_type_id, documents.exchange_rate_sale, document_payments.payment")
            ->get();

        $sn = $tenant->table('sale_notes')
            ->selectRaw("date_of_issue, currency_type_id, exchange_rate_sale,
                         CASE WHEN currency_type_id = 'PEN' THEN total ELSE total * exchange_rate_sale END as total_pen")
            ->whereBetween('date_of_issue', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->where('changed', false)
            ->whereIn('state_type_id', ['01', '03', '05', '07', '13'])
            ->when($establishment_id, fn($q) => $q->where('establishment_id', $establishment_id))
            ->get();

        $sn_pay = $tenant->table('sale_note_payments')
            ->join('sale_notes', 'sale_notes.id', '=', 'sale_note_payments.sale_note_id')
            ->whereBetween('sale_notes.date_of_issue', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->where('sale_notes.establishment_id', $establishment_id)
            ->where('sale_notes.changed', false)
            ->whereIn('sale_notes.state_type_id', ['01', '03', '05', '07', '13'])
            ->selectRaw("sale_notes.date_of_issue, sale_notes.currency_type_id, sale_notes.exchange_rate_sale, sale_note_payments.payment")
            ->get();

        $purchases = $tenant->table('purchases')
            ->selectRaw("date_of_issue, currency_type_id, exchange_rate_sale, total, total_perception")
            ->whereBetween('date_of_issue', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->whereIn('state_type_id', ['01', '03', '05', '07', '13'])
            ->when($establishment_id, fn($q) => $q->where('establishment_id', $establishment_id))
            ->get();

        $expenses = $tenant->table('expenses')
            ->selectRaw("date_of_issue, currency_type_id, exchange_rate_sale, total")
            ->whereBetween('date_of_issue', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->where('state_type_id', '05')
            ->when($establishment_id, fn($q) => $q->where('establishment_id', $establishment_id))
            ->get();

        // Agrupar por mes 'YYYY-MM'
        $by_month = [];
        foreach ($docs as $d) {
            $m = substr($d->date_of_issue, 0, 7);
            if (!isset($by_month[$m])) {
                $by_month[$m] = ['sales_pen'=>0,'credit_pen'=>0,'docs_count'=>0];
            }
            if (in_array($d->document_type_id, ['01','03','08'])) {
                $by_month[$m]['sales_pen'] += (float) $d->total_pen;
                $by_month[$m]['docs_count']++;
            } elseif ($d->document_type_id == '07') {
                $by_month[$m]['credit_pen'] += (float) $d->credit_pen;
            }
        }

        $pay_by_month = [];
        foreach ($payments as $p) {
            $m = substr($p->date_of_issue, 0, 7);
            $factor = $p->currency_type_id == 'PEN' ? 1 : $p->exchange_rate_sale;
            $pay_by_month[$m] = ($pay_by_month[$m] ?? 0) + $p->payment * $factor;
        }

        $sn_by_month = [];
        foreach ($sn as $s) {
            $m = substr($s->date_of_issue, 0, 7);
            if (!isset($sn_by_month[$m])) {
                $sn_by_month[$m] = ['total_pen'=>0,'count'=>0];
            }
            $sn_by_month[$m]['total_pen'] += (float) $s->total_pen;
            $sn_by_month[$m]['count']++;
        }

        $sn_pay_by_month = [];
        foreach ($sn_pay as $p) {
            $m = substr($p->date_of_issue, 0, 7);
            $factor = $p->currency_type_id == 'PEN' ? 1 : $p->exchange_rate_sale;
            $sn_pay_by_month[$m] = ($sn_pay_by_month[$m] ?? 0) + $p->payment * $factor;
        }

        $pur_by_month = [];
        foreach ($purchases as $p) {
            $m = substr($p->date_of_issue, 0, 7);
            $factor = $p->currency_type_id == 'PEN' ? 1 : $p->exchange_rate_sale;
            $pur_by_month[$m] = ($pur_by_month[$m] ?? 0) + ($p->total + $p->total_perception) * $factor;
        }

        $exp_by_month = [];
        foreach ($expenses as $e) {
            $m = substr($e->date_of_issue, 0, 7);
            $factor = $e->currency_type_id == 'PEN' ? 1 : $e->exchange_rate_sale;
            $exp_by_month[$m] = ($exp_by_month[$m] ?? 0) + $e->total * $factor;
        }

        // Construir resultado por mes
        $result = [];
        $cursor = $start->copy();
        while ($cursor <= $end) {
            $m = $cursor->format('Y-m');
            $docs_total = ($by_month[$m]['sales_pen'] ?? 0) - ($by_month[$m]['credit_pen'] ?? 0);
            $sn_total = $sn_by_month[$m]['total_pen'] ?? 0;
            $monthly_sales = $docs_total + $sn_total;
            $sales_count = ($by_month[$m]['docs_count'] ?? 0) + ($sn_by_month[$m]['count'] ?? 0);
            $avg = $sales_count > 0 ? ($monthly_sales / $sales_count) : 0;
            $ar = max($monthly_sales - (($pay_by_month[$m] ?? 0) + ($sn_pay_by_month[$m] ?? 0)), 0);
            $nu = $monthly_sales - ($pur_by_month[$m] ?? 0) - ($exp_by_month[$m] ?? 0);

            $result[$m] = [
                'monthly_sales' => round($monthly_sales, 2),
                'average_ticket' => round($avg, 2),
                'accounts_receivable' => round($ar, 2),
                'net_utility' => round($nu, 2),
                'income' => round($monthly_sales, 2),
                'egress' => round(($pur_by_month[$m] ?? 0) + ($exp_by_month[$m] ?? 0), 2),
            ];

            // Avanzar al primer día del siguiente mes
            $cursor = $cursor->copy()->addMonth()->startOfMonth();
            // Evitar bucle infinito si addMonth no avanza
            if ($cursor->format('Y-m') === $m) {
                $cursor->addDay();
            }
        }

        return $result;
    }

    /**
     * KPIs de un rango (todas las sucursales), normalizado a PEN.
     * net_utility es aproximado: ventas - compras - gastos (no usa costo por producto).
     */
    private function previousRange($date_start, $date_end)
    {
        $start = Carbon::parse($date_start);
        $end = Carbon::parse($date_end);
        $days = $start->diffInDays($end) + 1;
        $previous_end = $start->copy()->subDay();

        return [
            'start' => $previous_end->copy()->subDays($days - 1)->format('Y-m-d'),
            'end' => $previous_end->format('Y-m-d'),
        ];
    }

    private function previousComparisonRange(array $filters)
    {
        if (($filters['period'] ?? null) === 'month') {
            $previous = Carbon::parse($filters['date_start'])->startOfMonth()->subMonth();

            return [
                'start' => $previous->copy()->startOfMonth()->format('Y-m-d'),
                'end' => $previous->copy()->endOfMonth()->format('Y-m-d'),
            ];
        }

        if (($filters['period'] ?? null) === 'between_months') {
            $start = Carbon::parse($filters['date_start'])->startOfMonth();
            $end = Carbon::parse($filters['date_end'])->startOfMonth();
            $months = $start->diffInMonths($end) + 1;
            $previous_start = $start->copy()->subMonths($months)->startOfMonth();
            $previous_end = $start->copy()->subMonth()->endOfMonth();

            return [
                'start' => $previous_start->format('Y-m-d'),
                'end' => $previous_end->format('Y-m-d'),
            ];
        }

        return $this->previousRange($filters['date_start'], $filters['date_end']);
    }

    private function kpisForRange($date_start, $date_end, $establishment_id = null)
    {
        $tenant = DB::connection('tenant');

        // ── DOCUMENTOS: 4 queries agregadas en lugar de N+1 ─────────────────────
        $docBase = function () use ($tenant, $establishment_id, $date_start, $date_end) {
            return $tenant->table('documents')
                ->whereBetween('date_of_issue', [$date_start, $date_end])
                ->whereIn('state_type_id', ['01', '03', '05', '07', '13']);
        };

        $sales_pen = (clone $docBase)()->where('establishment_id', $establishment_id)
            ->whereIn('document_type_id', ['01', '03', '08'])
            ->where('currency_type_id', 'PEN')
            ->sum('total');

        $sales_usd_pen = (clone $docBase)()->where('establishment_id', $establishment_id)
            ->whereIn('document_type_id', ['01', '03', '08'])
            ->where('currency_type_id', 'USD')
            ->selectRaw('SUM(total * exchange_rate_sale) as total_pen')
            ->value('total_pen') ?? 0;

        $credit_pen = (clone $docBase)()->where('establishment_id', $establishment_id)
            ->where('document_type_id', '07')
            ->where('currency_type_id', 'PEN')
            ->sum('total');

        $credit_usd_pen = (clone $docBase)()->where('establishment_id', $establishment_id)
            ->where('document_type_id', '07')
            ->where('currency_type_id', 'USD')
            ->selectRaw('SUM(total * exchange_rate_sale) as total_pen')
            ->value('total_pen') ?? 0;

        $documents_count = (clone $docBase)()->where('establishment_id', $establishment_id)
            ->whereIn('document_type_id', ['01', '03', '08'])
            ->count();

        // Pagos de documentos: 1 query agregada con JOIN (antes era N+1)
        $payments_pen = $tenant->table('document_payments')
            ->join('documents', 'documents.id', '=', 'document_payments.document_id')
            ->whereBetween('documents.date_of_issue', [$date_start, $date_end])
            ->where('documents.establishment_id', $establishment_id)
            ->whereIn('documents.state_type_id', ['01', '03', '05', '07', '13'])
            ->whereIn('documents.document_type_id', ['01', '03', '08'])
            ->where('documents.currency_type_id', 'PEN')
            ->sum('document_payments.payment');

        $payments_usd_pen = $tenant->table('document_payments')
            ->join('documents', 'documents.id', '=', 'document_payments.document_id')
            ->whereBetween('documents.date_of_issue', [$date_start, $date_end])
            ->where('documents.establishment_id', $establishment_id)
            ->whereIn('documents.state_type_id', ['01', '03', '05', '07', '13'])
            ->whereIn('documents.document_type_id', ['01', '03', '08'])
            ->where('documents.currency_type_id', 'USD')
            ->selectRaw('SUM(document_payments.payment * documents.exchange_rate_sale) as total_pen')
            ->value('total_pen') ?? 0;

        $documents_payment = (float) $payments_pen + (float) $payments_usd_pen;
        $documents_sales_total = (float) $sales_pen + (float) $sales_usd_pen;
        $documents_note_credit = (float) $credit_pen + (float) $credit_usd_pen;
        $documents_total = $documents_sales_total - $documents_note_credit;

        // ── NOTAS DE VENTA: 4 queries agregadas ────────────────────────────────
        $snBase = function () use ($tenant, $establishment_id, $date_start, $date_end) {
            return $tenant->table('sale_notes')
                ->whereBetween('date_of_issue', [$date_start, $date_end])
                ->where('changed', false)
                ->whereIn('state_type_id', ['01', '03', '05', '07', '13']);
        };

        $sn_pen = (clone $snBase)()->where('establishment_id', $establishment_id)
            ->where('currency_type_id', 'PEN')
            ->sum('total');

        $sn_usd_pen = (clone $snBase)()->where('establishment_id', $establishment_id)
            ->where('currency_type_id', 'USD')
            ->selectRaw('SUM(total * exchange_rate_sale) as total_pen')
            ->value('total_pen') ?? 0;

        $sn_count = (clone $snBase)()->where('establishment_id', $establishment_id)->count();

        // Pagos de NV: 1 query agregada con JOIN
        $sn_payments_pen = $tenant->table('sale_note_payments')
            ->join('sale_notes', 'sale_notes.id', '=', 'sale_note_payments.sale_note_id')
            ->whereBetween('sale_notes.date_of_issue', [$date_start, $date_end])
            ->where('sale_notes.establishment_id', $establishment_id)
            ->where('sale_notes.changed', false)
            ->whereIn('sale_notes.state_type_id', ['01', '03', '05', '07', '13'])
            ->where('sale_notes.currency_type_id', 'PEN')
            ->sum('sale_note_payments.payment');

        $sn_payments_usd_pen = $tenant->table('sale_note_payments')
            ->join('sale_notes', 'sale_notes.id', '=', 'sale_note_payments.sale_note_id')
            ->whereBetween('sale_notes.date_of_issue', [$date_start, $date_end])
            ->where('sale_notes.establishment_id', $establishment_id)
            ->where('sale_notes.changed', false)
            ->whereIn('sale_notes.state_type_id', ['01', '03', '05', '07', '13'])
            ->where('sale_notes.currency_type_id', 'USD')
            ->selectRaw('SUM(sale_note_payments.payment * sale_notes.exchange_rate_sale) as total_pen')
            ->value('total_pen') ?? 0;

        $sale_notes_payment = (float) $sn_payments_pen + (float) $sn_payments_usd_pen;
        $sale_notes_total = (float) $sn_pen + (float) $sn_usd_pen;

        // ── COMPRAS: 2 queries agregadas ──────────────────────────────────────
        $purchase_pen = $tenant->table('purchases')
            ->whereBetween('date_of_issue', [$date_start, $date_end])
            ->whereIn('state_type_id', ['01', '03', '05', '07', '13'])
            ->where('establishment_id', $establishment_id)
            ->where('currency_type_id', 'PEN')
            ->selectRaw('SUM(total + total_perception) as total_pen')
            ->value('total_pen') ?? 0;

        $purchase_usd_pen = $tenant->table('purchases')
            ->whereBetween('date_of_issue', [$date_start, $date_end])
            ->whereIn('state_type_id', ['01', '03', '05', '07', '13'])
            ->where('establishment_id', $establishment_id)
            ->where('currency_type_id', 'USD')
            ->selectRaw('SUM((total + total_perception) * exchange_rate_sale) as total_pen')
            ->value('total_pen') ?? 0;

        $purchases_total = (float) $purchase_pen + (float) $purchase_usd_pen;

        // ── GASTOS: 2 queries agregadas ───────────────────────────────────────
        $expense_pen = $tenant->table('expenses')
            ->whereBetween('date_of_issue', [$date_start, $date_end])
            ->where('state_type_id', '05')
            ->where('establishment_id', $establishment_id)
            ->where('currency_type_id', 'PEN')
            ->sum('total');

        $expense_usd_pen = $tenant->table('expenses')
            ->whereBetween('date_of_issue', [$date_start, $date_end])
            ->where('state_type_id', '05')
            ->where('establishment_id', $establishment_id)
            ->where('currency_type_id', 'USD')
            ->selectRaw('SUM(total * exchange_rate_sale) as total_pen')
            ->value('total_pen') ?? 0;

        $expenses_total = (float) $expense_pen + (float) $expense_usd_pen;

        $monthly_sales = $documents_total + $sale_notes_total;
        $sales_count = $documents_count + $sn_count;
        $average_ticket = $sales_count > 0 ? ($monthly_sales / $sales_count) : 0;
        $accounts_receivable = max($monthly_sales - ($documents_payment + $sale_notes_payment), 0);
        $net_utility = $monthly_sales - $purchases_total - $expenses_total;

        return [
            'monthly_sales' => round($monthly_sales, 2),
            'average_ticket' => round($average_ticket, 2),
            'accounts_receivable' => round($accounts_receivable, 2),
            'net_utility' => round($net_utility, 2),
            'income' => round($monthly_sales, 2),
            'egress' => round($purchases_total + $expenses_total, 2),
        ];
    }

    public function lowStock(array $request = [])
    {
        $filters = $this->resolveFilters($request);
        $establishment_id = $filters['establishment_id'] ?: optional(Establishment::select('id')->first())->id;
        $stock_limit = 10;

        // Sin 'with' → eager load item por separado después (1 sola query en vez de N)
        $itemIds = ItemWarehouse::query()
            ->whereHas('item', function ($query) {
                $query->whereNotIsSet()
                      ->where('status', true)
                      ->where('unit_type_id', '!=', 'ZZ');
            })
            ->whereHas('warehouse', function ($query) use ($establishment_id) {
                $query->where('establishment_id', $establishment_id);
            })
            ->where('stock', '<=', $stock_limit)
            ->select('item_id', 'stock')
            ->get();

        if ($itemIds->isEmpty()) {
            return ['items' => [], 'total' => 0];
        }

        // 1 query para traer los items necesarios (vs 1 por cada item anterior)
        $items_by_id = Item::query()
            ->whereIn('id', $itemIds->pluck('item_id'))
            ->select('id', 'description', 'stock_min')
            ->get()
            ->keyBy('id');

        $low = $itemIds->map(function ($row) use ($items_by_id) {
            $item = $items_by_id->get($row->item_id);
            return [
                'product' => $item ? $item->description : '(sin descripción)',
                'stock' => (float) $row->stock,
                'stock_min' => $item ? (float) $item->stock_min : 0.0,
            ];
        })->sortBy('stock')->values();

        return [
            'items' => $low->all(),
            'total' => $low->count(),
        ];
    }

    public function monthGoal()
    {
        $configuration = Configuration::first();
        $goal = (float) ($configuration->dashboard_goal_amount ?? 0);

        $ref = Carbon::now();
        $sales = $this->kpisForRange(
            $ref->copy()->startOfMonth()->format('Y-m-d'),
            $ref->copy()->endOfMonth()->format('Y-m-d')
        )['monthly_sales'];

        $day = (int) $ref->format('j');
        $days_in_month = (int) $ref->format('t');

        return [
            'enabled' => (bool) ($configuration->dashboard_goal_enabled ?? false),
            'goal' => round($goal, 2),
            'sales' => $sales,
            'percent' => $goal > 0 ? round(($sales / $goal) * 100, 1) : 0,
            'remaining' => round(max($goal - $sales, 0), 2),
            'projected' => $day > 0 ? round(($sales / $day) * $days_in_month, 2) : $sales,
        ];
    }

    public function debtors($request = [], $limit = 4)
    {
        if (is_numeric($request)) {
            $limit = (int) $request;
            $request = [];
        }

        $filters = $this->resolveFilters((array) $request);

        $document_payments = DB::connection('tenant')->table('document_payments')
            ->select('document_id', DB::raw('SUM(payment) as total_payment'))
            ->groupBy('document_id');

        $documents = DB::connection('tenant')->table('documents')
            ->join('persons', 'persons.id', '=', 'documents.customer_id')
            ->leftJoinSub($document_payments, 'payments', function ($join) {
                $join->on('documents.id', '=', 'payments.document_id');
            })
            ->leftJoinSub(Document::getQueryCreditNotes(), 'credit_notes', function ($join) {
                $join->on('documents.id', '=', 'credit_notes.affected_document_id');
            })
            ->leftJoin('invoices', 'invoices.document_id', '=', 'documents.id')
            ->whereIn('documents.state_type_id', ['01', '03', '05', '07', '13'])
            ->whereIn('documents.document_type_id', ['01', '03', '08'])
            ->where('documents.total_canceled', 0)
            ->when($filters['establishment_id'], function ($query) use ($filters) {
                $query->where('documents.establishment_id', $filters['establishment_id']);
            })
            ->select(
                'documents.id',
                'documents.customer_id',
                'persons.name as customer_name',
                DB::raw("CONCAT(documents.series, '-', documents.number) as number_full"),
                'documents.total',
                'documents.currency_type_id',
                'documents.exchange_rate_sale',
                DB::raw('IFNULL(payments.total_payment, 0) as total_payment'),
                DB::raw('IFNULL(credit_notes.total_credit_notes, 0) as total_credit_notes'),
                'invoices.date_of_due'
            )->get();

        $sale_note_payments = DB::connection('tenant')->table('sale_note_payments')
            ->select('sale_note_id', DB::raw('SUM(payment) as total_payment'))
            ->groupBy('sale_note_id');

        $sale_notes = DB::connection('tenant')->table('sale_notes')
            ->join('persons', 'persons.id', '=', 'sale_notes.customer_id')
            ->leftJoinSub($sale_note_payments, 'payments', function ($join) {
                $join->on('sale_notes.id', '=', 'payments.sale_note_id');
            })
            ->whereIn('sale_notes.state_type_id', ['01', '03', '05', '07', '13'])
            ->where('sale_notes.changed', false)
            ->where('sale_notes.total_canceled', false)
            ->when($filters['establishment_id'], function ($query) use ($filters) {
                $query->where('sale_notes.establishment_id', $filters['establishment_id']);
            })
            ->select(
                'sale_notes.id',
                'sale_notes.customer_id',
                'persons.name as customer_name',
                'sale_notes.filename as number_full',
                'sale_notes.total',
                'sale_notes.currency_type_id',
                'sale_notes.exchange_rate_sale',
                DB::raw('IFNULL(payments.total_payment, 0) as total_payment'),
                DB::raw('0 as total_credit_notes'),
                DB::raw('NULL as date_of_due')
            )->get();

        $today = Carbon::today();
        $customers = [];
        $formatDebtDue = function ($date_of_due) use ($today) {
            if (!$date_of_due) {
                return [
                    'due_days' => null,
                    'due_text' => 'sin vencimiento',
                    'status' => 'al_dia',
                ];
            }

            $days = $today->diffInDays(Carbon::parse($date_of_due)->startOfDay(), false);

            if ($days < 0) {
                $abs = abs($days);
                return [
                    'due_days' => $days,
                    'due_text' => "venció hace {$abs} " . ($abs === 1 ? 'día' : 'días'),
                    'status' => 'vencido',
                ];
            }

            if ($days === 0) {
                return [
                    'due_days' => 0,
                    'due_text' => 'vence hoy',
                    'status' => 'por_vencer',
                ];
            }

            return [
                'due_days' => $days,
                'due_text' => $days === 1 ? 'vence mañana' : "vence en {$days} días",
                'status' => $days <= 7 ? 'por_vencer' : 'al_dia',
            ];
        };

        foreach ($documents->concat($sale_notes) as $row) {
            $factor = ($row->currency_type_id == 'USD') ? (float) $row->exchange_rate_sale : 1;
            $balance = ((float) $row->total - (float) $row->total_credit_notes - (float) $row->total_payment) * $factor;

            if ($balance <= 0.005) {
                continue;
            }

            $cid = $row->customer_id;
            if (!isset($customers[$cid])) {
                $customers[$cid] = ['customer' => $row->customer_name, 'total_to_pay' => 0, 'due' => null, 'debts' => []];
            }

            $customers[$cid]['total_to_pay'] += $balance;

            $due_data = $formatDebtDue($row->date_of_due);
            $customers[$cid]['debts'][] = [
                'id' => $row->id,
                'number' => $row->number_full,
                'total_to_pay' => round($balance, 2),
                'due_days' => $due_data['due_days'],
                'due_text' => $due_data['due_text'],
                'status' => $due_data['status'],
            ];
        }

        $list = collect($customers)->sortByDesc('total_to_pay');

        $items = $list->take($limit)->map(function ($c) use ($today) {
            $status = 'al_dia';
            $due_text = null;

            if ($c['due']) {
                $days = $today->diffInDays($c['due'], false);
                if ($days < 0) {
                    $status = 'vencido';
                    $abs = abs($days);
                    $due_text = "venció hace {$abs} " . ($abs === 1 ? 'día' : 'días');
                } elseif ($days === 0) {
                    $status = 'por_vencer';
                    $due_text = 'vence hoy';
                } else {
                    $status = ($days <= 7) ? 'por_vencer' : 'al_dia';
                    $due_text = "vence en {$days} " . ($days === 1 ? 'día' : 'días');
                }
            }

            return [
                'customer' => $c['customer'],
                'total_to_pay' => round($c['total_to_pay'], 2),
                'status' => $status,
                'due_text' => $due_text,
            ];
        })->values();

        $items = $list->take($limit)->map(function ($c) {
            $debts = collect($c['debts'])->sortBy(function ($debt) {
                return is_null($debt['due_days']) ? 999999 : $debt['due_days'];
            })->values();
            $urgent_debt = $debts->first();

            return [
                'customer' => $c['customer'],
                'total_to_pay' => round($c['total_to_pay'], 2),
                'status' => $urgent_debt ? $urgent_debt['status'] : 'al_dia',
                'due_days' => $urgent_debt ? $urgent_debt['due_days'] : null,
                'due_text' => $urgent_debt ? $urgent_debt['due_text'] : null,
                'urgent_amount' => $urgent_debt ? $urgent_debt['total_to_pay'] : 0,
                'debts_count' => $debts->count(),
                'debts' => $debts,
            ];
        })->values();

        return [
            'items' => $items,
            'total' => round($list->sum('total_to_pay'), 2),
            'count' => $list->count(),
        ];
    }

    public function sunatStatus(array $request = [])
    {
        $filters = $this->resolveFilters($request);

        $counts = Document::query()
            ->selectRaw('state_type_id, COUNT(*) as total')
            ->when($filters['establishment_id'], function ($query) use ($filters) {
                $query->where('establishment_id', $filters['establishment_id']);
            })
            ->when($filters['date_start'] && $filters['date_end'], function ($query) use ($filters) {
                $query->whereBetween('date_of_issue', [$filters['date_start'], $filters['date_end']]);
            })
            ->groupBy('state_type_id')
            ->pluck('total', 'state_type_id');

        $sum = function ($ids) use ($counts) {
            $total = 0;
            foreach ((array) $ids as $id) {
                $total += (int) ($counts[$id] ?? 0);
            }
            return $total;
        };

        return [
            'accepted' => $sum('05'),
            'pending' => $sum(['01', '03']),
            'rejected' => $sum('09'),
        ];
    }

    private function paymentMethodsSubtitle(array $filters)
    {
        $subtitles = [
            'date' => 'Distribucion de cobros - dia seleccionado',
            'between_dates' => 'Distribucion de cobros - rango seleccionado',
            'month' => 'Distribucion de cobros - mes seleccionado',
            'between_months' => 'Distribucion de cobros - meses seleccionados',
            'last_week' => 'Distribucion de cobros - semana seleccionada',
            'all' => 'Distribucion de cobros - todos los registros',
        ];

        return $subtitles[$filters['period'] ?? 'month'] ?? 'Distribucion de cobros - periodo filtrado';
    }

    public function paymentMethods(array $request = [])
    {
        $filters = $this->resolveFilters($request);
        $date_start = $filters['date_start'] ?: Carbon::now()->startOfMonth()->format('Y-m-d');
        $date_end = $filters['date_end'] ?: Carbon::now()->endOfMonth()->format('Y-m-d');
        $tenant = DB::connection('tenant');

        // 2 queries agregadas (1 para documentos, 1 para NV) que devuelven los totales
        // POR método de pago, normalizados a PEN, con la descripción del método.
        $docBase = $tenant->table('document_payments')
            ->join('documents', 'documents.id', '=', 'document_payments.document_id')
            ->leftJoin('payment_method_types', 'payment_method_types.id', '=', 'document_payments.payment_method_type_id')
            ->whereBetween('documents.date_of_issue', [$date_start, $date_end])
            ->whereIn('documents.state_type_id', ['01', '03', '05', '07', '13'])
            ->whereIn('documents.document_type_id', ['01', '03', '08'])
            ->when($filters['establishment_id'], fn($q) => $q->where('documents.establishment_id', $filters['establishment_id']))
            ->selectRaw('payment_method_types.description as label,
                         SUM(CASE WHEN documents.currency_type_id = \'PEN\'
                                  THEN document_payments.payment
                                  ELSE document_payments.payment * documents.exchange_rate_sale END) as amount_pen')
            ->groupBy('payment_method_types.description');

        $snBase = $tenant->table('sale_note_payments')
            ->join('sale_notes', 'sale_notes.id', '=', 'sale_note_payments.sale_note_id')
            ->leftJoin('payment_method_types', 'payment_method_types.id', '=', 'sale_note_payments.payment_method_type_id')
            ->whereBetween('sale_notes.date_of_issue', [$date_start, $date_end])
            ->where('sale_notes.changed', false)
            ->whereIn('sale_notes.state_type_id', ['01', '03', '05', '07', '13'])
            ->when($filters['establishment_id'], fn($q) => $q->where('sale_notes.establishment_id', $filters['establishment_id']))
            ->selectRaw('payment_method_types.description as label,
                         SUM(CASE WHEN sale_notes.currency_type_id = \'PEN\'
                                  THEN sale_note_payments.payment
                                  ELSE sale_note_payments.payment * sale_notes.exchange_rate_sale END) as amount_pen')
            ->groupBy('payment_method_types.description');

        $totals = [];
        foreach ($docBase->get() as $row) {
            $label = $row->label ?: 'Otros';
            $totals[$label] = ($totals[$label] ?? 0) + (float) $row->amount_pen;
        }
        foreach ($snBase->get() as $row) {
            $label = $row->label ?: 'Otros';
            $totals[$label] = ($totals[$label] ?? 0) + (float) $row->amount_pen;
        }

        arsort($totals);

        $labels = [];
        $values = [];
        $total = 0;

        foreach ($totals as $label => $amount) {
            $amount = round($amount, 2);
            if ($amount <= 0) {
                continue;
            }
            $labels[] = $label;
            $values[] = $amount;
            $total += $amount;
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'total' => round($total, 2),
            'subtitle' => $this->paymentMethodsSubtitle($filters),
        ];
    }

    public function salesWeek(array $request = [])
    {
        $filters = $this->resolveFilters($request);

        if (($filters['period'] ?? null) !== 'all' && !empty($filters['date_start']) && !empty($filters['date_end'])) {
            $previous = $this->previousComparisonRange($filters);
            $labels = $this->rangeLabels($filters['date_start'], $filters['date_end']);
            $points = count($labels);

            return [
                'labels' => $labels,
                'previous_labels' => $this->rangeLabels($previous['start'], $previous['end']),
                'current' => $this->salesByRange($filters['date_start'], $filters['date_end'], $filters['establishment_id'], $points),
                'previous' => $this->salesByRange($previous['start'], $previous['end'], $filters['establishment_id'], $points),
                'subtitle' => 'Barras solidas = periodo filtrado - gris = periodo anterior',
            ];
        }

        $start_current = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $start_previous = $start_current->copy()->subWeek();

        $previous_labels = [];
        for ($i = 0; $i < 7; $i++) {
            $previous_labels[] = $start_previous->copy()->addDays($i)->format('d/m');
        }

        return [
            'labels' => ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
            'previous_labels' => $previous_labels,
            'current' => $this->dailySales($start_current, $filters['establishment_id']),
            'previous' => $this->dailySales($start_previous, $filters['establishment_id']),
            'subtitle' => 'Barras solidas = esta semana - gris = semana anterior',
        ];
    }

    private function dailySales($week_start, $establishment_id = null)
    {
        $tenant = DB::connection('tenant');
        $date_start = $week_start->format('Y-m-d');
        $date_end = $week_start->copy()->addDays(6)->format('Y-m-d');

        // 1 query agregada por tabla, agrupada por día (antes: 7 días × 14 queries = 98 queries)
        $documents_by_day = $tenant->table('documents')
            ->selectRaw("date_of_issue,
                         SUM(CASE WHEN currency_type_id = 'PEN' THEN total ELSE total * exchange_rate_sale END) as sales_pen,
                         SUM(CASE WHEN document_type_id = '07' THEN (CASE WHEN currency_type_id = 'PEN' THEN total ELSE total * exchange_rate_sale END) ELSE 0 END) as credit_pen")
            ->whereBetween('date_of_issue', [$date_start, $date_end])
            ->whereIn('state_type_id', ['01', '03', '05', '07', '13'])
            ->when($establishment_id, fn($q) => $q->where('establishment_id', $establishment_id))
            ->groupBy('date_of_issue')
            ->get()
            ->keyBy('date_of_issue');

        $sale_notes_by_day = $tenant->table('sale_notes')
            ->selectRaw("date_of_issue,
                         SUM(CASE WHEN currency_type_id = 'PEN' THEN total ELSE total * exchange_rate_sale END) as total_pen")
            ->whereBetween('date_of_issue', [$date_start, $date_end])
            ->where('changed', false)
            ->whereIn('state_type_id', ['01', '03', '05', '07', '13'])
            ->when($establishment_id, fn($q) => $q->where('establishment_id', $establishment_id))
            ->groupBy('date_of_issue')
            ->get()
            ->keyBy('date_of_issue');

        $values = array_fill(0, 7, 0.0);

        foreach ($documents_by_day as $date => $row) {
            $idx = Carbon::parse($date)->dayOfWeekIso - 1;
            $values[$idx] += ((float) $row->sales_pen) - ((float) $row->credit_pen);
        }

        foreach ($sale_notes_by_day as $date => $row) {
            $idx = Carbon::parse($date)->dayOfWeekIso - 1;
            $values[$idx] += (float) $row->total_pen;
        }

        return array_map(function ($value) {
            return round(max($value, 0), 2);
        }, $values);
    }

    private function rangeLabels($date_start, $date_end)
    {
        $start = Carbon::parse($date_start);
        $end = Carbon::parse($date_end);
        $days = $start->diffInDays($end) + 1;
        $labels = [];

        if ($days <= 31) {
            while ($start <= $end) {
                $labels[] = $start->format('d/m');
                $start->addDay();
            }

            return $labels;
        }

        while ($start <= $end) {
            $labels[] = $start->format('m/Y');
            $start = $start->copy()->endOfMonth()->addDay();
        }

        return $labels;
    }

    private function salesByRange($date_start, $date_end, $establishment_id = null, $points = null)
    {
        $tenant = DB::connection('tenant');
        $start = Carbon::parse($date_start);
        $end = Carbon::parse($date_end);
        $days = $start->diffInDays($end) + 1;
        $values = [];

        // 2 queries agregadas para todo el rango, agrupadas por día
        $docs_by_day = $tenant->table('documents')
            ->selectRaw("date_of_issue,
                         SUM(CASE WHEN currency_type_id = 'PEN' THEN total ELSE total * exchange_rate_sale END) as sales_pen,
                         SUM(CASE WHEN document_type_id = '07' THEN (CASE WHEN currency_type_id = 'PEN' THEN total ELSE total * exchange_rate_sale END) ELSE 0 END) as credit_pen")
            ->whereBetween('date_of_issue', [$date_start, $date_end])
            ->whereIn('state_type_id', ['01', '03', '05', '07', '13'])
            ->when($establishment_id, fn($q) => $q->where('establishment_id', $establishment_id))
            ->groupBy('date_of_issue')
            ->get()
            ->keyBy('date_of_issue');

        $sn_by_day = $tenant->table('sale_notes')
            ->selectRaw("date_of_issue,
                         SUM(CASE WHEN currency_type_id = 'PEN' THEN total ELSE total * exchange_rate_sale END) as total_pen")
            ->whereBetween('date_of_issue', [$date_start, $date_end])
            ->where('changed', false)
            ->whereIn('state_type_id', ['01', '03', '05', '07', '13'])
            ->when($establishment_id, fn($q) => $q->where('establishment_id', $establishment_id))
            ->groupBy('date_of_issue')
            ->get()
            ->keyBy('date_of_issue');

        if ($days <= 31) {
            $cursor = $start->copy();
            while ($cursor <= $end) {
                $d = $cursor->format('Y-m-d');
                $doc = $docs_by_day->get($d);
                $sn = $sn_by_day->get($d);
                $sales = ($doc ? (float) $doc->sales_pen - (float) $doc->credit_pen : 0)
                       + ($sn ? (float) $sn->total_pen : 0);
                $values[] = round(max($sales, 0), 2);
                $cursor->addDay();
            }

            return $this->normalizeSeriesLength($values, $points);
        }

        // Más de 31 días: agrupar por mes
        $cursor = $start->copy();
        while ($cursor <= $end) {
            $bucket_end = $cursor->copy()->endOfMonth();
            if ($bucket_end > $end) {
                $bucket_end = $end->copy();
            }

            $sales = 0;
            $inner = $cursor->copy();
            while ($inner <= $bucket_end) {
                $d = $inner->format('Y-m-d');
                $doc = $docs_by_day->get($d);
                $sn = $sn_by_day->get($d);
                $sales += ($doc ? (float) $doc->sales_pen - (float) $doc->credit_pen : 0)
                        + ($sn ? (float) $sn->total_pen : 0);
                $inner->addDay();
            }
            $values[] = round(max($sales, 0), 2);
            $cursor = $bucket_end->copy()->addDay();
        }

        return $this->normalizeSeriesLength($values, $points);
    }

    private function normalizeSeriesLength(array $values, $points = null)
    {
        if (!$points) {
            return $values;
        }

        if (count($values) > $points) {
            return array_slice($values, 0, $points);
        }

        while (count($values) < $points) {
            $values[] = 0;
        }

        return $values;
    }

    private function cashFlowSubtitle(array $filters)
    {
        $subtitles = [
            'date' => 'Ingresos vs egresos - dia seleccionado',
            'between_dates' => 'Ingresos vs egresos - rango seleccionado',
            'month' => 'Ingresos vs egresos - mes seleccionado',
            'between_months' => 'Ingresos vs egresos - meses seleccionados',
            'last_week' => 'Ingresos vs egresos - semana seleccionada',
            'all' => 'Ingresos vs egresos - ultimos 6 meses',
        ];

        return $subtitles[$filters['period'] ?? 'all'] ?? 'Ingresos vs egresos - periodo filtrado';
    }

    public function cashFlow($request = [], $months = 6)
    {
        if (is_numeric($request)) {
            $months = (int) $request;
            $request = [];
        }

        $filters = $this->resolveFilters((array) $request);
        $tenant = DB::connection('tenant');
        $months_es = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Set', 'Oct', 'Nov', 'Dic'];

        $labels = [];
        $income = [];
        $egress = [];

        if (($filters['period'] ?? null) !== 'all' && !empty($filters['date_start']) && !empty($filters['date_end'])) {
            $labels = $this->rangeLabels($filters['date_start'], $filters['date_end']);
            $start = Carbon::parse($filters['date_start']);
            $end = Carbon::parse($filters['date_end']);
            $days = $start->diffInDays($end) + 1;

            // 4 queries agregadas en lugar de days × kpisForRange (que era days × 16 queries)
            $docs_by_bucket = $tenant->table('documents')
                ->selectRaw("date_of_issue,
                             SUM(CASE WHEN currency_type_id = 'PEN' THEN total ELSE total * exchange_rate_sale END) as sales_pen,
                             SUM(CASE WHEN document_type_id = '07' THEN (CASE WHEN currency_type_id = 'PEN' THEN total ELSE total * exchange_rate_sale END) ELSE 0 END) as credit_pen")
                ->whereBetween('date_of_issue', [$filters['date_start'], $filters['date_end']])
                ->whereIn('state_type_id', ['01', '03', '05', '07', '13'])
                ->when($filters['establishment_id'], fn($q) => $q->where('establishment_id', $filters['establishment_id']))
                ->groupBy('date_of_issue')
                ->get()
                ->keyBy('date_of_issue');

            $sn_by_bucket = $tenant->table('sale_notes')
                ->selectRaw("date_of_issue,
                             SUM(CASE WHEN currency_type_id = 'PEN' THEN total ELSE total * exchange_rate_sale END) as total_pen")
                ->whereBetween('date_of_issue', [$filters['date_start'], $filters['date_end']])
                ->where('changed', false)
                ->whereIn('state_type_id', ['01', '03', '05', '07', '13'])
                ->when($filters['establishment_id'], fn($q) => $q->where('establishment_id', $filters['establishment_id']))
                ->groupBy('date_of_issue')
                ->get()
                ->keyBy('date_of_issue');

            $egress_by_bucket = $tenant->table('purchases')
                ->selectRaw("date_of_issue,
                             SUM(CASE WHEN currency_type_id = 'PEN' THEN (total + total_perception) ELSE (total + total_perception) * exchange_rate_sale END) as purchase_pen")
                ->whereBetween('date_of_issue', [$filters['date_start'], $filters['date_end']])
                ->whereIn('state_type_id', ['01', '03', '05', '07', '13'])
                ->when($filters['establishment_id'], fn($q) => $q->where('establishment_id', $filters['establishment_id']))
                ->groupBy('date_of_issue')
                ->get()
                ->keyBy('date_of_issue');

            if ($days <= 31) {
                $cursor = $start->copy();
                while ($cursor <= $end) {
                    $d = $cursor->format('Y-m-d');
                    $doc = $docs_by_bucket->get($d);
                    $sn = $sn_by_bucket->get($d);
                    $pur = $egress_by_bucket->get($d);
                    $income[] = round(($doc ? (float) $doc->sales_pen - (float) $doc->credit_pen : 0)
                                    + ($sn ? (float) $sn->total_pen : 0), 2);
                    $egress[] = round($pur ? (float) $pur->purchase_pen : 0, 2);
                    $cursor->addDay();
                }
            } else {
                $cursor = $start->copy();
                while ($cursor <= $end) {
                    $bucket_end = $cursor->copy()->endOfMonth();
                    if ($bucket_end > $end) {
                        $bucket_end = $end->copy();
                    }

                    $income_sum = 0;
                    $egress_sum = 0;
                    $inner = $cursor->copy();
                    while ($inner <= $bucket_end) {
                        $d = $inner->format('Y-m-d');
                        $doc = $docs_by_bucket->get($d);
                        $sn = $sn_by_bucket->get($d);
                        $pur = $egress_by_bucket->get($d);
                        $income_sum += ($doc ? (float) $doc->sales_pen - (float) $doc->credit_pen : 0)
                                     + ($sn ? (float) $sn->total_pen : 0);
                        $egress_sum += $pur ? (float) $pur->purchase_pen : 0;
                        $inner->addDay();
                    }
                    $income[] = round($income_sum, 2);
                    $egress[] = round($egress_sum, 2);
                    $cursor = $bucket_end->copy()->addDay();
                }
            }

            return array_merge(compact('labels', 'income', 'egress'), [
                'subtitle' => $this->cashFlowSubtitle($filters),
            ]);
        }

        $base = !empty($filters['date_start']) ? Carbon::parse($filters['date_start'])->startOfMonth() : Carbon::now()->startOfMonth();
        $start_global = $base->copy()->subMonths($months - 1)->startOfMonth();
        $end_global = $base->copy()->endOfMonth();

        // 4 queries agregadas para los $months meses (1 sola pasada)
        $docs_by_month = $tenant->table('documents')
            ->selectRaw("DATE_FORMAT(date_of_issue, '%Y-%m') as month,
                         SUM(CASE WHEN currency_type_id = 'PEN' THEN total ELSE total * exchange_rate_sale END) as sales_pen,
                         SUM(CASE WHEN document_type_id = '07' THEN (CASE WHEN currency_type_id = 'PEN' THEN total ELSE total * exchange_rate_sale END) ELSE 0 END) as credit_pen")
            ->whereBetween('date_of_issue', [$start_global->format('Y-m-d'), $end_global->format('Y-m-d')])
            ->whereIn('state_type_id', ['01', '03', '05', '07', '13'])
            ->when($filters['establishment_id'], fn($q) => $q->where('establishment_id', $filters['establishment_id']))
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        $sn_by_month = $tenant->table('sale_notes')
            ->selectRaw("DATE_FORMAT(date_of_issue, '%Y-%m') as month,
                         SUM(CASE WHEN currency_type_id = 'PEN' THEN total ELSE total * exchange_rate_sale END) as total_pen")
            ->whereBetween('date_of_issue', [$start_global->format('Y-m-d'), $end_global->format('Y-m-d')])
            ->where('changed', false)
            ->whereIn('state_type_id', ['01', '03', '05', '07', '13'])
            ->when($filters['establishment_id'], fn($q) => $q->where('establishment_id', $filters['establishment_id']))
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        $purchases_by_month = $tenant->table('purchases')
            ->selectRaw("DATE_FORMAT(date_of_issue, '%Y-%m') as month,
                         SUM(CASE WHEN currency_type_id = 'PEN' THEN (total + total_perception) ELSE (total + total_perception) * exchange_rate_sale END) as purchase_pen")
            ->whereBetween('date_of_issue', [$start_global->format('Y-m-d'), $end_global->format('Y-m-d')])
            ->whereIn('state_type_id', ['01', '03', '05', '07', '13'])
            ->when($filters['establishment_id'], fn($q) => $q->where('establishment_id', $filters['establishment_id']))
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        for ($i = $months - 1; $i >= 0; $i--) {
            $ref = $base->copy()->subMonths($i);
            $month_key = $ref->format('Y-m');

            $doc = $docs_by_month->get($month_key);
            $sn = $sn_by_month->get($month_key);
            $pur = $purchases_by_month->get($month_key);

            $labels[] = $months_es[(int) $ref->format('n')];
            $income[] = round(($doc ? (float) $doc->sales_pen - (float) $doc->credit_pen : 0)
                            + ($sn ? (float) $sn->total_pen : 0), 2);
            $egress[] = round($pur ? (float) $pur->purchase_pen : 0, 2);
        }

        return array_merge(compact('labels', 'income', 'egress'), [
            'subtitle' => $this->cashFlowSubtitle($filters),
        ]);
    }

    /**
     * @param $establishment_id
     * @param $date_start
     * @param $date_end
     * @return array
     */
    private function sale_note_totals($establishment_id, $date_start, $date_end)
    {
        $tenant = DB::connection('tenant');

        $builder = $tenant->table('sale_notes')
            ->where('establishment_id', $establishment_id)
            ->where('changed', false)
            ->whereIn('state_type_id', ['01', '03', '05', '07', '13']);

        if ($date_start && $date_end) {
            $builder->whereBetween('date_of_issue', [$date_start, $date_end]);
        }

        $agg = (clone $builder)->selectRaw("
            SUM(CASE WHEN currency_type_id = 'PEN' THEN total ELSE 0 END) as pen,
            SUM(CASE WHEN currency_type_id = 'USD' THEN total * exchange_rate_sale ELSE 0 END) as usd_pen
        ")->first();

        $sale_note_total = (float) ($agg->pen ?? 0) + (float) ($agg->usd_pen ?? 0);

        // Pagos agregados con JOIN
        $payment_query = $tenant->table('sale_note_payments')
            ->join('sale_notes', 'sale_notes.id', '=', 'sale_note_payments.sale_note_id')
            ->where('sale_notes.establishment_id', $establishment_id)
            ->where('sale_notes.changed', false)
            ->whereIn('sale_notes.state_type_id', ['01', '03', '05', '07', '13']);

        if ($date_start && $date_end) {
            $payment_query->whereBetween('sale_notes.date_of_issue', [$date_start, $date_end]);
        }

        $payments = (clone $payment_query)->selectRaw("
            SUM(CASE WHEN sale_notes.currency_type_id = 'PEN' THEN sale_note_payments.payment ELSE 0 END) as pen_pay,
            SUM(CASE WHEN sale_notes.currency_type_id = 'USD' THEN sale_note_payments.payment * sale_notes.exchange_rate_sale ELSE 0 END) as usd_pay
        ")->first();

        $sale_note_total_payment = (float) ($payments->pen_pay ?? 0) + (float) ($payments->usd_pay ?? 0);
        $sale_note_total_to_pay = $sale_note_total - $sale_note_total_payment;

        return [
            'totals' => [
                'total_payment' => number_format($sale_note_total_payment, 2, ".", ""),
                'total_to_pay' => number_format($sale_note_total_to_pay, 2, ".", ""),
                'total' => number_format($sale_note_total, 2, ".", ""),
            ],
            'graph' => [
                'labels' => ['Total cobrado', 'Pendiente de cobro'],
                'datasets' => [
                    [
                        'label' => 'Notas de venta',
                        'data' => [round($sale_note_total_payment, 2), round($sale_note_total_to_pay, 2)],
                        'backgroundColor' => [
                            'rgb(36, 71, 232, .1)',
                            'rgb(254, 0, 108, .1)',
                        ],
                        'borderColor' => [
                            'rgb(36, 71, 232)',
                            'rgb(254, 0, 108)',
                        ]
                    ]
                ],
            ]
        ];
    }


    /**
     * 
     * Obtener totales de cpe
     * 
     * Usado en:
     * App\Traits\LockedEmissionTrait - Control de limite de ventas mensual
     *
     * @param  string $start_date
     * @param  string $end_date
     * @return float
     */
    public function sale_note_totals_global($start_date = null, $end_date = null)
    {
        $sale_notes_query = SaleNote::without(['user', 'soap_type', 'state_type', 'currency_type', 'items'])
            ->where('changed', false)
            ->whereStateTypeAccepted()
            ->select('id', 'currency_type_id', 'total', 'exchange_rate_sale');

        if($start_date && $end_date)
        {
            $sale_notes_query->whereBetween('date_of_issue', [$start_date, $end_date]);
        }

        $sale_notes = $sale_notes_query->get();

        //PEN
        $sale_note_total_pen = 0;
        $sale_note_total_payment_pen = 0;

        $sale_note_total_pen = collect($sale_notes->where('currency_type_id', 'PEN'))->sum('total');

        //USD
        $sale_note_total_usd = 0;
        $sale_note_total_payment_usd = 0;

        //TWO CURRENCY
        foreach ($sale_notes as $sale_note)
        {

            if($sale_note->currency_type_id == 'PEN'){

                $sale_note_total_payment_pen += collect($sale_note->payments)->sum('payment');

            }else{

                $sale_note_total_usd += $sale_note->total * $sale_note->exchange_rate_sale;
                $sale_note_total_payment_usd += collect($sale_note->payments)->sum('payment') * $sale_note->exchange_rate_sale;

            }
        }

        //TOTALS
        $sale_note_total = $sale_note_total_pen + $sale_note_total_usd;

        return number_format($sale_note_total,2, ".", "");
    }

    /**
     * @param $establishment_id
     * @param $date_start
     * @param $date_end
     * @return array
     */
    private function document_totals($establishment_id, $date_start, $date_end)
    {
        $tenant = DB::connection('tenant');
        $where_state = ['01','03','05','07','13'];
        $where_type  = ['01','03','08'];

        $builder = $tenant->table('documents')
            ->where('establishment_id', $establishment_id)
            ->whereIn('state_type_id', $where_state);

        if ($date_start && $date_end) {
            $builder->whereBetween('date_of_issue', [$date_start, $date_end]);
        }

        $agg = (clone $builder)->selectRaw("
            SUM(CASE WHEN currency_type_id='PEN' AND document_type_id IN ('01','03','08') THEN total ELSE 0 END) as pen_total,
            SUM(CASE WHEN currency_type_id='USD' AND document_type_id IN ('01','03','08') THEN total*exchange_rate_sale ELSE 0 END) as usd_pen,
            SUM(CASE WHEN currency_type_id='PEN' AND document_type_id='07' THEN total ELSE 0 END) as nc_pen,
            SUM(CASE WHEN currency_type_id='USD' AND document_type_id='07' THEN total*exchange_rate_sale ELSE 0 END) as nc_usd_pen
        ")->first();

        $document_total_pen = (float) ($agg->pen_total ?? 0);
        $document_total_usd = (float) ($agg->usd_pen ?? 0);
        $document_total_note_credit = (float) ($agg->nc_pen ?? 0) + (float) ($agg->nc_usd_pen ?? 0);
        $document_total = round(($document_total_pen + $document_total_usd - $document_total_note_credit), 2);

        // Pagos agregados con JOIN
        $payment_query = $tenant->table('document_payments')
            ->join('documents', 'documents.id', '=', 'document_payments.document_id')
            ->where('documents.establishment_id', $establishment_id)
            ->whereIn('documents.state_type_id', $where_state)
            ->whereIn('documents.document_type_id', $where_type);

        if ($date_start && $date_end) {
            $payment_query->whereBetween('documents.date_of_issue', [$date_start, $date_end]);
        }

        $payments = (clone $payment_query)->selectRaw("
            SUM(CASE WHEN documents.currency_type_id = 'PEN' THEN document_payments.payment ELSE 0 END) as pen_pay,
            SUM(CASE WHEN documents.currency_type_id = 'USD' THEN document_payments.payment * documents.exchange_rate_sale ELSE 0 END) as usd_pay
        ")->first();

        $document_total_payment = (float) ($payments->pen_pay ?? 0) + (float) ($payments->usd_pay ?? 0);
        $document_total_to_pay = $document_total - $document_total_payment;

        return [
            'totals' => [
                'total_payment' => number_format($document_total_payment, 2, ".", ""),
                'total_to_pay' => number_format($document_total_to_pay, 2, ".", ""),
                'total' => number_format($document_total, 2, ".", ""),
            ],
            'graph' => [
                'labels' => ['Total cobrado', 'Pendiente de cobro'],
                'datasets' => [
                    [
                        'label' => 'Comprobantes',
                        'data' => [round($document_total_payment, 2), round($document_total_to_pay, 2)],
                        'backgroundColor' => [
                            'rgb(36, 71, 232, .1)',
                            'rgb(254, 0, 108, .1)',
                        ],
                        'borderColor' => [
                            'rgb(36, 71, 232)',
                            'rgb(254, 0, 108)',
                        ],
                    ]
                ],
            ]
        ];
    }
    
    
    /**
     * 
     * Obtener totales de cpe
     * 
     * Usado en:
     * App\Traits\LockedEmissionTrait - Control de limite de ventas mensual
     *
     * @param  string $start_date
     * @param  string $end_date
     * @return float
     */
    public function document_totals_globals($start_date = null, $end_date = null)
    {
        $documents_query = Document::without(['user', 'soap_type', 'state_type', 'document_type', 'currency_type', 'group', 'items', 'invoice', 'note'])
                                    ->select('id', 'state_type_id', 'document_type_id', 'currency_type_id', 'total', 'exchange_rate_sale');


        if($start_date && $end_date)
        {
            $documents_query->whereBetween('date_of_issue', [$start_date, $end_date]);
        }

        $documents = $documents_query->get();
        
        //PEN
        $document_total_pen = 0;
        $document_total_payment_pen = 0;
        $document_total_note_credit_pen = 0;

        $document_total_pen = collect($documents->whereIn('state_type_id', ['01','03','05','07','13'])->whereIn('document_type_id', ['01','03','08']))->where('currency_type_id', 'PEN')->sum('total');


        //USD
        $document_total_usd = 0;
        $document_total_note_credit_usd = 0;
        $document_total_payment_usd = 0;

        $documents_usd = $documents->whereIn('state_type_id', ['01','03','05','07','13'])
                                    ->whereIn('document_type_id', ['01','03','08'])
                                    ->where('currency_type_id', 'USD');

        foreach ($documents_usd as $dusd) {
            $document_total_usd += $dusd->total * $dusd->exchange_rate_sale;
        }

        //TWO CURRENCY

        foreach ($documents as $document)
        {
            if($document->currency_type_id == 'PEN'){

                if(in_array($document->state_type_id,['01','03','05','07','13'])){

                    $document_total_payment_pen += collect($document->payments)->sum('payment');
                    $document_total_note_credit_pen += ($document->document_type_id == '07') ? $document->total:0; //nota de credito

                }


            }else{

                if(in_array($document->state_type_id,['01','03','05','07','13'])){

                    $document_total_payment_usd += collect($document->payments)->sum('payment') * $document->exchange_rate_sale;
                    $document_total_note_credit_usd += ($document->document_type_id == '07') ? $document->total * $document->exchange_rate_sale:0; //nota de credito

                }

            }

        }

        //TOTALS
        $document_total = $document_total_pen + $document_total_usd;
        $document_total_note_credit = $document_total_note_credit_pen + $document_total_note_credit_usd;

        $document_total = round(($document_total - $document_total_note_credit),2);

        return number_format($document_total,2, ".", "");
    }

    /**
     * Total de ventas (comprobantes netos de NC + notas de venta), normalizado a PEN
     *
     * @param int    $establishment_id
     * @param string $date_start  Y-m-d
     * @param string $date_end    Y-m-d
     *
     * @return array
     */
    public function salesTotalByRange($establishment_id, $date_start, $date_end)
    {
        $documents_query = Document::query()
            ->where('establishment_id', $establishment_id)
            ->whereIn('state_type_id', ['01','03','05','07','13']);

        if ($date_start && $date_end) {
            $documents_query->whereBetween('date_of_issue', [$date_start, $date_end]);
        }

        $documents = $documents_query->get();

        $document_total = 0;
        $document_note_credit = 0;
        foreach ($documents as $doc) {
            $factor = ($doc->currency_type_id == 'USD') ? $doc->exchange_rate_sale : 1;
            if (in_array($doc->document_type_id, ['01','03','08'])) {
                $document_total += $doc->total * $factor;
            } elseif ($doc->document_type_id == '07') { // nota de crédito
                $document_note_credit += $doc->total * $factor;
            }
        }
        $documents_total = round($document_total - $document_note_credit, 2);

        $sale_notes_query = SaleNote::query()
            ->where('establishment_id', $establishment_id)
            ->where('changed', false)
            ->whereStateTypeAccepted();

        if ($date_start && $date_end) {
            $sale_notes_query->whereBetween('date_of_issue', [$date_start, $date_end]);
        }

        $sale_notes = $sale_notes_query->get();

        $sale_notes_total = 0;
        foreach ($sale_notes as $sn) {
            $factor = ($sn->currency_type_id == 'USD') ? $sn->exchange_rate_sale : 1;
            $sale_notes_total += $sn->total * $factor;
        }
        $sale_notes_total = round($sale_notes_total, 2);

        return [
            'documents_total' => $documents_total,
            'sale_notes_total' => $sale_notes_total,
            'total' => round($documents_total + $sale_notes_total, 2),
        ];
    }

    /**
     * @param $establishment_id
     * @param $date_start
     * @param $date_end
     * @param $period
     * @param $month_start
     * @param $month_end
     * @return array
     */
    private function totals($establishment_id, $date_start, $date_end, $period, $month_start, $month_end)
    {
        $tenant = DB::connection('tenant');
        $docSelect = 'id,date_of_issue,time_of_issue,currency_type_id,exchange_rate_sale,total,document_type_id,state_type_id';

        if($date_start && $date_end){
            // Calcular totales agregados con SQL (sin descargar rows)
            $doc_aggs = $tenant->table('documents')
                ->where('establishment_id', $establishment_id)
                ->whereBetween('date_of_issue', [$date_start, $date_end])
                ->selectRaw("SUM(CASE WHEN currency_type_id='PEN' AND state_type_id IN ('01','03','05','07','13') AND document_type_id IN ('01','03','08') THEN total ELSE 0 END) as pen,
                             SUM(CASE WHEN currency_type_id='USD' AND state_type_id IN ('01','03','05','07','13') AND document_type_id IN ('01','03','08') THEN total*exchange_rate_sale ELSE 0 END) as usd_pen,
                             SUM(CASE WHEN state_type_id IN ('01','03','05','07','13') AND document_type_id='07' AND currency_type_id='PEN' THEN total ELSE 0 END) as nc_pen,
                             SUM(CASE WHEN state_type_id IN ('01','03','05','07','13') AND document_type_id='07' AND currency_type_id='USD' THEN total*exchange_rate_sale ELSE 0 END) as nc_usd_pen")
                ->first();

            $sn_aggs = $tenant->table('sale_notes')
                ->where('establishment_id', $establishment_id)
                ->where('changed', false)
                ->whereBetween('date_of_issue', [$date_start, $date_end])
                ->whereIn('state_type_id', ['01', '03', '05', '07', '13'])
                ->selectRaw("SUM(CASE WHEN currency_type_id='PEN' THEN total ELSE 0 END) as pen,
                             SUM(CASE WHEN currency_type_id='USD' THEN total*exchange_rate_sale ELSE 0 END) as usd_pen")
                ->first();

            // Descargar solo las columnas necesarias para el chart (evita lazy loads)
            $sale_notes = $tenant->table('sale_notes')
                ->select('id', 'date_of_issue', 'time_of_issue', 'currency_type_id', 'exchange_rate_sale', 'total')
                ->where('establishment_id', $establishment_id)
                ->where('changed', false)
                ->whereBetween('date_of_issue', [$date_start, $date_end])
                ->whereIn('state_type_id', ['01', '03', '05', '07', '13'])
                ->get();

            $documents = $tenant->table('documents')
                ->selectRaw($docSelect)
                ->where('establishment_id', $establishment_id)
                ->whereBetween('date_of_issue', [$date_start, $date_end])
                ->get();

        }else{
            $doc_aggs = $tenant->table('documents')
                ->where('establishment_id', $establishment_id)
                ->selectRaw("SUM(CASE WHEN currency_type_id='PEN' AND state_type_id IN ('01','03','05','07','13') AND document_type_id IN ('01','03','08') THEN total ELSE 0 END) as pen,
                             SUM(CASE WHEN currency_type_id='USD' AND state_type_id IN ('01','03','05','07','13') AND document_type_id IN ('01','03','08') THEN total*exchange_rate_sale ELSE 0 END) as usd_pen,
                             SUM(CASE WHEN state_type_id IN ('01','03','05','07','13') AND document_type_id='07' AND currency_type_id='PEN' THEN total ELSE 0 END) as nc_pen,
                             SUM(CASE WHEN state_type_id IN ('01','03','05','07','13') AND document_type_id='07' AND currency_type_id='USD' THEN total*exchange_rate_sale ELSE 0 END) as nc_usd_pen")
                ->first();

            $sn_aggs = $tenant->table('sale_notes')
                ->where('establishment_id', $establishment_id)
                ->where('changed', false)
                ->whereIn('state_type_id', ['01', '03', '05', '07', '13'])
                ->selectRaw("SUM(CASE WHEN currency_type_id='PEN' THEN total ELSE 0 END) as pen,
                             SUM(CASE WHEN currency_type_id='USD' THEN total*exchange_rate_sale ELSE 0 END) as usd_pen")
                ->first();

            $sale_notes = $tenant->table('sale_notes')
                ->select('id', 'date_of_issue', 'time_of_issue', 'currency_type_id', 'exchange_rate_sale', 'total')
                ->where('establishment_id', $establishment_id)
                ->where('changed', false)
                ->whereIn('state_type_id', ['01', '03', '05', '07', '13'])
                ->get();

            $documents = $tenant->table('documents')
                ->selectRaw($docSelect)
                ->where('establishment_id', $establishment_id)
                ->get();
        }

        //DOCUMENT totals (from SQL aggregations)
        $document_total = (float) ($doc_aggs->pen ?? 0) + (float) ($doc_aggs->usd_pen ?? 0);
        $document_total_note_credit = (float) ($doc_aggs->nc_pen ?? 0) + (float) ($doc_aggs->nc_usd_pen ?? 0);
        $documents_total = $document_total - $document_total_note_credit;

        //SALE NOTE totals (from SQL aggregations)
        $sale_note_total_pen = (float) ($sn_aggs->pen ?? 0);
        $sale_note_total_usd = (float) ($sn_aggs->usd_pen ?? 0);
        $sale_notes_total = $sale_note_total_pen + $sale_note_total_usd;

        //TOTALS
        $total = $sale_notes_total + $documents_total;

        // dd($period, $month_start, $month_end);

        // if(in_array($period, ['month', 'between_months'])) {
        //     if($month_start === $month_end) {
        //         $data_array = $this->getDocumentsByDays($sale_notes, $documents, $date_start, $date_end);
        //     } else {
        //         $data_array = $this->getDocumentsByMonths($sale_notes, $documents, $month_start, $month_end);
        //     }
        // }

        if($period == 'month')
        {
            $data_array = $this->getDocumentsByDays($sale_notes, $documents, $date_start, $date_end);
        }
        else if($period == 'between_months' && $month_start === $month_end)
        {
            $data_array = $this->getDocumentsByDays($sale_notes, $documents, $date_start, $date_end);
        }
        else if($period == 'between_months')
        {
            $data_array = $this->getDocumentsByMonths($sale_notes, $documents, $month_start, $month_end);
        }
        else
        {
            if($date_start === $date_end) {
                $data_array = $this->getDocumentsByHours($sale_notes, $documents);
            } else {
                $data_array = $this->getDocumentsByDays($sale_notes, $documents, $date_start, $date_end);
            }
        }

        return [
            'totals' => [
                'total_documents' => number_format($documents_total,2, ".", ""),
                'total_sale_notes' => number_format($sale_notes_total,2, ".", ""),
                'total' => number_format($total,2, ".", ""),
            ],
            'graph' => [
                'labels' => array_keys($data_array['total_array']),
                'datasets' => [
                    [
                        'label' => 'Total notas de venta',
                        'data' => array_values($data_array['sale_notes_array']),
                        'backgroundColor' => 'rgba(254, 0, 108, .1)',
                        'borderColor' => 'rgb(254, 0, 108)',
                        'borderWidth' => 2,
                        'fill' => true,
                        'lineTension' => 0.3,
                        'pointRadius' => 1
                    ],
                    [
                        'label' => 'Total comprobantes',
                        'data' => array_values($data_array['documents_array']),
                        'backgroundColor' => 'rgba(36, 71, 232, .1)',
                        'borderColor' => 'rgb(36, 71, 232)',
                        'borderWidth' => 2,
                        'fill' => true,
                        'lineTension' => 0.3,
                        'pointRadius' => 1
                    ],
                    [
                        'label' => 'Total',
                        'data' => array_values($data_array['total_array']),
                        'backgroundColor' => 'rgba(177, 184, 194, .1)',
                        'borderColor' => 'rgb(177, 184, 194)',
                        'borderWidth' => 2,
                        'fill' => true,
                        'lineTension' => 0.3,
                        'pointRadius' => 1
                    ]

                ],
            ]
        ];
    }

    private function getDocumentsByHours($sale_notes, $documents)
    {
        $sale_notes_array = [];
        $documents_array = [];
        $total_array = [];
        $document_total = 0;
        $document_total_note_credit = 0;

        $h_start = 0;
        $h_end = 23;

        for ($h = $h_start; $h <= $h_end; $h++)
        {
            $h_format = str_pad($h, 2, '0', STR_PAD_LEFT);

            //SALE NOTE
            $sale_note_total_pen = 0;
            $sale_note_total_col_usd = [];
            $sale_note_total_usd = 0;

            $sale_note_total_pen = $sale_notes->filter(function ($row) use($h_format) {
                return substr($row->time_of_issue, 0, 2) === $h_format;
            })->where('currency_type_id', 'PEN')->sum('total');

            $sale_note_total_col_usd = $sale_notes->filter(function ($row) use($h_format) {
                return substr($row->time_of_issue, 0, 2) === $h_format;
            })->where('currency_type_id', 'USD');

            foreach ($sale_note_total_col_usd as $sn) {
                $sale_note_total_usd += $sn->total * $sn->exchange_rate_sale;
            }

            $sale_note_total = $sale_note_total_pen + $sale_note_total_usd;
            $sale_notes_array[$h_format.'h'] = round($sale_note_total, 2);

            //SALE NOTE


            //DOCUMENT
            $document_total_pen = 0;
            $document_total_col_usd = [];
            $document_total_usd = 0;
            $document_total_nc_col_usd = [];
            $document_total_note_credit_usd = 0;

            $document_total_pen = $documents->filter(function ($row) use($h_format) {
                return substr($row->time_of_issue, 0, 2) === $h_format;
            })->whereIn('state_type_id', ['01','03','05','07','13'])->where('currency_type_id', 'PEN')->whereIn('document_type_id', ['01','03','08'])->sum('total');

            $document_total_col_usd = $documents->filter(function ($row) use($h_format) {
                return substr($row->time_of_issue, 0, 2) === $h_format;
            })->whereIn('state_type_id', ['01','03','05','07','13'])->where('currency_type_id', 'USD')->whereIn('document_type_id', ['01','03','08']);

            foreach ($document_total_col_usd as $doc) {
                $document_total_usd += $doc->total * $doc->exchange_rate_sale;
            }

            //NC
            $document_total_note_credit_pen = $documents->filter(function ($row) use($h_format) {
                return substr($row->time_of_issue, 0, 2) === $h_format;
            })->whereIn('state_type_id', ['01','03','05','07','13'])->where('document_type_id', '07')->where('currency_type_id', 'PEN')->sum('total');

            $document_total_nc_col_usd = $documents->filter(function ($row) use($h_format) {
                return substr($row->time_of_issue, 0, 2) === $h_format;
            })->whereIn('state_type_id', ['01','03','05','07','13'])->where('document_type_id', '07')->where('currency_type_id', 'USD');

            foreach ($document_total_nc_col_usd as $docnc) {
                $document_total_note_credit_usd += $docnc->total * $docnc->exchange_rate_sale;
            }

            $d_total = $document_total_pen + $document_total_usd;
            $d_total_nc = $document_total_note_credit_pen + $document_total_note_credit_usd;

            $document_total = $d_total - $d_total_nc;
            //DOCUMENT

            $documents_array[$h_format.'h'] = round($document_total, 2);

            $total_array[$h_format.'h'] = round($sale_note_total + $document_total,2);
        }

        return compact('sale_notes_array', 'documents_array', 'total_array');
    }

    private function getDocumentsByDays($sale_notes, $documents, $date_start, $date_end)
    {
        $sale_notes_array = [];
        $documents_array = [];
        $total_array = [];
        $document_total = 0;
        $document_total_note_credit = 0;

        $d_start = Carbon::parse($date_start);
        $d_end = Carbon::parse($date_end);

        while ($d_start <= $d_end)
        {

            //SALE NOTE
            $sale_note_total_pen = collect($sale_notes->where('currency_type_id', 'PEN'))->where('date_of_issue', $d_start)->sum('total');

            $sale_note_total_usd = collect($sale_notes->where('currency_type_id', 'USD'))->where('date_of_issue', $d_start)->map(function ($item, $key) {
                return $item->total * $item->exchange_rate_sale;
            })->sum();

            $sale_note_total = round($sale_note_total_pen + $sale_note_total_usd, 2);
            $sale_notes_array[$d_start->format('d').'d'] = $sale_note_total;

            //DOCUMENT
            $document_total_pen = collect($documents)->whereIn('state_type_id', ['01','03','05','07','13'])
                                                 ->whereIn('document_type_id', ['01','03','08'])
                                                 ->where('currency_type_id', 'PEN')
                                                 ->where('date_of_issue', $d_start)->sum('total');

            $document_total_usd = collect($documents)->whereIn('state_type_id', ['01','03','05','07','13'])
                                                 ->whereIn('document_type_id', ['01','03','08'])
                                                 ->where('currency_type_id', 'USD')
                                                 ->where('date_of_issue', $d_start)
                                                 ->map(function ($item, $key) {
                                                    return $item->total * $item->exchange_rate_sale;
                                                 })->sum();

            $document_total_note_credit_pen = collect($documents)->where('document_type_id', '07')
                                                            ->whereIn('state_type_id', ['01','03','05','07','13'])
                                                            ->where('currency_type_id', 'PEN')
                                                            ->where('date_of_issue', $d_start)
                                                            ->sum('total');

            $document_total_note_credit_usd = collect($documents)->where('document_type_id', '07')
                                                            ->whereIn('state_type_id', ['01','03','05','07','13'])
                                                            ->where('currency_type_id', 'USD')
                                                            ->where('date_of_issue', $d_start)
                                                            ->map(function ($item, $key) {
                                                                return $item->total * $item->exchange_rate_sale;
                                                            })->sum();


            $d_total = $document_total_pen + $document_total_usd;
            $d_total_note_credit = $document_total_note_credit_pen + $document_total_note_credit_usd;

            $document_total = round($d_total - $d_total_note_credit,2);

            $documents_array[$d_start->format('d').'d'] = $document_total;

            $total_array[$d_start->format('d').'d'] = round($sale_note_total + $document_total ,2);

            $d_start = $d_start->addDay();
        }

        return compact('sale_notes_array', 'documents_array', 'total_array');
    }

    private function getDocumentsByMonths($sale_notes, $documents, $month_start, $month_end)
    {
        $sale_notes_array = [];
        $documents_array = [];
        $total_array = [];
        $document_total = 0;
        $document_total_note_credit = 0;

        $m_start = (int) substr($month_start, 5, 2);
        $m_end = (int) substr($month_end, 5, 2);
//        dd($m_start);
        for ($m = $m_start; $m <= $m_end; $m++)
        {
            $m_format = str_pad($m, 2, '0', STR_PAD_LEFT);

            //SALE NOTE
            $sale_note_total_pen = 0;
            $sale_note_total_col_usd = [];
            $sale_note_total_usd = 0;

            $sale_note_total_pen = $sale_notes->where('currency_type_id', 'PEN')->filter(function ($row) use($m_format) {
                return $row->date_of_issue->format('m') === $m_format;
            })->sum('total');

            $sale_note_total_col_usd = $sale_notes->filter(function ($row) use($m_format) {
                return $row->date_of_issue->format('m') === $m_format;
            })->where('currency_type_id', 'USD');

            foreach ($sale_note_total_col_usd as $sn) {
                $sale_note_total_usd += $sn->total * $sn->exchange_rate_sale;
            }

            $sale_note_total = round($sale_note_total_pen + $sale_note_total_usd, 2);

            $sale_notes_array[$m_format.'m'] = $sale_note_total;


            //DOCUMENT
            $document_total_pen = 0;
            $document_total_col_usd = [];
            $document_total_usd = 0;
            $document_total_nc_col_usd = [];
            $document_total_note_credit_usd = 0;

            $document_total_pen = $documents->filter(function ($row) use($m_format) {
                return $row->date_of_issue->format('m') === $m_format;
            })->whereIn('state_type_id', ['01','03','05','07','13'])->where('currency_type_id', 'PEN')
            ->whereIn('document_type_id', ['01','03','08'])->sum('total');

            $document_total_col_usd = $documents->filter(function ($row) use($m_format) {
                return $row->date_of_issue->format('m') === $m_format;
            })->whereIn('state_type_id', ['01','03','05','07','13'])->where('currency_type_id', 'USD')->whereIn('document_type_id', ['01','03','08']);

            foreach ($document_total_col_usd as $doc) {
                $document_total_usd += $doc->total * $doc->exchange_rate_sale;
            }

            //NC
            $document_total_note_credit_pen = $documents->filter(function ($row) use($m_format) {
                return $row->date_of_issue->format('m') === $m_format;
            })->whereIn('state_type_id', ['01','03','05','07','13'])->where('document_type_id', '07')->where('currency_type_id', 'PEN')->sum('total');

            $document_total_nc_col_usd = $documents->filter(function ($row) use($m_format) {
                return $row->date_of_issue->format('m') === $m_format;
            })->whereIn('state_type_id', ['01','03','05','07','13'])->where('document_type_id', '07')->where('currency_type_id', 'USD');

            foreach ($document_total_nc_col_usd as $docnc) {
                $document_total_note_credit_usd += $docnc->total * $docnc->exchange_rate_sale;
            }

            $d_total = $document_total_pen + $document_total_usd;
            $d_total_nc = $document_total_note_credit_pen + $document_total_note_credit_usd;

            $document_total = $d_total - $d_total_nc;
            //DOCUMENT

            $documents_array[$m_format.'m'] = round($document_total, 2);

            $total_array[$m_format.'m'] = round($sale_note_total + $document_total, 2);
        }

        return compact('sale_notes_array', 'documents_array', 'total_array');
    }





    private function balance($establishment_id, $date_start, $date_end){

        $document = $this->get_document_totals($establishment_id, $date_start, $date_end);
        $sale_note = $this->get_sale_note_totals($establishment_id, $date_start, $date_end);
        $purchase = $this->get_purchase_totals($establishment_id, $date_start, $date_end);
        $expense = $this->get_expense_totals($establishment_id, $date_start, $date_end);

        $response_totals_document = $document['totals'];
        $response_totals_sale_note = $sale_note['totals'];
        $response_totals_purchase = $purchase['totals'];
        $response_totals_expense = $expense['totals'];

        // dd($response_totals_document, $response_totals_sale_note, $response_totals_purchase, $response_totals_expense);

        $total_document =  $response_totals_document['total'];
        $total_payment_document =  $response_totals_document['total_payment'];

        $total_sale_note =  $response_totals_sale_note['total'];
        $total_payment_sale_note =  $response_totals_sale_note['total_payment'];

        $total_purchase = $response_totals_purchase['total'];
        $total_payment_purchase = $response_totals_purchase['total_payment'];

        $total_expense = $response_totals_expense['total'];
        $total_payment_expense = $response_totals_expense['total_payment'];

        $all_totals = $total_document + $total_sale_note - $total_expense - $total_purchase;
        $all_totals_payment = $total_payment_document + $total_payment_sale_note - $total_payment_purchase - $total_payment_expense ;

        return [
            'totals' => [
                'total_document' => number_format($total_document,2),
                'total_payment_document' => number_format($total_payment_document,2),
                'total_sale_note' => number_format($total_sale_note,2),
                'total_payment_sale_note' => number_format($total_payment_sale_note,2),
                'total_purchase' => number_format($total_purchase,2),
                'total_payment_purchase' => number_format($total_payment_purchase,2),
                'total_expense' => number_format($total_expense,2),
                'total_payment_expense' => number_format($total_payment_expense,2),

                'all_totals' => number_format($all_totals,2),
                'all_totals_payment' => number_format($all_totals_payment,2),
            ],
            'graph' => [
                'labels' => ['Totales', 'Total pagos'],
                'datasets' => [
                    [
                        'label' => 'Grafico',
                        'data' => [round($all_totals,2), round($all_totals_payment,2)],
                        'backgroundColor' => [
                            'rgb(36, 71, 232, .1)',
                            'rgb(254, 0, 108, .1)',
                        ],
                        'borderColor' => [
                            'rgb(36, 71, 232)',
                            'rgb(254, 0, 108)',
                        ],
                    ]
                ],
            ]
        ];
    }

    public function getItems(){

        $items = Item::orderBy('description')->take(20)->get()->transform(function($row) {
            return [
                'id' => $row->id,
                'description' => ($row->internal_id) ? "{$row->internal_id} - {$row->description}" :$row->description,
            ];
        });

        return $items;

    }

    public function data_mobile($request)
    {
        $establishment_id = $request['establishment_id'];
        $period = $request['period'];
        $date_start = $request['date_start'];
        $date_end = $request['date_end'];
        $month_start = $request['month_start'];
        $month_end = $request['month_end'];

        $d_start = null;
        $d_end = null;

        /** @todo: Eliminar periodo, fechas y cambiar por

        $date_start = $request['date_start'];
        $date_end = $request['date_end'];
        \App\CoreFacturalo\Helpers\Functions\FunctionsHelper\FunctionsHelper::setDateInPeriod($request, $date_start, $date_end);
         */
        switch ($period) {
            case 'month':
                $d_start = Carbon::parse($month_start.'-01')->format('Y-m-d');
                $d_end = Carbon::parse($month_start.'-01')->endOfMonth()->format('Y-m-d');
                break;
            case 'between_months':
                $d_start = Carbon::parse($month_start.'-01')->format('Y-m-d');
                $d_end = Carbon::parse($month_end.'-01')->endOfMonth()->format('Y-m-d');
                break;
            case 'date':
                $d_start = $date_start;
                $d_end = $date_start;
                break;
            case 'between_dates':
                $d_start = $date_start;
                $d_end = $date_end;
                break;
        }

        return [
            'general' => $this->totals($establishment_id, $d_start, $d_end, $period, $month_start, $month_end),
        ];
    }
    

    /**
     * 
     * Método para acceder a los totales (método privado)
     * El gráfico no incluye pedidos
     * 
     * Usado en:
     * ReportController - App
     *
     * @param  int $establishment_id
     * @param  string $d_start
     * @param  string $d_end
     * @param  string $period
     * @param  string $month_start
     * @param  string $month_end
     * @return array
     */
    public function getGeneralTotals($establishment_id, $d_start, $d_end, $period, $month_start, $month_end)
    {
        $data = $this->totals($establishment_id, $d_start, $d_end, $period, $month_start, $month_end);

        $total_order_notes = $this->getTotalsOrderNote($establishment_id, $d_start, $d_end);
        
        $data['totals']['total_order_notes'] = $this->roundNumber($total_order_notes);
        $data['totals']['total'] = $this->roundNumber($total_order_notes + (float) $data['totals']['total']);

        return $data;
    }


}
