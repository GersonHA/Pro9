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
            'search_by_second_name' => 'nullable|boolean',
            'search_by_model' => 'nullable|boolean',
            'search_by_extra_name' => 'nullable|boolean',
            'show_quotation_pos' => 'nullable|boolean',
            'lots_govern_stock' => 'nullable|boolean',


            // 'subtotal_account' => ['required'],
            // 'stock' => ['required', 'boolean']
        ];
    }
}
