<?php

namespace App\Modules\Integration\Http\Requests\TimeTracking;

use App\Filters\TimeTrackingActivityFilter;
use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class Activity extends Request
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
            'project_id' => [
                'nullable',
            ],
            'ticket_id' => [
                'nullable',
            ],
            'external_activity_id' => [
                'nullable',
                'string',
            ],
            'time_tracking_user_id' => [
                'nullable',
                'integer',
                Rule::exists('time_tracking_users', 'id'),
            ],
            'time_tracking_project_id' => [
                'nullable',
                'integer',
                Rule::exists('time_tracking_projects', 'id'),
            ],
            'time_tracking_note_id' => [
                'nullable',
                'integer',
                Rule::exists('time_tracking_notes', 'id'),
            ],
            'comment' => [
                'nullable',
                'string',
            ],
            'time_tracking_note_content' => [
                'nullable',
                'string',
            ],
            'external_user_id' => [
                'nullable',
                'string',
                Rule::exists('time_tracking_users', 'external_user_id'),
            ],
            'min_utc_started_at' => $this->getDateRules(),
            'max_utc_started_at' => $this->getDateRules(),
            'min_utc_finished_at' => $this->getDateRules(),
            'max_utc_finished_at' => $this->getDateRules(),
            'min_tracked' => [
                'nullable',
                'integer',
            ],
            'max_tracked' => [
                'nullable',
                'integer',
            ],
            'min_activity_level' => [
                'nullable',
                'numeric',
            ],
            'max_activity_level' => [
                'nullable',
                'numeric',
            ],
            'all' => [
                'sometimes',
                'boolean',
            ],
            'source' => [
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

        if ($this->notSetToCustomEmpty('project_id')) {
            $rules['project_id'] = array_merge($rules['project_id'], [
                'integer',
                Rule::exists('projects', 'id')
                    ->where('company_id', $current_company_id),
            ]);
        }

        if ($this->notSetToCustomEmpty('ticket_id')) {
            $rules['ticket_id'] = array_merge($rules['ticket_id'], [
                'integer',
                Rule::exists('tickets', 'id'),
            ]);
        }

        return $rules;
    }

    protected function notSetToCustomEmpty($field)
    {
        return $this->input($field) &&
            $this->input($field) != TimeTrackingActivityFilter::EMPTY;
    }

    protected function getDateRules()
    {
        return [
            'nullable',
            'string',
            'date_format:Y-m-d H:i:s',
        ];
    }
}
