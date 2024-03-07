<?php

namespace App\Modules\Project\Http\Requests;

use App\Models\Other\UserCompanyStatus;
use Illuminate\Validation\Rule;

class ProjectUpdate extends ProjectStore
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $selected_company_id = $this->selectedCompanyId();

        $rules = $this->getBaseRules($selected_company_id);
        $rules['name'][] = $this->getUniqueNameRule($selected_company_id)
            ->ignore($this->route('project')->id);

        $rules['short_name'][] = $this->getUniqueShortNameRule($selected_company_id)
            ->ignore($this->route('project')->id);

        $rules['time_tracking_visible_for_clients'] = ['required', 'boolean'];
        $rules['status_for_calendar_id'] = ['nullable', Rule::exists('statuses', 'id')
            ->where('project_id', $this->route('project')->id), ];
        $rules['language'] = ['required', 'in:pl,en'];
        $rules['email_notification_enabled'] = ['required', 'boolean'];
        $rules['slack_notification_enabled'] = ['required', 'boolean'];
        $rules['slack_webhook_url'] = ['max:255', 'url'];
        $rules['slack_channel'] = ['max:255'];
        $rules['color'] = ['required', 'max:255'];
        $rules['ticket_scheduled_dates_with_time'] = ['required', 'boolean'];
        $rules['users.*.user_id'] = [
            'required',
            'distinct',
            'int'
            ];

        return $rules;
    }
}
