<?php
namespace App\Http\Controllers\Tenant;

use App\Exports\CashProductExport;
use App\Exports\CashPaymentExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CashRequest;
use App\Http\Resources\Tenant\CashCollection;
use App\Http\Resources\Tenant\CashResource;
use App\Models\Tenant\Cash;
use App\Models\Tenant\CashDocument;
use App\Models\Tenant\Company;
use App\Models\Tenant\DocumentItem;
use App\Models\Tenant\PaymentMethodType;
use App\Models\Tenant\PurchaseItem;
use App\Models\Tenant\SaleNoteItem;
use App\Models\Tenant\SaleNote;
use App\Models\Tenant\Document;
use App\Models\Tenant\User;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Traits\FinanceTrait;
use Modules\Pos\Models\CashTransaction;
use App\Models\Tenant\CashDocumentCredit;
use Modules\Finance\Models\Income;
use Modules\Finance\Models\GlobalPayment;
use App\Models\Tenant\DocumentPayment;
use App\Models\Tenant\SaleNotePayment;
use App\CoreFacturalo\Helpers\Template\ReportHelper;
use Carbon\Carbon;
use Modules\Restaurant\Models\RestaurantTable;
use App\Models\Tenant\CashDocumentPayment;


/**
 * Class CashController
 *
 * @package App\Http\Controllers\Tenant
 * @mixin  Controller
 */
class CashController extends Controller
{

    use FinanceTrait;

    public function index()
    {
        return view('tenant.cash.index');
    }

    public function columns()
    {
        return [
            'income' => 'Ingresos',
            'user' => 'Vendedor',
        ];
    }

    public function records(Request $request)
    {
        $query = Cash::withOut(['cash_documents'])
                ->whereTypeUser();
                
        if ($request->column == 'user') {   
            $query->whereHas('user', function($q) use($request) {
                $q->where('name', 'like', "%{$request->value}%");
            });
        } else {
            $query->where($request->column, 'like', "%{$request->value}%");
        }
    
        $query->orderBy('date_opening', 'DESC')
                ->orderBy('time_opening','desc');

        return new CashCollection($query->paginate(config('tenant.items_per_page')));
    }

    public function create()
    {
        return view('tenant.items.form');
    }

    public function tables()
    {
        $user = auth()->user();
        $type = $user->type;
        $users = array();

        switch($type)
        {
            case 'admin':
                $users = User::where('type', 'seller')->get();
                $users->push($user);
                break;
            case 'seller':
                $users = User::where('id', $user->id)->get();
                break;
        }

        return compact('users', 'user');
    }

    public function opening_cash()
    {

        $cash = User::resolveActiveCashForUser(auth()->user());

        return compact('cash');
    }
    
    /**
     * 
     * Usado en:
     * CashController - App
     *
     * @param  int $user_id
     * @return array
     */
    public function opening_cash_check($user_id)
    {
        $cash = Cash::where([['user_id', $user_id],['state', true]])->first();
        return compact('cash');
    }

    
    /**
     * 
     * Usado en:
     * CashController - App
     *
     * @param  int $id
     * @return array
     */
    public function record($id)
    {
        $record = new CashResource(Cash::findOrFail($id));

        return $record;
    }
    
    
    /**
     * 
     * Usado en:
     * CashController - App
     *
     * @param  CashRequest $request
     * @return array
     */
    public function store(CashRequest $request) {

        $id = $request->input('id');
        $user_id = $request->input('user_id');
        $cashId = 0;

        if($user_id == 0){
            $request->merge(['user_id' => auth()->user()->id]);
        }

        DB::connection('tenant')->transaction(function () use ($id, $request,&$cashId) {

            $cash = Cash::firstOrNew(['id' => $id]);
            $cash->fill($request->all());

            if(!$id){
                $cash->date_opening = date('Y-m-d');
                $cash->time_opening = date('H:i:s');

                // Auto-referencia (formato pro8): si el cajero deja el campo
                // "Número de Referencia" en blanco al aperturar, generamos
                // "ddmyyy-Nombre" usando la fecha del día y el primer nombre
                // del usuario logueado. Aterriza p.ej. "050326-Carlos".
                // Si el admin proporciona valor manual, respetamos el suyo.
                if (empty($cash->reference_number)) {
                    $date_format = date('dmy'); // Genera: 050326
                    $first_name = explode(' ', auth()->user()->name)[0]; // Saca "Carlos"
                    $cash->reference_number = "{$date_format}-{$first_name}";
                }
            }

            $cash->save();
            $cashId = $cash->id;
            $this->createCashTransaction($cash, $request);

        });


        return [
            'success' => true,
            'message' => ($id)?'Caja actualizada con éxito':'Caja aperturada con éxito',
            'data' => [
                'cash_id' => $cashId
            ]
        ];

    }


    public function createCashTransaction($cash, $request){

        $this->destroyCashTransaction($cash);

        $data = [
            'date' => date('Y-m-d'),
            'description' => 'Saldo inicial',
            'payment_method_type_id' => '01',
            'payment' => $request->beginning_balance,
            'payment_destination_id' => 'cash',
            'user_id' => $request->user_id,
        ];

        $cash_transaction = $cash->cash_transaction()->create($data);

        $this->createGlobalPaymentTransaction($cash_transaction, $data);

    }

    
    /**
     * 
     * Usado en:
     * CashController - App
     *
     * @param  int $id
     * @return array
     */
    public function close($id) {
        // cash_documents grandes (HENAVI: 6500+) pueden OOM-ear con 128MB
        // default de Apache mod_php. Subir a 512M SOLO para este endpoint.
        @ini_set('memory_limit', '512M');

        $cash = Cash::findOrFail($id);

        if(!$cash){
            return [
                'success' => false,
                'message' => 'Caja no encontrada',
            ];
        }

        $notAvailable = RestaurantTable::where('status', 'notavailable')->exists();

        if ($notAvailable) {
            return [
                'success' => false,
                'message' => 'No se puede cerrar caja , existe mesas abiertas.',
            ];
        }

        // dd($cash->cash_documents);

        $cash->date_closed = date('Y-m-d');
        $cash->time_closed = date('H:i:s');

        $final_balance = 0;
        $income = 0;

        // ============================================================
        // Refactor perf (plan #33): 5 SQL agregadas reemplazan O(N·K) loop.
        // Equivalencia exacta con el foreach anterior:
        //   1. sale_notes (rama principal HENAVI)        — pagos amarrados a esta caja
        //   2. documents SIN notas                       — pagos directos
        //   3. documents CON notas (NC/ND)               — pre-existente: total NULL en notes → 0
        //   4. expenses (state=05)                       — descuenta payment
        //   5. purchases (canceled + state aceptado)     — descuenta total
        //   6. quotations (applyQuotationToCash guard)   — suma getTransformTotal
        // ============================================================

        // 1. Sale notes
        $row = DB::connection('tenant')->selectOne(
            "SELECT COALESCE(SUM(
                CASE WHEN sn.currency_type_id = 'PEN' THEN sp.payment
                     ELSE sp.payment * COALESCE(sn.exchange_rate_sale, 1) END
            ), 0) AS balance
            FROM cash_documents cd
            INNER JOIN sale_notes sn ON sn.id = cd.sale_note_id
            INNER JOIN sale_note_payments sp ON sp.sale_note_id = sn.id
            INNER JOIN cash_document_payments cdp
                ON cdp.sale_note_payment_id = sp.id AND cdp.cash_id = ?
            WHERE cd.cash_id = ?
              AND sn.state_type_id IN ('01','03','05','07','13')",
            [$cash->id, $cash->id]
        );
        $final_balance += (float) $row->balance;

        // 2. Documents sin notas
        $row = DB::connection('tenant')->selectOne(
            "SELECT COALESCE(SUM(
                CASE WHEN d.currency_type_id = 'PEN' THEN dp.payment
                     ELSE dp.payment * COALESCE(d.exchange_rate_sale, 1) END
            ), 0) AS balance
            FROM cash_documents cd
            INNER JOIN documents d ON d.id = cd.document_id
            INNER JOIN document_payments dp ON dp.document_id = d.id
            INNER JOIN cash_document_payments cdp
                ON cdp.document_payment_id = dp.id AND cdp.cash_id = ?
            WHERE cd.cash_id = ?
              AND d.state_type_id IN ('01','03','05','07','13')
              AND NOT EXISTS (SELECT 1 FROM notes nt WHERE nt.affected_document_id = d.id)",
            [$cash->id, $cash->id]
        );
        $final_balance += (float) $row->balance;

        // 3. Notas que afectan docs en esta caja.
        //    Equivalencia con el foreach anterior: las columnas total/currency_type_id/
        //    exchange_rate_sale NO existen en la tabla `notes`; en el código viejo eso
        //    devolvía null y la suma resultaba 0 (ADR-0005: NC no toca caja). El agregado
        //    SQL reproduce esa semántica multiplicando por NULL → COALESCE → 0.
        $row = DB::connection('tenant')->selectOne(
            "SELECT COALESCE(SUM(
                CASE WHEN nt.note_type = 'debit' THEN 1 ELSE -1 END
            ), 0) AS balance
            FROM cash_documents cd
            INNER JOIN notes nt ON nt.affected_document_id = cd.document_id
            WHERE cd.cash_id = ?",
            [$cash->id]
        );
        $final_balance += (float) $row->balance * 0; // notes.* (total/currency/exchange) son NULL → 0

        // 4. Expenses (state_type_id = '05')
        $row = DB::connection('tenant')->selectOne(
            "SELECT COALESCE(SUM(
                CASE WHEN e.currency_type_id = 'PEN' THEN ep.payment
                     ELSE ep.payment * COALESCE(e.exchange_rate_sale, 1) END
            ), 0) AS balance
            FROM cash_documents cd
            INNER JOIN expense_payments ep ON ep.id = cd.expense_payment_id
            INNER JOIN expenses e ON e.id = ep.expense_id
            WHERE cd.cash_id = ?
              AND e.state_type_id = '05'",
            [$cash->id]
        );
        $final_balance -= (float) $row->balance;

        // 5. Purchases canceladas en estado aceptado (pre-existente: solo total_canceled=1)
        $row = DB::connection('tenant')->selectOne(
            "SELECT COALESCE(SUM(
                CASE WHEN p.currency_type_id = 'PEN' THEN p.total
                     ELSE p.total * COALESCE(p.exchange_rate_sale, 1) END
            ), 0) AS balance
            FROM cash_documents cd
            INNER JOIN purchases p ON p.id = cd.purchase_id
            WHERE cd.cash_id = ?
              AND p.state_type_id IN ('01','03','05','07','13')
              AND p.total_canceled = 1",
            [$cash->id]
        );
        $final_balance -= (float) $row->balance;

        // 6. Quotations con applyQuotationToCash() (hasStateTypeAccepted && hasPayments && !changed)
        $row = DB::connection('tenant')->selectOne(
            "SELECT COALESCE(SUM(
                CASE WHEN q.currency_type_id = 'PEN' THEN q.total
                     ELSE q.total * COALESCE(q.exchange_rate_sale, 1) END
            ), 0) AS balance
            FROM cash_documents cd
            INNER JOIN quotations q ON q.id = cd.quotation_id
            WHERE cd.cash_id = ?
              AND q.state_type_id IN ('01','03','05','07','13')
              AND q.changed = 0
              AND EXISTS (SELECT 1 FROM quotation_payments qp WHERE qp.quotation_id = q.id)",
            [$cash->id]
        );
        $final_balance += (float) $row->balance;

        $incomes=Income::where('user_id', $cash->user_id)->whereTypeUser();
        $incomes=$incomes->whereBetween('date_of_issue',[$cash->date_opening,$cash->date_closed]);
        $incomes=$incomes->whereBetween('time_of_issue',[$cash->time_opening,$cash->time_closed]);
        $incomes=$incomes->get();

        if (isset($incomes[0])) {
            foreach ($incomes as $income) {
                if (in_array($income->state_type_id, ['01','03','05','07','13'])) {
                    $final_balance += ($income->currency_type_id == 'PEN')
                        ? $income->total
                        : ($income->total * $income->exchange_rate_sale);
                }
            }
        }


        $cash->final_balance = round($final_balance + $cash->beginning_balance, 2);
        $cash->income = round($final_balance, 2);
        $cash->state = false;
        $cash->save();

        return [
            'success' => true,
            'message' => 'Caja cerrada con éxito',
        ];

    }

    /**
     * 
     * Usado en:
     * CashController - App
     * 
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function cash_document(Request $request)
    {
        $cash = User::resolveActiveCashForUser(auth()->user());

        if (!$cash) {
            abort(404, 'No hay caja abierta para el usuario actual.');
        }

        $isDocument = $request->document_id !== null;
        $documentModel = $isDocument ? Document::class : SaleNote::class;
        $documentField = $isDocument ? 'document_id' : 'sale_note_id';
        $paymentConditionField = $isDocument ? 'payment_condition_id' : 'payment_method_type_id';
        $creditCondition = $isDocument ? '02' : '09';

        $document = $documentModel::findOrFail((int) $request->$documentField);

        $isCredit = $document->$paymentConditionField === $creditCondition;
        // Evitar duplicicdad si ya existe

        $cashDocumentCredit = $isCredit ? CashDocumentCredit::updateOrCreate([
            'cash_id' => $cash->id,
            $documentField => $document->id,
        ]) : null;
        
        // NOTA: Se esta colocando dentro de los eventos de los modelos para poder registrarlo en caja
        // Gracias al updateOrCreate la información que primero se creo dentro de evento del modelo, no duplicara la información sino solo
        // lo actualiza
        $cashDocument = $cash->cash_documents()->updateOrCreate([
            'document_id' => $request->document_id,
            'sale_note_id' => $request->sale_note_id,
            'quotation_id' => $request->quotation_id,
        ]);
        
        $document->payments->each(function($payment) use($cash,$isDocument,$cashDocument){
            // Plan #B (Responsable per-row): pasar el usuario que ejecuta el
            // movimiento de caja (registra la venta/pago) para que el reporte
            // Pos pueda mostrar "Responsable" por fila en lugar del dueño.
            $this->syncCashDocumentPayment(
                $cash->id, $payment->id, $isDocument, optional($cashDocument)->id,
                null, auth()->user()->id
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Venta con éxito',
        ]);
    }

    
    /**
     * 
     * Usado en:
     * CashController - App
     *
     * @param  int $id
     * @return array
     */
    public function destroy($id)
    {

        $data = DB::connection('tenant')->transaction(function () use ($id) {

            $cash = Cash::findOrFail($id);

            if($cash->global_destination()->where('payment_type', '!=', CashTransaction::class)->count() > 0){
                return [
                    'success' => false,
                    'message' => 'No puede eliminar la caja, tiene transacciones relacionadas'
                ];
            }

            $this->destroyCashTransaction($cash);
            $cash->delete();

            return [
                'success' => true,
                'message' => 'Caja eliminada con éxito'
            ];

        });

        return $data;

    }


    public function destroyCashTransaction($cash){

        $ini_cash_transaction = $cash->cash_transaction;

        if($ini_cash_transaction){
            CashTransaction::find($ini_cash_transaction->id)->delete();
        }

    }


    public function report($cash) {
        

        $cash = Cash::query()->findOrFail($cash);
        $company = Company::query()->first();

        $methods_payment = collect(PaymentMethodType::all())->transform(function($row){
            return (object)[
                'id' => $row->id,
                'name' => $row->description,
                'sum' => 0
            ];
        });

        set_time_limit(0);

        $pdf = PDF::loadView('tenant.cash.report_pdf', compact("cash", "company", "methods_payment"));

        $filename = "Reporte_POS - {$cash->user->name} - {$cash->date_opening} {$cash->time_opening}";

        return $pdf->stream($filename.'.pdf');
    }

    public function report_general()
    {
        $cashes = Cash::select('id')->whereDate('date_opening', date('Y-m-d'))->pluck('id');
        $cash_documents =  CashDocument::whereIn('cash_id', $cashes)->get();
        // dd($cash_documents);

        $company = Company::first();
        set_time_limit(0);

        $pdf = PDF::loadView('tenant.cash.report_general_pdf', compact("cash_documents", "company"));
        $filename = "Reporte_POS";
        return $pdf->download($filename.'.pdf');

    }
    

    /**
     * 
     * Usado en:
     * CashController - App
     *
     * @param  int $id
     * @param  bool $is_garage
     * @return mixed
     */
    public function report_products($id, $is_garage = false)
    {

        $data = $this->getDataReport($id, $is_garage);
        $pdf = PDF::loadView('tenant.cash.report_product_pdf', $data);
        $filename = "Reporte_POS_PRODUCTOS - {$data['cash']->user->name} - {$data['cash']->date_opening} {$data['cash']->time_opening}";

        return $pdf->stream($filename.'.pdf');

    }

    public function report_products_excel($id)
    {
        // FIX #35 OOM prod: cash 5 + 19,614 SaleNoteItems con JSON column
        // 'item' hidrata ~2.7GB en mi test local. Prod container es 128M
        // → FatalError. Subir solo este método, sin tocar php.ini global.
        @ini_set('memory_limit', '512M');
        @set_time_limit(0);

        $data = $this->getDataReport($id);
        $filename = "Reporte_POS_PRODUCTOS - {$data['cash']->user->name} - {$data['cash']->date_opening} {$data['cash']->time_opening}";

        $cashProductExport = new CashProductExport();
        $cashProductExport
            ->documents($data['documents'])
            ->company($data['company'])
            ->cash($data['cash']);
        // return $cashProductExport->view();
        return $cashProductExport
                ->download($filename.'.xlsx');

    }


    public function getDataReport($id, $is_garage = false)
    {

        $cash = Cash::findOrFail($id);
        $company = Company::first();
        $cash_documents =  CashDocument::getDocumentIdsReport($cash);
        ReportHelper::setBoolIsGarage($is_garage);

        $source = DocumentItem::with('document')->whereIn('document_id', $cash_documents)->get();

        $documents = collect($source)->transform(function(DocumentItem $row){

            $item = $row->item;
            $data = $row->toArray();
            $data['item'] =$item;
            $data['unit_value']=$data['unit_value']??0;
            $data['sub_total'] =$data['unit_value'] * $data['quantity'];
            $data['number_full'] = $row->document->number_full;
            $data['description'] = $row->item->description;
            $data['unit_type_id'] = $this->getUnitTypeId($row);
            $data['record_type'] = 'document_item';

            $data['total'] = $row->total;
            $data['item_id'] = $row->item_id;

            /*
            $data['total'] = $row->document->total;
            $data['item_id'] =$row->relation_item->id;
            */

            return $data;
        });

        $documents = $documents->merge($this->getSaleNotesReportProducts($cash));

        $documents = $documents->merge($this->getPurchasesReportProducts($cash));

        return compact("cash", "company", "documents", 'is_garage');

    }



    public function getSaleNotesReportProducts($cash)
    {

        $cd_sale_notes =  CashDocument::getSaleNoteIdsReport($cash);

        $sale_note_items = SaleNoteItem::with('sale_note')->whereIn('sale_note_id', $cd_sale_notes)->get();

        return collect($sale_note_items)->transform(function(SaleNoteItem $row){
            $item = $row->item;
            $data = $row->toArray();
            $data['item'] =$item;
            $data['unit_value']=$data['unit_value']??0;
            $data['sub_total'] =$data['unit_value'] * $data['quantity'];
            $data['number_full'] = $row->sale_note->number_full;
            $data['description'] = $row->item->description;
            $data['unit_type_id'] = $this->getUnitTypeId($row);
            $data['record_type'] = 'sale_note_item';
            
            $data['total'] = $row->total;
            $data['item_id'] = $row->item_id;

            /*
            $data['total'] = $row->sale_note->total;
            $data['item_id'] =$row->relation_item->id;
            */

            return $data;
        });

    }


    public function getPurchasesReportProducts($cash)
    {

        $cd_purchases =  CashDocument::getPurchaseIdsReport($cash);

        $purchase_items = PurchaseItem::with('purchase')->whereIn('purchase_id', $cd_purchases)->get();

        return collect($purchase_items)->transform(function(PurchaseItem $row){

            $item = $row->item;
            $data = $row->toArray();
            $data['item'] =$item;
            $data['unit_value']=$data['unit_value']??0;
            $data['sub_total'] =$data['unit_value'] * $data['quantity'];
            $data['number_full'] = $row->purchase->number_full;
            $data['description'] = $row->item->description;
            $data['unit_type_id'] = $this->getUnitTypeId($row);
            $data['record_type'] = 'purchase_item';

            $data['total'] = $row->total;
            $data['item_id'] = $row->item_id;

            /*
            $data['total'] = $row->purchase->total;
            $data['item_id'] =$row->purchase->id;
            */

            return $data;
        });

    }
    
    
    /**
     * @param  array $row
     * @return string
     */
    private function getUnitTypeId($row)
    {
        return $row->item->unit_type_id ?? null;
    }


    public function report_cash_excel($cash_id)
    {
        // FIX #35 OOM prod (preventivo): cash 5 + 6,533 cash_documents con
        // eager loading post-6d7a48e9 mide 397MB peak en mi test. Prod es
        // 128M → podría OOMEar. Subir solo este método.
        @ini_set('memory_limit', '512M');
        set_time_limit(0);
        $data = [];
        /** @var Cash $cash */
        $cash = Cash::findOrFail($cash_id);
        $establishment = $cash->user->establishment;
        $status_type_id = self::getStateTypeId();
        $final_balance = 0;
        $cash_income = 0;
        $credit = 0;
        $cash_egress = 0;
        $cash_final_balance = 0;
        // Eager load de TODAS las relaciones que el foreach (L735+) accede por fila.
        // Antes: 6,533 cash_documents con N+1 masivo (~80K queries: sale_note +
        // payments + customer + document_type + expense→expense_type/supplier,
        // etc.). Cada caja tardaba ~30-60 min wall-clock.
        // Ahora: 1 query batched por relación → ~80-100 queries totales, ~2-5s.
        $cash_documents = $cash->cash_documents()->with([
            'sale_note.customer:id,name,number',
            'sale_note.payments',
            // 'document.customer' NO existe como relation en Document — es un
            // accessor JSON que decodifica la columna snapshot. NO genera query.
            'document.payments',
            'document.document_type:id,description',
            'technical_service.customer:id,name,number',
            'technical_service.payments',
            'purchase.supplier:id,name,number',
            'purchase.payments',
            'purchase.purchase_payments',
            'purchase.document_type:id,description',
            'expense_payment',
            'expense_payment.expense.expense_type:id,description',
            'expense_payment.expense.supplier:id,name,number',
            // 'quotation.customer' tampoco es relation (es accessor JSON).
            'quotation.payments',
        ])->get();
        $all_documents = [];
        $type_payment = ['01'];

        // Metodos de pago de no credito
        $methods_payment_credit = PaymentMethodType::NonCredit()->get()->transform(function ($row) {
            return $row->id;
        })->toArray();

        $methods_payment = collect(PaymentMethodType::where('id','01')->get())->transform(function ($row) {
            return (object)[
                'id'   => $row->id,
                'name' => $row->description,
                'sum'  => 0,
            ];
        });
        $company = Company::first();

        $data['cash'] = $cash;
        $data['cash_user_name'] = $cash->user->name;
        $data['cash_date_opening'] = $cash->date_opening;
        $data['cash_state'] = $cash->state;
        $data['cash_date_closed'] = $cash->date_closed;
        $data['cash_time_closed'] = $cash->time_closed;
        $data['cash_time_opening'] = $cash->time_opening;
        $data['cash_documents'] = $cash_documents;
        $data['cash_documents_total'] = (int)$cash_documents->count();

        $data['company_name'] = $company->name;
        $data['company_number'] = $company->number;
        $data['company'] = $company;

        $data['status_type_id'] = $status_type_id;

        $data['establishment'] = $establishment;
        $data['establishment_address'] = $establishment->address;
        $data['establishment_department_description'] = $establishment->department->description;
        $data['establishment_district_description'] = $establishment->district->description;
        $data['nota_venta'] = 0;
        $nota_credito = 0;
        $nota_debito = 0;
        /************************/

        foreach ($cash_documents as $cash_document) {
            $type_transaction = null;
            $document_type_description = null;
            $number = null;
            $date_of_issue = null;
            $customer_name = null;
            $customer_number = null;
            $currency_type_id = null;
            $temp = [];
            $notes = [];
            $usado = '';
            
            /** Documentos de Tipo Nota de venta */
            if ($cash_document->sale_note) {
                $sale_note = $cash_document->sale_note;
                if (in_array($sale_note->state_type_id, $status_type_id)) {
                        $record_total = 0;
                        $total = self::CalculeTotalOfCurency(
                            $sale_note->total,
                            $sale_note->currency_type_id,
                            $sale_note->exchange_rate_sale
                        );
                        $cash_income += $total;
                        $final_balance += $total;
                        if (count($sale_note->payments) > 0) {
                            $pays = $sale_note->payments;
                            foreach ($methods_payment as $record) {
                                $record_total = $pays->where('payment_method_type_id', $record->id)->sum('payment');
                                $record->sum = ($record->sum + $record_total);

                                if (!empty($record_total)) {
                                    if(self::getStringPaymentMethod($record->id) == "Efectivo"){
                                        $temp = [
                                            'type_transaction'          => 'Venta',
                                            'document_type_description' => 'NOTA DE VENTA',
                                            'number'                    => $sale_note->number_full,
                                            'date_of_issue'             => $sale_note->date_of_issue->format('Y-m-d'),
                                            'date_sort'                 => $sale_note->date_of_issue,
                                            'customer_name'             => $sale_note->customer->name,
                                            'customer_number'           => $sale_note->customer->number,
                                            'total'                     => ((!in_array($sale_note->state_type_id, $status_type_id)) ? 0
                                                : $sale_note->total),
                                            'currency_type_id'          => $sale_note->currency_type_id,
                                            'usado'                     => $usado." ".__LINE__,
                                            'tipo'                      => 'sale_note',
                                            'total_payments'            => (!in_array($sale_note->state_type_id, $status_type_id)) ? 0 : $sale_note->payments->sum('payment'),
                                        ];

                                    }
                                }
                            }
                        }
                    
                }
              
            } 
            /** Documentos de Tipo Document */
            
            else if ($cash_document->document) {
                $record_total = 0;
                $document = $cash_document->document;
                $payment_condition_id = $document->payment_condition_id;
                $pays = $document->payments;
                $pagado = 0;
                if (in_array($document->state_type_id, $status_type_id)) {
                    if ($payment_condition_id == '01') {
                            $total = self::CalculeTotalOfCurency(
                                $document->total,
                                $document->currency_type_id,
                                $document->exchange_rate_sale
                            );
                            // $usado .= '<br>Tomado para income<br>';
                            $cash_income += $total;
                            $final_balance += $total;
                            if (count($pays) > 0) {
                                // $usado .= '<br>Se usan los pagos<br>';
                                foreach ($methods_payment as $record) {
                                    $record_total = $pays
                                        ->where('payment_method_type_id', $record->id)
                                        ->whereIn('document.state_type_id', $status_type_id)
                                        ->sum('payment');
                                    $record->sum = ($record->sum + $record_total);
                                    if (!empty($record_total)) {
                                        // $usado .= self::getStringPaymentMethod($record->id).'<br>Se usan los pagos Tipo - 1er IF -  '.$record->id.'<br>';
                                        if(self::getStringPaymentMethod($record->id) == "Efectivo"){
                                            $temp = [
                                                'type_transaction'          => 'Venta',
                                                'document_type_description' => $document->document_type->description,
                                                'number'                    => $document->number_full,
                                                'date_of_issue'             => $document->date_of_issue->format('Y-m-d'),
                                                'date_sort'                 => $document->date_of_issue,
                                                'customer_name'             => $document->customer->name,
                                                'customer_number'           => $document->customer->number,
                                                'id_pagos'                  => $document->payment_condition_id,
                                                'total'                     => (!in_array($document->state_type_id, $status_type_id)) ? 0
                                                    : $document->total,
                                                'currency_type_id'          => $document->currency_type_id,
                                                'usado'                     => $usado." ".__LINE__,
                            
                                                'tipo' => 'document',
                                                'total_payments'            => (!in_array($document->state_type_id, $status_type_id)) ? 0 : $document->payments->sum('payment'),
                                            ];

                                        }
                                    }
                                }
                        }
                    }
                }
                
                /* Notas de credito o debito*/
                $notes = $document->getNotes();
            } 
            /** Documentos de Tipo Servicio tecnico */
            else if ($cash_document->technical_service) {
                
                    $usado = '<br>Se usan para cash<br>';
                    $technical_service = $cash_document->technical_service;
                    $cash_income += $technical_service->cost;
                    $final_balance += $technical_service->cost;
                        if (count($technical_service->payments) > 0) {
                            $usado = '<br>Se usan los pagos<br>';
                            $pays = $technical_service->payments;
                            foreach ($methods_payment as $record) {
                                $record->sum = ($record->sum + $pays->where('payment_method_type_id', $record->id)->sum('payment'));
                                if (!empty($record_total)) {
                                    $usado .= self::getStringPaymentMethod($record->id).'<br>Se usan los pagos Tipo '.$record->id.'<br>';
                                }
                            }
                        }
                    
                $temp = [
                    'type_transaction'          => 'Venta',
                    'document_type_description' => 'Servicio técnico',
                    'number'                    => 'TS-'.$technical_service->id,//$value->document->number_full,
                    'date_of_issue'             => $technical_service->date_of_issue->format('Y-m-d'),
                    'date_sort'                 => $technical_service->date_of_issue,
                    'customer_name'             => $technical_service->customer->name,
                    'customer_number'           => $technical_service->customer->number,
                    'total'                     => $technical_service->cost,
                    'currency_type_id'          => 'PEN',
                    'usado'                     => $usado." ".__LINE__,
                    'tipo'                      => 'technical_service',
                    'total_payments'            => $technical_service->payments->sum('payment'),
                ];
            }
            
            /** Documentos de Tipo compras */
            else if ($cash_document->purchase) {

                /**
                 * @var \App\Models\Tenant\CashDocument $cash_document
                 * @var \App\Models\Tenant\Purchase $purchase
                 * @var \Illuminate\Database\Eloquent\Collection $payments
                 */
                $purchase = $cash_document->purchase;

                if (in_array($purchase->state_type_id, $status_type_id)) {
                    
                    $payments = $purchase->purchase_payments;
                    /* dd($payments[0]['payment_method_type_id']); */
                    $record_total = 0;
                    // $total = self::CalculeTotalOfCurency($purchase->total, $purchase->currency_type_id, $purchase->exchange_rate_sale);
                    // $cash_egress += $total;
                    // $final_balance -= $total;
                    if (count($payments) > 0) {
                        $pays = $payments;
                        foreach ($methods_payment as $record) {
                            $record_total = $pays->where('payment_method_type_id', '01')->sum('payment');
                            // $record->sum = ($record->sum - $record_total);
                            $cash_egress += $record_total;
                            $final_balance -= $record_total;

                            if(!empty($record_total)){
                                if(self::getStringPaymentMethod($record->id) == "Efectivo"){
                                    $temp = [
                                        'type_transaction'          => 'Compra',
                                        'document_type_description' => $purchase->document_type->description,
                                        'number'                    => $purchase->number_full,
                                        'date_of_issue'             => $purchase->date_of_issue->format('Y-m-d'),
                                        'date_sort'                 => $purchase->date_of_issue,
                                        'customer_name'             => $purchase->supplier->name,
                                        'customer_number'           => $purchase->supplier->number,
                                        'total'                     => ((!in_array($purchase->state_type_id, $status_type_id)) ? 0 : -$purchase->total),
                                        'currency_type_id'          => $purchase->currency_type_id,
                                        'usado'                     => $usado." ".__LINE__,
                                        'tipo'                      => 'purchase',
                                        'total_payments'            => (!in_array($purchase->state_type_id, $status_type_id)) ? 0 : $purchase->payments->sum('payment'),                        
                                    ];
                                }
                            }
                        }
                    }
                }
                
            }

            /** Documentos de Tipo Gastos */
            elseif ($cash_document->expense_payment) 
            {
                $expense_payment = $cash_document->expense_payment;
                $total_expense_payment = 0;

                if ($expense_payment->expense->state_type_id == '05') 
                {
                    $total_expense_payment = self::CalculeTotalOfCurency(
                        $expense_payment->payment,
                        $expense_payment->expense->currency_type_id,
                        $expense_payment->expense->exchange_rate_sale
                    );

                    $cash_egress += $total_expense_payment;
                    $final_balance -= $total_expense_payment;
                    // $cash_egress += $total;
                    // $final_balance -= $total;
                }

                $order_number = 9;

                $temp = [
                    'type_transaction'          => 'Gasto diverso',
                    'document_type_description' => $expense_payment->expense->expense_type->description,
                    'number'                    => $expense_payment->expense->number,
                    'date_of_issue'             => $expense_payment->expense->date_of_issue->format('Y-m-d'),
                    'date_sort'                 => $expense_payment->expense->date_of_issue,
                    'customer_name'             => $expense_payment->expense->supplier->name,
                    'customer_number'           => $expense_payment->expense->supplier->number,
                    'total'                     => -$total_expense_payment,
                    // 'total'                     => -$expense_payment->payment,
                    'currency_type_id'          => $expense_payment->expense->currency_type_id,
                    'usado'                     => $usado." ".__LINE__,

                    'tipo' => 'expense_payment',
                    'total_payments'            => $total_expense_payment,
                    // 'total_payments'            => -$expense_payment->payment,
                    'type_transaction_prefix'   => 'egress',
                    'order_number_key'          => $order_number.'_'.$expense_payment->expense->created_at->format('YmdHis'),
                    'document_items_description' => $this->getDocumentItemsDescription($expense_payment->expense),

                ];
            }

            /** Cotizaciones */
            else if ($cash_document->quotation) 
            {
                $quotation = $cash_document->quotation;

                // validar si cumple condiciones para usar registro en reporte
                if($quotation->applyQuotationToCash())
                {
                        if (in_array($quotation->state_type_id, $status_type_id)) 
                        {
                            $record_total = 0;
        
                            $total = self::CalculeTotalOfCurency(
                                $quotation->total,
                                $quotation->currency_type_id,
                                $quotation->exchange_rate_sale
                            );
        
                            $cash_income += $total;
                            $final_balance += $total;
        
                            if (count($quotation->payments) > 0) 
                            {
                                $pays = $quotation->payments;
                                foreach ($methods_payment as $record) {
                                    $record_total = $pays->where('payment_method_type_id', $record->id)->sum('payment');
                                    $record->sum = ($record->sum + $record_total);

                                    if(!empty($record_total)){
                                        if(self::getStringPaymentMethod($record->id) == "Efectivo"){
                                            $temp = [
                                                'type_transaction'          => 'Venta (Pago a cuenta)',
                                                'document_type_description' => 'COTIZACION  ',
                                                'number'                    => $quotation->number_full,
                                                'date_of_issue'             => $quotation->date_of_issue->format('Y-m-d'),
                                                'date_sort'                 => $quotation->date_of_issue,
                                                'customer_name'             => $quotation->customer->name,
                                                'customer_number'           => $quotation->customer->number,
                                                'total'                     => ((!in_array($quotation->state_type_id, $status_type_id)) ? 0 : $quotation->total),
                                                'currency_type_id'          => $quotation->currency_type_id,
                                                'usado'                     => $usado." ".__LINE__,
                                                'tipo'                      => 'quotation',
                                                'total_payments'            => (!in_array($quotation->state_type_id, $status_type_id)) ? 0 : $record_total,
                                            ];
                                        }
                                    }
                                }
                            }
                    }
    
                    

                }
                /** Cotizaciones */

            }

            

            if (!empty($temp)) {
                $temp['usado'] = isset($temp['usado']) ? $temp['usado'] : '--';
                $temp['total_string'] = self::FormatNumber($temp['total']);
                $temp['total_payments'] = self::FormatNumber($temp['total_payments']);
                $all_documents[] = $temp;
            }

            /** Notas de credito o debito */
            // if ($notes !== null) {
            //     foreach ($notes as $note) {
            //         $usado = 'Tomado para ';
            //         /** @var \App\Models\Tenant\Note $note */
            //         $sum = $note->isDebit();
            //         $type = ($note->isDebit()) ? 'Nota de debito' : 'Nota de crédito';
            //         $document = $note->getDocument();
            //         if (in_array($document->state_type_id, $status_type_id)) {
            //             $record_total = $document->getTotal();
            //             /** Si es credito resta */
            //             if ($sum) {
            //                 $usado .= 'Nota de debito';
            //                 $nota_debito += $record_total;
            //                 $final_balance += $record_total;
            //                 $usado .= "Id de documento {$document->id} - Nota de Debito /* $record_total * /<br>";
            //             } else {
            //                 $usado .= 'Nota de credito';
            //                 $nota_credito += $record_total;
            //                 $final_balance -= $record_total;
            //                 $usado .= "Id de documento {$document->id} - Nota de Credito /* $record_total * /<br>";
            //             }
            //             $temp = [
            //                 'type_transaction'          => $type,
            //                 'document_type_description' => $document->document_type->description,
            //                 'number'                    => $document->number_full,
            //                 'date_of_issue'             => $document->date_of_issue->format('Y-m-d'),
            //                 'date_sort'                 => $document->date_of_issue,
            //                 'customer_name'             => $document->customer->name,
            //                 'customer_number'           => $document->customer->number,
            //                 'total'                     => (!in_array($document->state_type_id, $status_type_id)) ? 0
            //                     : $document->total,
            //                 'currency_type_id'          => $document->currency_type_id,
            //                 'usado'                     => $usado.' '.__LINE__,
            //                 'tipo'                      => 'document',
            //                 'total_payments'            => (!in_array($document->state_type_id, $status_type_id)) ? 0
            //                 : $document->total,
            //             ];

            //             $temp['usado'] = isset($temp['usado']) ? $temp['usado'] : '--';
            //             $temp['total_string'] = self::FormatNumber($temp['total']);
            //             $all_documents[] = $temp;
            //         }

            //     }
            // }

        }

        // finanzas ingresos
        $id_income=$cash->user_id;
        $incomes=Income::where('user_id', $id_income)->whereTypeUser();
        $date_closed = Carbon::now()->format('Y-m-d');
        $time_closed = Carbon::now()->format('H:m:s');
        if($cash->date_closed){
            $incomes=$incomes->whereBetween('date_of_issue',[$cash->date_opening,$cash->date_closed]);
            $incomes=$incomes->whereBetween('time_of_issue',[$cash->time_opening,$cash->time_closed]);
        }else{
            $incomes=$incomes->whereBetween('date_of_issue',[$cash->date_opening,$date_closed]);
            $incomes=$incomes->whereBetween('time_of_issue',[$cash->time_opening,$time_closed]);
        }

        // Eager load de payments + payment_method_type + income_type para evitar
        // N+1 en el foreach de abajo (por cada income accedía a payment_method_type).
        $incomes=$incomes->with(['payments.payment_method_type:id,description', 'income_type:id,description'])->get();
        
        if (isset($incomes[0])) {

            $data['cash_documents_total'] = (int)$incomes->count();
            /* dd(isset($incomes[0])); */
            foreach ($incomes as $income) {
                
                $usado = '';                
                if( $income->payments[0]['payment_method_type']['id'] == "01"){
                    if (in_array($income->state_type_id, $status_type_id)){
                        $payments=$income->payments;
                            $record_total = 0;
        
                            $total = self::CalculeTotalOfCurency(
                                $income->total,
                                $income->currency_type_id,
                                $income->exchange_rate_sale
                            );
        
                            $cash_income += $total;
                            $final_balance += $total;

                            if (count($income->payments) > 0) 
                            {
                                $pays = $income->payments;
                                foreach ($methods_payment as $record) {
                                    $record_total = $pays->where('payment_method_type_id', $record->id)->sum('payment');
                                    $record->sum = ($record->sum + $record_total);
                                }
                            }

                            $temp = [
                                'type_transaction'          => 'Ingresos (finanzas)',
                                'document_type_description' => $income->income_type->description,
                                'number'                    => $income->number,
                                'date_of_issue'             => $income->date_of_issue->format('Y-m-d'),
                                'date_sort'                 => $income->date_of_issue,
                                'customer_name'             => $income->customer,
                                'customer_number'           => '-',
                                'total'                     => ((!in_array($income->state_type_id, $status_type_id)) ? 0 : $income->total),
                                'currency_type_id'          => $income->currency_type_id,
                                'usado'                     => $usado." ".__LINE__,
                                'tipo'                      => 'finance',
                                'total_payments'            => (!in_array($income->state_type_id, $status_type_id)) ? 0 : $income->payments->sum('payment'),
                                'document_items_description' => $this->getDocumentItemsDescription($income),
                            ];
                    }
                } else {
                    $temp = [];
                }
            
                /* dd((!in_array($income->state_type_id, $status_type_id)) ? 0 : $income->payments->sum('payment')); */                
                
                if (!empty($temp)) {
                    $temp['usado'] = isset($temp['usado']) ? $temp['usado'] : '--';
                    $temp['total_string'] = self::FormatNumber($temp['total']);
                    $temp['total_payments'] = self::FormatNumber($temp['total_payments']);
                    $all_documents[] = $temp;
                }
            }
        }

        

//        $all_documents = collect($all_documents)->sortBy('date_sort')->all();
        /************************/
        /************************/
        $data['all_documents'] = $all_documents;
        $temp = [];
        
        foreach ($methods_payment as $index => $item) {
            $temp[] = [
                'iteracion' => $index + 1,
                'name'      => $item->name,
                'sum'       => self::FormatNumber($item->sum),
            ];
        }

        $data['nota_credito'] = $nota_credito;
        $data['nota_debito'] = $nota_debito;
        $data['methods_payment'] = $temp;
        $data['credit'] = self::FormatNumber($credit);
        $data['cash_beginning_balance'] = self::FormatNumber($cash->beginning_balance);
        $cash_final_balance = $final_balance + $cash->beginning_balance;
        $data['cash_egress'] = self::FormatNumber($cash_egress);
        $data['cash_final_balance'] = self::FormatNumber($cash_final_balance);

        $data['cash_income'] = self::FormatNumber($cash_income);

        //$cash_income = ($final_balance > 0) ? ($cash_final_balance - $cash->beginning_balance) : 0;
        /* return $data; */
        /* dd($data); */
        $filename = "Reporte_POS_EFECTIVO - {$cash->user->name} - {$cash->date_opening} {$cash->time_opening}";

        $cashPaymentExport = new CashPaymentExport();
        $cashPaymentExport
            ->data($data);
        // return $cashProductExport->view();
        return $cashPaymentExport
                ->download($filename.'.xlsx');

    }
    

    /**
     * 
     * Descripcion de los items
     *
     * @param  $record
     * @return string
     */
    private function getDocumentItemsDescription($record)
    {
        $data = $record->items->pluck('description')->toArray();
        $full_description = "";

        foreach ($data as $value) 
        {
            $full_description .= "- {$value}<br>";
        }

        return $full_description;
    }


    public static function CalculeTotalOfCurency(
        $total = 0,
        $currency_type_id = 'PEN',
        $exchange_rate_sale = 1
    ) {
        if ($currency_type_id !== 'PEN') {
            $total = $total * $exchange_rate_sale;
        }
        return $total;
    }

    public static function getStateTypeId(){
        return [
            '01', //Registrado
            '03', // Enviado
            '05', // Aceptado
            '07', // Observado
            // '09', // Rechazado
            // '11', // Anulado
            '13' // Por anular
        ];
    }

    public static function FormatNumber($number = 0, $decimal = 2, $decimal_separador = '.', $miles_separador = '') {
        return number_format($number, $decimal, $decimal_separador, $miles_separador);
    }

    public static function getStringPaymentMethod($payment_id) {
        // FIX #34 perf: antes hacía PaymentMethodType::find($payment_id) por
        // cada llamada — en cash 5 eso eran ~4,200 queries. Cache estática:
        // solo ~10 payment_method_types existen, se cargan una vez por request.
        static $cache = null;
        if ($cache === null) {
            $cache = PaymentMethodType::pluck('description', 'id')->toArray();
        }
        return $cache[$payment_id] ?? '';
    }

    /**
     * Panel de Auditoría Forense — lista de cajas auditables.
     *
     * Port de pro8 L1213-1251 (commit 19e8fc86). Devuelve todas las cajas
     * (abiertas y cerradas) con descripción enriquecida para el selector
     * "Seleccione Caja a Auditar" del frontend cash/index.vue.
     *
     * Frontend consumía esta ruta desde #216 y devolvía HTTP 500 porque
     * el método nunca se portó a Pro9. Rutas declaradas pero métodos ausentes.
     */
    public function getBoxesForAudit()
    {
        try {
            $cajas = Cash::with('user')->orderBy('id', 'desc')->get();

            $formatted = $cajas->map(function ($caja) {
                $estado = $caja->state ? '🟢 ABIERTA' : '🔴 CERRADA';

                $fecha = 'Sin fecha';
                if ($caja->date_opening) {
                    $fecha = Carbon::parse($caja->date_opening)->format('d/m/Y');
                } elseif ($caja->created_at) {
                    $fecha = $caja->created_at->format('d/m/Y');
                }

                $usuario = $caja->user ? $caja->user->name : 'Administrador';
                $referencia = $caja->reference_number ? $caja->reference_number : $caja->id;

                return [
                    'id' => $caja->id,
                    'description' => "Caja {$referencia} | {$usuario} | {$fecha} ({$estado})",
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formatted,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error Interno: ' . $e->getMessage() . ' en la línea ' . $e->getLine(),
            ], 500);
        }
    }

    /**
     * Panel de Auditoría Forense — transacciones de una caja específica.
     *
     * Port de pro8 L1254-1330 (commit 19e8fc86). Lee GlobalPayments con
     * destination_type=Cash y destination_id=$id. Deduces tipo de pago
     * (SaleNote/Document/CashTransaction=Saldo Inicial). Ordena por fecha.
     *
     * Diferencias vs pro8: este método en Pro9 reemplaza los FQN por
     * aliases ya importados (Cash en lugar de \App\Models\Tenant\Cash,
     * GlobalPayment en lugar de \Modules\Finance\Models\GlobalPayment,
     * etc.) para mantener consistencia con el resto del archivo.
     */
    public function getAuditTransactions($id)
    {
        try {
            // FIX OOM 128MB (Gerson, 2026-07-24): caja 5 HENAVI tiene 6,700
            // GlobalPayments y memory_limit=128M del contenedor PROD.
            // Mismo patrón ya usado en report_products_excel y
            // report_cash_excel (commits e47261a0, f435da33, fe762b65).
            @ini_set('memory_limit', '512M');
            @set_time_limit(0);

            $cash = Cash::findOrFail($id);

            // FIX OOM (parte 2): pluck mapa id=>description una sola vez (1
            // query) en lugar de ->with('payment.payment_method_type') que
            // cargaba 6,700 instancias de PaymentMethodType en memoria.
            // payment_method_types solo tiene ~15 filas en BD.
            $pmt_desc = PaymentMethodType::pluck('description', 'id')->toArray();

            // FIX OOM (parte 4): pluck persons (clientes/proveedores) para
            // resolver nombres sin eager-load. persons tiene ~100 filas en
            // HENAVI; cargar 5K Customer instances por cada SaleNote era el
            // segundo OOM (502MB pico en debug).
            $persons_name = \App\Models\Tenant\Person::pluck('name', 'id')->toArray();
            $persons_number = \App\Models\Tenant\Person::pluck('number', 'id')->toArray();

            // FIX OOM (parte 5): chunk(500) mantiene la memoria acotada.
            // Cada lote se libera antes del siguiente; sin esto, aún con
            // split-by-type cargábamos 6,700 GPs + 6,700 models en RAM
            // (~342MB pico en debug). Con chunk bajamos a <100MB.
            $processChunk = function ($gps) use (&$transactions, $cash, $pmt_desc, $persons_name, $persons_number) {
                foreach ($gps as $gp) {
                    $payment = $gp->payment;
                    if (!$payment) continue;

                    $number_full = 'S/N';
                    $customer_name = 'Clientes - Varios';
                    $customer_number = '';

                    $model = null;
                    if ($gp->payment_type === SaleNotePayment::class) {
                        $model = $payment->sale_note;
                    } elseif ($gp->payment_type === DocumentPayment::class) {
                        $model = $payment->document;
                    } elseif ($gp->payment_type === CashTransaction::class) {
                        $number_full = 'SALDO INICIAL';
                    }

                    if ($model) {
                        $number_full = $model->number_full ?? ($model->series . '-' . $model->number);
                        if ($gp->payment_type === DocumentPayment::class) {
                            $cj = $model->customer;
                            $customer_name = is_object($cj) ? ($cj->name ?? 'Clientes - Varios') : 'Clientes - Varios';
                            $customer_number = is_object($cj) ? ($cj->number ?? '') : '';
                        } else {
                            $cid = $model->customer_id ?? null;
                            $customer_name = $cid ? ($persons_name[$cid] ?? 'Clientes - Varios') : 'Clientes - Varios';
                            $customer_number = $cid ? ($persons_number[$cid] ?? '') : '';
                        }
                    }

                    $monto = (float) $payment->payment;
                    $responsable = $gp->user->name ?? ($cash->user->name ?? 'Administrador');
                    $method = $pmt_desc[$payment->payment_method_type_id] ?? 'Efectivo';

                    if ($model && isset($model->date_of_issue) && isset($model->time_of_issue)) {
                        $date_format = Carbon::parse($model->date_of_issue)->format('Y-m-d');
                        $fecha_movimiento = Carbon::parse($date_format . ' ' . $model->time_of_issue);
                    } else {
                        $fecha_movimiento = $payment->created_at
                            ?? ($payment->date_of_payment ? Carbon::parse($payment->date_of_payment) : Carbon::now());
                    }

                    $transactions[] = [
                        'id' => 'gp-' . $gp->id,
                        'datetime' => $fecha_movimiento->format('Y-m-d H:i:s'),
                        'type' => ($monto < 0) ? 'Extorno / Anulación' : (($number_full === 'SALDO INICIAL') ? 'Apertura' : 'Venta'),
                        'responsable' => $responsable,
                        'document' => $number_full,
                        'customer_name' => $customer_name,
                        'customer_number' => $customer_number,
                        'method' => $method,
                        'amount' => $monto,
                        'date_sort' => $fecha_movimiento,
                    ];
                }
            };

            $transactions = [];
            // FIX OOM (parte 3): dividir por payment_type para evitar el
            // RelationNotFoundException de eager-load polymorphic
            // (CashTransaction no tiene relaciones sale_note/document, y
            // Document::customer es JSON accessor no relation).
            GlobalPayment::where('destination_id', $cash->id)
                ->where('destination_type', Cash::class)
                ->where('payment_type', SaleNotePayment::class)
                ->with(['payment.sale_note', 'user'])
                ->chunk(500, $processChunk);
            GlobalPayment::where('destination_id', $cash->id)
                ->where('destination_type', Cash::class)
                ->where('payment_type', DocumentPayment::class)
                ->with(['payment.document', 'user'])
                ->chunk(500, $processChunk);
            GlobalPayment::where('destination_id', $cash->id)
                ->where('destination_type', Cash::class)
                ->where('payment_type', CashTransaction::class)
                ->with('user')
                ->chunk(500, $processChunk);

            usort($transactions, function ($a, $b) {
                return $a['date_sort'] <=> $b['date_sort'];
            });

            $transactions = array_map(function ($item) {
                unset($item['date_sort']);
                return $item;
            }, $transactions);

            return response()->json(['success' => true, 'data' => $transactions]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage() . ' Línea: ' . $e->getLine()], 500);
        }
    }
}
