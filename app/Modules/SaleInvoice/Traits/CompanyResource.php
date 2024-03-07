<?php

namespace App\Modules\SaleInvoice\Traits;

use Illuminate\Validation\Rule;

trait CompanyResource
{
    /**
     * Add company resource validation rule .
     *
     * @return array
     */
    public function addCompanyResourceRule()
    {
        return [
            'id' => [
                'required',
                'numeric',
                Rule::exists('invoices', 'id')->where(
                    'company_id',
                    auth()->user()->getSelectedCompanyId()
                ),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function all($keys = null)
    {
        $data = parent::all();
        $data['id'] = $this->route('id');

        return $data;
    }
}
