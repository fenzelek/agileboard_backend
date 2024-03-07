<?php

namespace App\Modules\Integration\Http\Requests\TimeTracking;

use App\Filters\TimeTrackingProjectFilter;
use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class Project extends Request
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
            'project_id' => [
                'nullable',
            ],
            'external_project_id' => [
                'nullable',
                'string',
            ],
            'external_project_name' => [
                'nullable',
                'string',
            ],
        ];

        if ($this->notSetToCustomEmpty('project_id')) {
            $rules['project_id'] = array_merge($rules['project_id'], [
                'integer',
                Rule::exists('projects', 'id')
                    ->where('company_id', $current_company_id),
            ]);
        }

        return $rules;
    }

    protected function notSetToCustomEmpty($field)
    {
        return $this->input($field) &&
            $this->input($field) != TimeTrackingProjectFilter::EMPTY;
    }
}
