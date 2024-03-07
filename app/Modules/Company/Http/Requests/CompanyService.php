<?php

namespace App\Modules\Company\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Db\CompanyService as CompanyServiceModel;
use Illuminate\Validation\Rule;

class CompanyService extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'name' => ['required', 'max:255'],
            'type' => [
                'required',
                Rule::in([
                    CompanyServiceModel::TYPE_SERVICE,
                    CompanyServiceModel::TYPE_ARTICLE,
                ]),
            ],
            'vat_rate_id' => ['required', Rule::exists('vat_rates', 'id')->where('is_visible', 1)],
            'pkwiu' => [
                'present',
                'string',
                'max:20',
            ],
            'print_on_invoice' => [
                'required',
                'boolean',
            ],
            'description' => [
                'present',
                'string',
                'max:1000',
            ],
            'price_net' => [
                'present',
            ],
            'price_gross' => [
                'present',
            ],
            'service_unit_id' => [
                'required',
                'integer',
                Rule::exists('service_units', 'id'),
            ],
        ];

        if ($this->input('price_net') !== null) {
            $rules['price_net'][] = 'numeric';
            $rules['price_net'][] = 'min:0.01';
            $rules['price_net'][] = 'max:9999999.99';
            $rules['price_gross'][] = 'required';
        } else {
            $rules['price_gross'][] = 'nullable';
        }

        if ($this->input('price_gross') !== null) {
            $rules['price_gross'][] = 'numeric';
            $rules['price_gross'][] = 'min:0.01';
            $rules['price_gross'][] = 'max:9999999.99';
            $rules['price_net'][] = 'required';
        } else {
            $rules['price_net'][] = 'nullable';
        }

        return $rules;
    }
}
