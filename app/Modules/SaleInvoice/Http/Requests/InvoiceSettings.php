<?php

namespace App\Modules\SaleInvoice\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class InvoiceSettings extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'default_payment_term_days' => ['required', 'integer', 'min:0', 'max:366'],
            'default_invoice_gross_counted' => ['required', 'boolean'],
            'invoice_registries' => ['required', 'array', 'max:100', 'invoice_registries_prefix'],
            'invoice_registries.*.id' => [
                'nullable',
                'distinct',
                Rule::exists('invoice_registries', 'id'),
            ],
            'invoice_registries.*.name' => ['required', 'string', 'max:255'],
            'invoice_registries.*.prefix' => [
                'present',
                'string',
                'max:10',
                'alpha_dash',
                'distinct',
            ],
            'invoice_registries.*.default' => ['required', 'boolean'],
            'invoice_registries.*.invoice_format_id' => [
                'required',
                Rule::exists('invoice_formats', 'id'),
            ],
            'invoice_registries.*.start_number' => [
                'present',
                'nullable',
            ],
            'one_default_registry' => ['numeric', Rule::in([1])],
        ];

        // Check if start number can be used
        if (is_array($this->input('invoice_registries'))) {
            foreach ($this->input('invoice_registries') as $key => $register) {
                if (isset($register['invoice_format_id']) && isset($register['start_number'])) {
                    $registry_id = array_get($register, 'id', null);

                    $rules['invoice_registries.' . $key . '.start_number'] = [
                        'registries_start_number:' . $registry_id . ',' .
                        $register['invoice_format_id'],
                        'required',
                        'integer',
                        'min:1',
                    ];
                }
            }
        }

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function all($keys = null)
    {
        $data = parent::all();
        // make sure data will be trimmed before validation
        array_walk_recursive($data, function (&$input, $key) {
            $input = trimInput($input);
        });
        $data['one_default_registry'] =
            collect($this->input('invoice_registries.*.default'))->sum();

        return $data;
    }
}
