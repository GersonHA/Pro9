<?php

namespace Modules\Dashboard\Traits;

use App\Models\Tenant\Document;
use App\Models\Tenant\DocumentPayment;
use App\Models\Tenant\SaleNote;
use App\Models\Tenant\SaleNotePayment;
use Carbon\Carbon;
use App\Models\Tenant\Person;
use App\Models\Tenant\Purchase;
use Modules\Expense\Models\Expense;
use Modules\Order\Models\OrderNote;
use Illuminate\Support\Facades\DB;


trait TotalsTrait
{

    public function get_purchase_totals($establishment_id, $date_start, $date_end)
    {
        $tenant = DB::connection('tenant');
        $where_state = ['01','03','05','07','13'];

        $builder = $tenant->table('purchases')
            ->whereIn('state_type_id', $where_state)
            ->where('establishment_id', $establishment_id);

        if ($date_start && $date_end) {
            $builder->whereBetween('date_of_issue', [$date_start, $date_end]);
        }

        $agg = (clone $builder)->selectRaw("
            SUM(CASE WHEN currency_type_id = 'PEN' THEN total ELSE 0 END) as pen_total,
            SUM(CASE WHEN currency_type_id = 'PEN' THEN total_perception ELSE 0 END) as pen_perception,
            SUM(CASE WHEN currency_type_id = 'USD' THEN total * exchange_rate_sale ELSE 0 END) as usd_total,
            SUM(CASE WHEN currency_type_id = 'USD' THEN total_perception * exchange_rate_sale ELSE 0 END) as usd_perception
        ")->first();

        // Pagos: 1 sola query agregada con JOIN
        $payment_query = $tenant->table('purchase_payments')
            ->join('purchases', 'purchases.id', '=', 'purchase_payments.purchase_id')
            ->where('purchases.establishment_id', $establishment_id)
            ->whereIn('purchases.state_type_id', $where_state);

        if ($date_start && $date_end) {
            $payment_query->whereBetween('purchases.date_of_issue', [$date_start, $date_end]);
        }

        $payments = (clone $payment_query)->selectRaw("
            SUM(CASE WHEN purchases.currency_type_id = 'PEN' THEN purchase_payments.payment ELSE 0 END) as pen_pay,
            SUM(CASE WHEN purchases.currency_type_id = 'USD' THEN purchase_payments.payment * purchases.exchange_rate_sale ELSE 0 END) as usd_pay
        ")->first();

        $purchases_total = (float) ($agg->pen_total ?? 0) + (float) ($agg->pen_perception ?? 0)
                         + (float) ($agg->usd_total ?? 0) + (float) ($agg->usd_perception ?? 0);

        $total_payment = (float) ($payments->pen_pay ?? 0) + (float) ($payments->usd_pay ?? 0);

        return [
            'totals' => [
                'total_payment' => round($total_payment, 2),
                'total' => round($purchases_total, 2),
            ]
        ];
    }


    public function get_expense_totals($establishment_id, $date_start, $date_end)
    {
        $tenant = DB::connection('tenant');

        $builder = $tenant->table('expenses')
            ->where('establishment_id', $establishment_id)
            ->where('state_type_id', '05');

        if ($date_start && $date_end) {
            $builder->whereBetween('date_of_issue', [$date_start, $date_end]);
        }

        $agg = (clone $builder)->selectRaw("
            SUM(CASE WHEN currency_type_id = 'PEN' THEN total ELSE 0 END) as pen,
            SUM(CASE WHEN currency_type_id = 'USD' THEN total * exchange_rate_sale ELSE 0 END) as usd_pen
        ")->first();

        $expenses_total = (float) ($agg->pen ?? 0) + (float) ($agg->usd_pen ?? 0);

        // Pagos: 1 sola query agregada con JOIN
        $payment_query = $tenant->table('expense_payments')
            ->join('expenses', 'expenses.id', '=', 'expense_payments.expense_id')
            ->where('expenses.establishment_id', $establishment_id)
            ->where('expenses.state_type_id', '05');

        if ($date_start && $date_end) {
            $payment_query->whereBetween('expenses.date_of_issue', [$date_start, $date_end]);
        }

        $payment = (clone $payment_query)->sum('expense_payments.payment');

        return [
            'totals' => [
                'total_payment' => round((float) $payment, 2),
                'total' => round($expenses_total, 2),
            ]
        ];
    }


    public function get_sale_note_totals($establishment_id, $date_start, $date_end)
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

        $total = (float) ($agg->pen ?? 0) + (float) ($agg->usd_pen ?? 0);

        // Pagos: 1 sola query agregada con JOIN
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

        $total_payment = (float) ($payments->pen_pay ?? 0) + (float) ($payments->usd_pay ?? 0);

        return [
            'totals' => [
                'total_payment' => round($total_payment, 2),
                'total' => round($total, 2),
            ]
        ];
    }


    public function get_document_totals($establishment_id, $date_start, $date_end)
    {
        $tenant = DB::connection('tenant');
        $where_state = ['01','03','05','07','13'];
        $where_type  = ['01','03','08'];

        $builder = $tenant->table('documents')
            ->where('establishment_id', $establishment_id);

        if ($date_start && $date_end) {
            $builder->whereBetween('date_of_issue', [$date_start, $date_end]);
        }

        $agg = (clone $builder)->selectRaw("
            SUM(CASE WHEN currency_type_id='PEN' AND state_type_id IN ('01','03','05','07','13') AND document_type_id IN ('01','03','08') THEN total ELSE 0 END) as pen_total,
            SUM(CASE WHEN currency_type_id='USD' AND state_type_id IN ('01','03','05','07','13') AND document_type_id IN ('01','03','08') THEN total*exchange_rate_sale ELSE 0 END) as usd_pen,
            SUM(CASE WHEN currency_type_id='PEN' AND state_type_id IN ('01','03','05','07','13') AND document_type_id='07' THEN total ELSE 0 END) as nc_pen,
            SUM(CASE WHEN currency_type_id='USD' AND state_type_id IN ('01','03','05','07','13') AND document_type_id='07' THEN total*exchange_rate_sale ELSE 0 END) as nc_usd_pen
        ")->first();

        $document_total = (float) ($agg->pen_total ?? 0) + (float) ($agg->usd_pen ?? 0);
        $document_total_note_credit = (float) ($agg->nc_pen ?? 0) + (float) ($agg->nc_usd_pen ?? 0);
        $document_total_final = round($document_total - $document_total_note_credit, 2);

        // Pagos: 1 sola query agregada con JOIN
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

        return [
            'totals' => [
                'total_payment' => round($document_total_payment, 2),
                'total' => $document_total_final,
            ]
        ];
    }


    /**
     *
     * Obtener suma total de pedidos
     *
     * @param  int $establishment_id
     * @param  string $date_start
     * @param  string $date_end
     * @return float
     */
    public function getTotalsOrderNote($establishment_id, $date_start, $date_end)
    {
        $order_notes = OrderNote::filterTotalsReport($establishment_id, $date_start, $date_end);

        return $order_notes->get()->sum(function($row){
            return $row->getTransformTotal();
        });
    }


    /**
     * Redondear número
     *
     * @param  float $value
     * @param  int $decimals
     * @return float
     */
    public function roundNumber($value, $decimals = 2)
    {
        return number_format($value, $decimals, ".", "");
    }

}
