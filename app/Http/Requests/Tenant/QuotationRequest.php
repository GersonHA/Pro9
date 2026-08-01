<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class QuotationRequest extends FormRequest
{
	public function authorize()
	{
		return true;
	}

	public function rules()
	{
		return [
			'customer_id' => [
				'required',
			],
			'exchange_rate_sale' => [
				'required',
				'numeric'
			],
			'currency_type_id' => [
				'required',
			],
			'date_of_issue' => [
				'required',
			],
			// FIX cotizaciones 2026-07-26: evita cotización vacía que rompe PDF
			// cuando el frontend omite items por bug. Patrón copiado de
			// PurchaseRequest::rules(). Sin min:1 para no romper frontend.
			'items' => [
				'required',
				'array',
			],
			'custom_fields_data' => [
				'nullable',
				'array',
			],
		];
	}
}
