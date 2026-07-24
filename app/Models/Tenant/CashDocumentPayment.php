<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class CashDocumentPayment extends ModelTenant
{

    protected $fillable = [
        'cash_id',
        'document_payment_id',
        'sale_note_payment_id',
        'cash_document_id',
        'cash_document_credit_id',
        // Plan #B (Responsable per-row): usuario que ejecutó el movimiento de caja
        // (registró el pago, anuló, o editó el método). Nullable para no romper
        // pivotes históricos — fallback a $cash->user->name en el render.
        'user_id',
    ];

    public function cash()
    {
        return $this->belongsTo(Cash::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
