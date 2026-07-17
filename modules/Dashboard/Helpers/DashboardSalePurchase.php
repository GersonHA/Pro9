<?php

namespace Modules\Dashboard\Helpers;

use App\Models\Tenant\Document;
use App\Models\Tenant\SaleNote;
use App\Models\Tenant\Person;
use App\Models\Tenant\DocumentItem;
use App\Models\Tenant\SaleNoteItem;
use App\Models\Tenant\Purchase;
use App\Models\Tenant\Item;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Class DashboardSalePurchase
 *
 * @package Modules\Dashboard\Helpers
 */
class DashboardSalePurchase
{
    /**
     * @param $request
     *
     * @return array
     */
    public function data($request)
    {
        $establishment_id = $request['establishment_id'];
        $period = $request['period'];
        $date_start = $request['date_start'];
        $date_end = $request['date_end'];
        $month_start = $request['month_start'];
        $month_end = $request['month_end'];
        $enabled_move_item = $request['enabled_move_item'];
        $enabled_transaction_customer = $request['enabled_transaction_customer'];
        $no_take = isset($request['no_take']) ? $request['no_take'] : false; // evitar limite
        $page = isset($request['page']) ? $request['page'] : 1;

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

        return [
            'purchase' => $this->purchase_totals($establishment_id, $d_start, $d_end),
            'items_by_sales' => $this->items_by_sales($establishment_id, $d_start, $d_end, $enabled_move_item, $no_take, $page),
            'top_customers' => $this->top_customers($establishment_id, $d_start, $d_end, $enabled_transaction_customer),
        ];
    }

    private function top_customers($establishment_id, $d_start, $d_end, $enabled_transaction_customer){
        // Antes: ->get() materializaba 2,840 Eloquent models en RAM (HENAVI jun-26:
        //        22 docs + 2,818 SN) + Person::find() N+1 por cada cliente único.
        // Ahora: 2 SQL queries agregadas (UNION ALL documents+sale_notes GROUP BY customer_id
        //        + batch Person::whereIn). Memoria: ~36MB → <2MB; queries: miles → 2.

        $states = ['01','03','05','07','13'];

        // Query agregada: documents + sale_notes en uno con UNION ALL,
        // agrupado por customer_id. Mantiene paridad exacta con la lógica original:
        //  - total: ventas doc tipo 01/03/08 (con conversión PEN/USD) + NV (con conversión) - NC tipo 07 (sin conversión)
        //  - transaction_quantity: count ventas + count NV - count NC

        if ($d_start && $d_end) {
            $date_filter_doc = "establishment_id = ? AND state_type_id IN ('01','03','05','07','13') AND date_of_issue BETWEEN ? AND ?";
            $date_filter_sn  = "establishment_id = ? AND changed = 0 AND state_type_id IN ('01','03','05','07','13') AND date_of_issue BETWEEN ? AND ?";
            $doc_params = [$establishment_id, $d_start, $d_end];
            $sn_params  = [$establishment_id, $d_start, $d_end];
        } else {
            $date_filter_doc = "establishment_id = ? AND state_type_id IN ('01','03','05','07','13')";
            $date_filter_sn  = "establishment_id = ? AND changed = 0 AND state_type_id IN ('01','03','05','07','13')";
            $doc_params = [$establishment_id];
            $sn_params  = [$establishment_id];
        }

        $aggregated = DB::connection('tenant')->select("
            SELECT
                customer_id,
                SUM(CASE WHEN source = 'doc' AND document_type_id IN ('01','03','08') THEN
                    CASE WHEN currency_type_id = 'PEN' THEN total
                         WHEN currency_type_id = 'USD' THEN total * exchange_rate_sale
                    END
                ELSE 0 END) AS totals_docs_sale,
                SUM(CASE WHEN source = 'doc' AND document_type_id = '07' THEN total
                ELSE 0 END) AS total_credit_note,
                SUM(CASE WHEN source = 'sn' THEN
                    CASE WHEN currency_type_id = 'PEN' THEN total
                         WHEN currency_type_id = 'USD' THEN total * exchange_rate_sale
                    END
                ELSE 0 END) AS totals_sale_note,
                SUM(CASE WHEN source = 'doc' AND document_type_id IN ('01','03','08') THEN 1 ELSE 0 END) AS doc_sale_count,
                SUM(CASE WHEN source = 'doc' AND document_type_id = '07' THEN 1 ELSE 0 END) AS doc_credit_count,
                SUM(CASE WHEN source = 'sn' THEN 1 ELSE 0 END) AS sn_sale_count
            FROM (
                SELECT 'doc' AS source, customer_id, document_type_id, currency_type_id, exchange_rate_sale, total
                FROM documents WHERE $date_filter_doc
                UNION ALL
                SELECT 'sn' AS source, customer_id, NULL AS document_type_id, currency_type_id, exchange_rate_sale, total
                FROM sale_notes WHERE $date_filter_sn
            ) combined
            GROUP BY customer_id
            HAVING (totals_docs_sale + totals_sale_note - total_credit_note) > 0
        ", array_merge($doc_params, $sn_params));

        if (empty($aggregated)) {
            return collect([]);
        }

        // Batch lookup de Person (reemplaza N+1 de Person::find())
        $customer_ids = array_map(fn($r) => $r->customer_id, $aggregated);
        $persons = Person::where('type', 'customers')
            ->whereIn('id', $customer_ids)
            ->select('id', 'name', 'number')
            ->get()
            ->keyBy('id');

        $top_customers = collect([]);

        foreach ($aggregated as $row) {
            $cid = $row->customer_id;
            $transaction_quantity = (int)$row->doc_sale_count
                                   + (int)$row->sn_sale_count
                                   - (int)$row->doc_credit_count;

            $difference = (float)$row->totals_docs_sale
                        + (float)$row->totals_sale_note
                        - (float)$row->total_credit_note;

            $customer = $persons->get($cid);
            if (empty($customer)) {
                // Cuando es eliminado un cliente (pero existe en docs/NV), crea stub
                $customer = (object)[
                    'id' => $cid,
                    'name' => '',
                    'number' => '',
                ];
            }

            $top_customers->push([
                'total' => number_format($difference, 2, '.', ''),
                'name' => $customer->name,
                'number' => $customer->number,
                'transaction_quantity' => $transaction_quantity,
            ]);
        }

        $order_column = ($enabled_transaction_customer) ? 'transaction_quantity' : 'total';
        // total viene como string formateado, hay que ordenar por valor numérico
        if ($order_column === 'total') {
            $sorted = $top_customers->sortByDesc(fn($r) => (float)$r['total']);
        } else {
            $sorted = $top_customers->sortByDesc('transaction_quantity');
        }

        return $sorted->values()->take(10);
    }


    /**
     * @param $establishment_id
     * @param $d_start
     * @param $d_end
     *
     * @return array[]
     */
    private function purchase_totals($establishment_id, $d_start, $d_end)
    {
        /*
        $purchases = Purchase::without(['user', 'soap_type', 'state_type', 'document_type', 'currency_type', 'group', 'items', 'purchase_payments'])
            ->whereIn('state_type_id', ['01','03','05','07','13'])
            ->where('establishment_id', $establishment_id)
            ->select('id', 'state_type_id', 'establishment_id', 'currency_type_id', 'total', 'exchange_rate_sale', 'total_perception')
            ->get();

        $purchases_total = $purchases->where('currency_type_id', 'PEN')->sum('total');

        $purchase_dollr = $purchases->where('currency_type_id', 'USD');

        foreach ($purchase_dollr as $pr) {
            $purchases_total +=  $pr->total * $pr->exchange_rate_sale;
        }
        $purchases_total_perception = round($purchases->sum('total_perception'),2);
        */
         $purchases = Purchase::DasboardSalePurchase($establishment_id)->OnlyDateOfIssueByYear()->get();
         /*
         if(!empty($d_start)){
             $purchases->where('date_of_issue','>=',$d_start);
         }
         if(!empty($d_end)){
             $purchases->where('date_of_issue','<=',$d_end);
         }
         $purchases = $purchases->get();
         */

        $purchases_total = $purchases->sum('total_purchase');
        $purchases_total_perception = $purchases->sum('total_perception_purchase');

        $data_array = ['Ene', 'Feb','Mar', 'Abr','May', 'Jun','Jul', 'Ago','Sep', 'Oct', 'Nov', 'Dic'];

        $purchases_by_month = $purchases->groupBy(function($date) {
                                return Carbon::parse($date->date_of_issue)->format('m');
                            });


        return [
            'totals' => [
                'purchases_total_perception' => number_format($purchases_total_perception,2),
                'purchases_total' => number_format( round($purchases_total, 2),2),
                'total' => number_format($purchases_total + $purchases_total_perception,2),
                'date_of_issue'=>[
                    'start'=>$d_start,
                    'end'=>$d_end,
                ]
            ],
            'graph' => [
                'labels' => $data_array,
                'datasets' => [
                    [
                        'label' => 'Total percepciones',
                        'data' => $this->arrayPurchasesbyMonth($purchases_by_month, 'total_perception_purchase'),
                        'backgroundColor' => 'rgba(254, 0, 108, .1)',
                        'borderColor' => 'rgb(254, 0, 108)',
                        'borderWidth' => 2,
                        'fill' => true,
                        'lineTension' => 0.3,
                        'pointRadius' => 1
                    ],
                    [
                        'label' => 'Total compras',
                        'data' => $this->arrayPurchasesbyMonth($purchases_by_month, 'total_purchase'),
                        'backgroundColor' => 'rgba(36, 71, 232, .1)',
                        'borderColor' => 'rgb(36, 71, 232)',
                        'borderWidth' => 2,
                        'fill' => true,
                        'lineTension' => 0.3,
                        'pointRadius' => 1
                    ],
                    [
                        'label' => 'Total',
                        'data' => $data_array,
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



    /**
     * Total de compras (normalizado a PEN) filtrado por rango de fechas.
     *
     * @param int    $establishment_id
     * @param string $date_start  Y-m-d
     * @param string $date_end    Y-m-d
     *
     * @return array
     */
    public function purchasesTotalByRange($establishment_id, $date_start, $date_end)
    {
        $query = Purchase::DasboardSalePurchase($establishment_id);

        if ($date_start && $date_end) {
            $query->whereBetween('date_of_issue', [$date_start, $date_end]);
        }

        $purchases = $query->get();

        $purchases_total = round($purchases->sum('total_purchase'), 2);
        $purchases_total_perception = round($purchases->sum('total_perception_purchase'), 2);

        return [
            'purchases_total' => $purchases_total,
            'purchases_total_perception' => $purchases_total_perception,
            'total' => round($purchases_total + $purchases_total_perception, 2),
        ];
    }

    private function items_by_sales($establishment_id, $d_start, $d_end, $enabled_move_item, $no_take = false, $page)
    {
        // 1) document_items agregado por item_id
        //    Separa ventas normales (document_type_id IN '01','03','08') de NC.
        //    Conversión a PEN con exchange_rate_sale para USD.
        $doc_items_query = DB::connection('tenant')->table('document_items as di')
            ->selectRaw("
                di.item_id,
                SUM(CASE WHEN d.document_type_id IN ('01','03','08') THEN
                    CASE WHEN d.currency_type_id = 'PEN' THEN di.total
                         WHEN d.currency_type_id = 'USD' THEN di.total * d.exchange_rate_sale
                    END
                ELSE 0 END) as total_sale,
                SUM(CASE WHEN d.document_type_id NOT IN ('01','03','08') THEN
                    CASE WHEN d.currency_type_id = 'PEN' THEN di.total
                         WHEN d.currency_type_id = 'USD' THEN di.total * d.exchange_rate_sale
                    END
                ELSE 0 END) as total_credit,
                SUM(CASE WHEN d.document_type_id IN ('01','03','08') THEN di.quantity
                         ELSE -di.quantity END) as move_quantity
            ")
            ->join('documents as d', 'd.id', '=', 'di.document_id')
            ->where('d.establishment_id', $establishment_id)
            ->whereIn('d.state_type_id', ['01','03','05','07','13']);

        // 2) sale_note_items agregado por item_id (todo es venta)
        $sn_items_query = DB::connection('tenant')->table('sale_note_items as sni')
            ->selectRaw("
                sni.item_id,
                SUM(CASE WHEN sn.currency_type_id = 'PEN' THEN sni.total
                         WHEN sn.currency_type_id = 'USD' THEN sni.total * sn.exchange_rate_sale
                    END) as total_sale,
                0 as total_credit,
                SUM(sni.quantity) as move_quantity
            ")
            ->join('sale_notes as sn', 'sn.id', '=', 'sni.sale_note_id')
            ->where('sn.establishment_id', $establishment_id)
            ->where('sn.changed', false)
            ->whereIn('sn.state_type_id', ['01','03','05','07','13']);

        if ($d_start && $d_end) {
            $doc_items_query->whereBetween('d.date_of_issue', [$d_start, $d_end]);
            $sn_items_query->whereBetween('sn.date_of_issue', [$d_start, $d_end]);
        }

        $doc_items = $doc_items_query->groupBy('di.item_id')->get()->keyBy('item_id');
        $sn_items = $sn_items_query->groupBy('sni.item_id')->get()->keyBy('item_id');

        // 3) Merge por item_id
        $merged = [];
        foreach ($doc_items as $item_id => $row) {
            $merged[(int) $item_id] = [
                'item_id' => (int) $item_id,
                'total_sale' => (float) $row->total_sale,
                'total_credit' => (float) $row->total_credit,
                'move_quantity' => (float) $row->move_quantity,
            ];
        }
        foreach ($sn_items as $item_id => $row) {
            $id = (int) $item_id;
            if (isset($merged[$id])) {
                $merged[$id]['total_sale'] += (float) $row->total_sale;
                $merged[$id]['move_quantity'] += (float) $row->move_quantity;
            } else {
                $merged[$id] = [
                    'item_id' => $id,
                    'total_sale' => (float) $row->total_sale,
                    'total_credit' => 0,
                    'move_quantity' => (float) $row->move_quantity,
                ];
            }
        }

        // 4) Filtra > 0 y ordena descendente
        $items_by_sales = collect();
        foreach ($merged as $r) {
            $difference = $r['total_sale'] - $r['total_credit'];
            if ($difference > 0) {
                $items_by_sales->push([
                    'item_id' => $r['item_id'],
                    'total' => $difference,
                    'move_quantity' => $r['move_quantity'],
                ]);
            }
        }

        $order_column = ($enabled_move_item) ? 'move_quantity' : 'total';
        $items_by_sales = $items_by_sales->sortByDesc($order_column)->values();

        // 5) Batch lookup de items activos para descripción (solo si hay items)
        if ($items_by_sales->isEmpty()) {
            return $no_take
                ? new LengthAwarePaginator(collect(), 0, 10, $page)
                : collect([]);
        }

        $item_records = DB::connection('tenant')->table('items')
            ->select('id', 'description', 'internal_id')
            ->whereIn('id', $items_by_sales->pluck('item_id')->all())
            ->where('status', true)
            ->get()
            ->keyBy('id');

        // 6) Construye respuesta con el MISMO shape JSON que la versión previa
        $final = collect();
        foreach ($items_by_sales as $r) {
            $item = $item_records->get($r['item_id']);
            if (!$item) continue;
            $final->push([
                'total' => number_format($r['total'], 2, ".", ""),
                'description' => $item->description,
                'internal_id' => $item->internal_id,
                'move_quantity' => number_format($r['move_quantity'], 2, ".", ""),
            ]);
        }

        if ($no_take) {
            return new LengthAwarePaginator($final->forPage($page, 10), $final->count(), 10, $page);
        }
        return $final->take(10)->values();
    }

    /**
     * @param $purchases
     * @return array
     */
    private function arrayPurchasesbyMonth($purchases_by_month, $total){

        return [
            isset($purchases_by_month['01']) ? round($purchases_by_month['01']->sum($total), 2) : 0,
            isset($purchases_by_month['02']) ? round($purchases_by_month['02']->sum($total), 2) : 0,
            isset($purchases_by_month['03']) ? round($purchases_by_month['03']->sum($total), 2) : 0,
            isset($purchases_by_month['04']) ? round($purchases_by_month['04']->sum($total), 2) : 0,
            isset($purchases_by_month['05']) ? round($purchases_by_month['05']->sum($total), 2) : 0,
            isset($purchases_by_month['06']) ? round($purchases_by_month['06']->sum($total), 2) : 0,
            isset($purchases_by_month['07']) ? round($purchases_by_month['07']->sum($total), 2) : 0,
            isset($purchases_by_month['08']) ? round($purchases_by_month['08']->sum($total), 2) : 0,
            isset($purchases_by_month['09']) ? round($purchases_by_month['09']->sum($total), 2) : 0,
            isset($purchases_by_month['10']) ? round($purchases_by_month['10']->sum($total), 2) : 0,
            isset($purchases_by_month['11']) ? round($purchases_by_month['11']->sum($total), 2) : 0,
            isset($purchases_by_month['12']) ? round($purchases_by_month['12']->sum($total), 2) : 0
        ];

    }

    private function calculateTotalCurrency($currency_type_id, $exchange_rate_sale,  $total)
    {
        if($currency_type_id == 'USD')
        {
            return $total * $exchange_rate_sale;
        }
        else{
            return $total;
        }
    }




}
