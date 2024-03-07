<?php

namespace App\Modules\Company\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Db\CompanyModule;
use App\Models\Db\Payment;
use Illuminate\Validation\Rule;

class PaymentConfimBuy extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'subscription' => ['required', Rule::in([0, false, '0'])],
        ];

        try {
            $history = $this->payment->transaction->companyModulesHistory()->first();
            $company_module = CompanyModule::where('company_id', auth()->user()->getSelectedCompanyId())
                ->whereNotNull('package_id')->first();
            if ($history->package_id || ($company_module->subscription_id && $company_module->subscription->active)) {
                $rules = [
                    'subscription' => ['required', 'boolean'],
                ];
            }
        } catch (\Exception $e) {
        }

        if ($this->input('type') == Payment::TYPE_CARD) {
            if ($this->input('token')) {
                $rules['token'] = ['required'];
            } else {
                $rules['card_exp_month'] = ['required', 'numeric', 'digits:2'];
                $rules['card_exp_year'] = ['required', 'numeric', 'digits:4'];
                $rules['card_cvv'] = ['required', 'numeric', 'digits:3'];
                $rules['card_number'] = ['required', 'min:12', 'max:19'];
            }
        }

        return $rules;
    }
}
