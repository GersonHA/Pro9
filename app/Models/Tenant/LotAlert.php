<?php

namespace App\Models\Tenant;

class LotAlert extends ModelTenant
{
    protected $fillable = [
        'item_id',
        'lot_code',
        'date_of_due',
        'establishment_id',
        'message',
        'seen',
        'type',
        'threshold_days',
        'lots_data',
    ];

    protected $casts = [
        'seen'        => 'boolean',
        'date_of_due' => 'date',
        'lots_data'   => 'array',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function reads()
    {
        return $this->hasMany(LotAlertRead::class);
    }
}
