<?php

namespace App\Modules\Contractor\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Db\CountryVatinPrefix;
use App\Models\Other\ModuleType;
use App\Models\Other\ContractorAddressType;
use Illuminate\Validation\Rule;

class ContractorStoreUpdate extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $pl_prefix_id = CountryVatinPrefix::where('key', 'PL')->first()->id;

        $rules = [
            'name' => ['required', 'max:255'],
            'vatin' => ['max:15'],
            'country_vatin_prefix_id' => [
                'nullable',
                Rule::exists('country_vatin_prefixes', 'id'),
            ],
            'email' => ['present', 'email', 'max:63'],
            'phone' => ['present', 'max:15'],
            'bank_name' => ['present', 'max:63'],
            'bank_account_number' => ['present', 'max:63'],
            'main_address_street' => ['required', 'max:255'],
            'main_address_number' => ['required', 'max:31'],
            'main_address_zip_code' => ['present', 'max:7'],
            'main_address_city' => ['required', 'max:63'],
            'main_address_country' => [
                'required',
                'max:63',
                Rule::exists('country_vatin_prefixes', 'name'),
            ],
            'contact_address_street' => ['required', 'max:255'],
            'contact_address_number' => ['required', 'max:31'],
            'contact_address_zip_code' => ['present', 'max:7'],
            'contact_address_city' => ['required', 'max:63'],
            'contact_address_country' => [
                'required',
                'max:63',
                Rule::exists('country_vatin_prefixes', 'name'),
            ],
            'default_payment_term_days' => ['integer', 'min:0', 'max:366'],
            'default_payment_method_id' => [Rule::exists('payment_methods', 'id')],
        ];

        // When prefix is not polish and not empty string don`t validate length
        $request_prefix = $this->input('country_vatin_prefix_id');
        if ($request_prefix != $pl_prefix_id && $request_prefix) {
            $rules['vatin'] = ['required','max:255'];
        }
        if ($this->input('main_address_country') != 'Polska') {
            $rules['main_address_zip_code'] = ['present', 'max:255'];
        }
        if ($this->input('contact_address_country') != 'Polska') {
            $rules['contact_address_zip_code'] = ['present', 'max:255'];
        }

        return $rules + $this->requiredAddressesFields();
    }

    /**
     * Get all of the input and files for the request.
     *
     * @param  array|mixed  $keys
     *
     * @return array
     */
    public function all($keys = null)
    {
        $data = parent::all();

        // make sure data will be trimmed before validation
        array_walk_recursive($data, function (&$input, $key) {
            $input = trimInput($input);
        });
        if ($this->canAddExtraAddresses()) {
            // add extra data that should be validated
            $data['one_default_delivery_address'] = collect($this->input('addresses.*.default'))->sum();
        }

        return $data;
    }

    /**
     * Required addresses fields.
     *
     * @return array
     */
    protected function requiredAddressesFields()
    {
        $delivery_addresses = [
            'addresses' => ['required', 'array', 'check_polish_zip_code'],
            'addresses.*.type' => ['required', Rule::in([ContractorAddressType::DELIVERY])],
            'addresses.*.default' => ['required', 'boolean'],
            'addresses.*.street' => ['required', 'max:255'],
            'addresses.*.number' => ['required', 'max:63'],
            'addresses.*.zip_code' => ['present', 'max:255'],
            'addresses.*.city' => ['required', 'max:255'],
            'addresses.*.country' => [
                'required',
                'max:255',
                Rule::exists('country_vatin_prefixes', 'name'),
            ],
            'one_default_delivery_address' => [Rule::in([1])],
        ];
        if ($this->method() == Request::METHOD_PUT) {
            $delivery_addresses['addresses.*.id'] = [
                Rule::exists('contractor_addresses')->where('contractor_id', $this->route('contractor')),
            ];
        }

        return $this->canAddExtraAddresses() ? $delivery_addresses : [];
    }

    /**
     * Can add extra Addresses.
     *
     * @return bool
     */
    protected function canAddExtraAddresses()
    {
        return  auth()->user()->selectedCompany()->appSettings(ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED);
    }
}
