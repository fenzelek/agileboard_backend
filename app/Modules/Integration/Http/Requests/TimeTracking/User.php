<?php

namespace App\Modules\Integration\Http\Requests\TimeTracking;

use App\Filters\TimeTrackingUserFilter;
use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class User extends Request
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        $current_company_id = $this->currentUser()->getSelectedCompanyId();

        $rules = [
            'id' => [
                'nullable',
                'integer',
            ],
            'integration_id' => [
                'nullable',
                'integer',
                Rule::exists('integrations', 'id')
                    ->where('company_id', $current_company_id),
            ],
            'user_id' => [
                'nullable',
            ],
            'external_user_id' => [
                'nullable',
                'string',
            ],
            'external_user_email' => [
                'nullable',
                'string',
            ],
            'external_user_name' => [
                'nullable',
                'string',
            ],
        ];

        if ($this->notSetToCustomEmpty('user_id')) {
            $rules['user_id'] = array_merge($rules['user_id'], [
                'integer',
                Rule::exists('user_company', 'user_id')
                    ->where('company_id', $current_company_id),
            ]);
        }

        return $rules;
    }

    protected function notSetToCustomEmpty($field)
    {
        return $this->input($field) &&
            $this->input($field) != TimeTrackingUserFilter::EMPTY;
    }
}
