<?php

namespace App\Modules\Integration\Http\Requests\TimeTracking;

use App\Http\Requests\Request;
use App\Models\Db\Integration\Integration;
use Illuminate\Validation\Rule;

class ActivityBulkUpdate extends Request
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        $current_company_id = $this->currentUser()->getSelectedCompanyId();
        $adminable = $this->currentUser()->isOwnerOrAdmin();
        $rules = [
            'activities' => [
                'required',
                'array',
                'max:100',
            ],
            'activities.*' => [
                'required',
                'array',
            ],
            'activities.*.id' => $this->getIdRules($adminable, $current_company_id),
            'activities.*.project_id' => $this->getProjectIdRules($adminable, $current_company_id),
            'activities.*.ticket_id' => [
                'present',
                'nullable',
                'integer',
            ],
            'activities.*.comment' => [
                'present',
                'string',
                'max:255',
            ],
        ];

        // only company owner/admin can lock or unlock record
        if ($adminable) {
            $rules['activities.*.locked'] = [
                'required',
                'boolean',
            ];
        }

        // now need to make sure activity has set ticket_id that belongs to given project_id
        if (is_array($this->input('activities'))) {
            $rules = $this->addTicketIdExtraRules($rules);
        }

        return $rules;
    }

    /**
     * Add ticket_id extra rules.
     *
     * @param array $rules
     *
     * @return array
     */
    protected function addTicketIdExtraRules(array $rules)
    {
        foreach ($this->input('activities') as $key => $activity) {
            // both keys need to be set to process
            if (! array_key_exists('ticket_id', $activity) ||
                ! array_key_exists('project_id', $activity)) {
                continue;
            }

            if ($activity['project_id'] !== null && $activity['ticket_id'] !== null) {
                $rules['activities.' . $key . '.ticket_id'] =
                    Rule::exists('tickets', 'id')
                        ->where('project_id', $activity['project_id']);
            } else {
                // this is small trick - if it won't be passed as null, we will require
                // invalid type array and altogether it will fail together with
                //  activities.*.ticket_id rules which requires string
                $rules['activities.' . $key . '.ticket_id'] = ['nullable', 'array'];
            }
        }

        return $rules;
    }

    /**
     * Get rules for id field.
     *
     * @param bool $adminable
     * @param int $current_company_id
     *
     * @return array
     */
    protected function getIdRules($adminable, $current_company_id)
    {
        $rules = [
            'required',
            'integer',
            'distinct',
        ];

        $exists_rule = Rule::exists('time_tracking_activities', 'id')
            ->where(function ($query) use ($current_company_id) {
                $query->whereIn('integration_id', Integration::where(
                    'company_id',
                    $current_company_id
                )->pluck('id')->all());
            });

        // if not company admin/owner, only allow to modify own entries that are not locked
        if (! $adminable) {
            $exists_rule = $exists_rule->where('user_id', $this->currentUser()->id)
                ->whereNull('locked_user_id');
        }

        $rules[] = $exists_rule;

        return $rules;
    }

    /**
     * Get rules for project_id field.
     *
     * @param bool $adminable
     * @param int $current_company_id
     *
     * @return array
     */
    protected function getProjectIdRules($adminable, $current_company_id)
    {
        $rules = [
            'present',
            'nullable',
            'integer',
            Rule::exists('projects', 'id')
                ->where('company_id', $current_company_id),
        ];

        // if not company admin/owner, only allow to set project user is assigned to
        if (! $adminable) {
            $rules[] = Rule::exists('project_user', 'project_id')
                ->where('user_id', $this->currentUser()->id);
        }

        return $rules;
    }
}
