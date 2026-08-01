<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfigurationRequest extends FormRequest
{
    public function authorize() {
        return true;
    }

    public function rules() {
        $id = $this->input('id');

        return [
            'send_auto' => ['required', 'boolean'],
            'cron' => ['required', 'boolean'],
            'decimal_quantity' => ['required', 'integer'],
            'enable_extended_search' => 'nullable|boolean',
            'allow_free_product' => 'nullable|boolean',
            'search_by_second_name' => 'nullable|boolean',
            'search_by_model' => 'nullable|boolean',
            'search_by_extra_name' => 'nullable|boolean',
            'show_quotation_pos' => 'nullable|boolean',
            'lots_govern_stock' => 'nullable|boolean',
            'allow_multiple_cpe_from_sale_note' => 'nullable|boolean',


            // 'subtotal_account' => ['required'],
            // 'stock' => ['required', 'boolean']
        ];
    }
}
