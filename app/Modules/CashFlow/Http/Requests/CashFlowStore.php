<?php

namespace App\Modules\CashFlow\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;
use App\Models\Db\CashFlow as CashFlowModel;

class CashFlowStore extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'document_type' => [
                'in:receipt,invoice',
            ],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'direction' => ['required', Rule::in(CashFlowModel::directions())],
            'flow_date' => ['required', 'date'],
            'cashless' => ['required', 'boolean'],
        ];

        // if document_type is given this should be id of record in table
        if ($this->input('document_id') && in_array($this->input('document_type'), ['receipt', 'invoice'])) {
            $exists = Rule::exists(str_plural($this->input('document_type')), 'id')
                ->where('company_id', auth()->user()->getSelectedCompanyId());

            if ($this->input('document_type') == 'invoice') {
                $exists = $exists->whereNull('deleted_at');
            }

            $rules['document_id'][] = $exists;
        }

        return $rules;
    }
}
