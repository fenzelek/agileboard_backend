<?php

namespace App\Modules\Company\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Db\CountryVatinPrefix;
use App\Modules\Company\Traits\VatPayerRules;
use App\Rules\OneDefaultBankAccount;
use Illuminate\Validation\Rule;

class CompanyUpdate extends Request
{
    use VatPayerRules;

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
            'vat_payer' => ['required', 'boolean'],
            'country_vatin_prefix_id' => [
                'nullable',
                Rule::exists('country_vatin_prefixes', 'id'),
            ],
            'email' => ['present', 'email', 'max:63'],
            'logotype' => [
                'nullable',
                'image',
                'max:10240',
                'dimensions:min_width=50,min_height=50,max_width=1920,max_height=1080',
            ],
            'remove_logotype' => ['boolean'],
            'website' => ['present', 'max:255'],
            'phone' => ['present', 'max:15'],
            'main_address_street' => ['required', 'max:255'],
            'main_address_number' => ['required', 'max:31'],
            'main_address_zip_code' => ['required', 'max:7'],
            'main_address_city' => ['required', 'max:63'],
            'main_address_country' => [
                'required',
                'max:63',
                Rule::exists('country_vatin_prefixes', 'name'),
            ],
            'contact_address_street' => ['required', 'max:255'],
            'contact_address_number' => ['required', 'max:31'],
            'contact_address_zip_code' => ['required', 'max:7'],
            'contact_address_city' => ['required', 'max:63'],
            'contact_address_country' => [
                'required',
                'max:63',
                Rule::exists('country_vatin_prefixes', 'name'),
            ],
            'bank_accounts' => ['nullable', 'array', new OneDefaultBankAccount()],
            'bank_accounts.*.id' => [Rule::exists('bank_accounts')->where('company_id', $this->input('selected_company_id'))],
            'bank_accounts.*.bank_name' => ['required', 'max:63'],
            'bank_accounts.*.number' => ['required', 'max:63'],
            'bank_accounts.*.default' => ['required', 'boolean'],
        ];

        if ($this->file('logotype')) {
            $rules['remove_logotype'] = [Rule::in([0, false])];
        }
        // When prefix is not polish and not empty string don`t validate length
        $request_prefix = $this->country_vatin_prefix_id;
        if ($request_prefix != $pl_prefix_id && $request_prefix) {
            $rules['vatin'] = ['required', 'max:255'];
        }
        if ($this->main_address_country != 'Polska') {
            $rules['main_address_zip_code'] = ['required', 'max:255'];
        }
        if ($this->contact_address_country != 'Polska') {
            $rules['contact_address_zip_code'] = ['required', 'max:255'];
        }

        return array_merge($rules, $this->vatPayerCommonRules());
    }

    /**
     * @inheritdoc
     */
    public function all($keys = null)
    {
        $data = parent::all();

        // make sure data will be trimmed before validation
        foreach ($data as $key => $val) {
            $data[$key] = is_string($val) ? trim($val) : $val;
        }

        return $data;
    }
}
