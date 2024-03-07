<?php

namespace App\Modules\Integration\Http\Requests\TimeTracking\Traits;

use Illuminate\Validation\Rule;

trait StoreActivityTrait
{
    /**
     * @return array
     */
    protected function commonStoreActivityRules(): array
    {
        $current_company_id = $this->currentUser()->getSelectedCompanyId();

        return [
            'project_id' => [
                'required', Rule::exists('projects', 'id')
                    ->where('company_id', $current_company_id),
            ],
            'ticket_id' => [
                'required', Rule::exists('tickets', 'id'),
            ],
            'comment' => [
                'nullable',
                'string',
            ],
            'from' => 'required', 'string', 'date_format:Y-m-d H:i:s',
            'to' => 'required', 'string', 'date_format:Y-m-d H:i:s',
        ];
    }

    /**
     * @return array
     */
    protected function commonRemoveActivitiesRules(): array
    {
        $current_company_id = $this->currentUser()->getSelectedCompanyId();

        return [
            'activities.*.*' => [
                $this->getIdRules($current_company_id),
            ],
        ];
    }

    protected function getDateRules()
    {
        return [
            'string',
            'date_format:Y-m-d H:i:s',
        ];
    }
}
