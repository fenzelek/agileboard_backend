<?php

namespace App\Modules\Project\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Other\UserCompanyStatus;
use Illuminate\Validation\Rule;

class ProjectStore extends Request
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
        $rules['name'][] = $this->getUniqueNameRule($selected_company_id);
        $rules['short_name'][] = $this->getUniqueShortNameRule($selected_company_id);
        $rules['first_number_of_tickets'] = ['required', 'numeric', 'min:1'];
        $rules['time_tracking_visible_for_clients'] = ['required', 'boolean'];
        $rules['language'] = ['required', 'in:pl,en'];
        $rules['email_notification_enabled'] = ['required', 'boolean'];
        $rules['slack_notification_enabled'] = ['required', 'boolean'];
        $rules['slack_webhook_url'] = ['max:255', 'url'];
        $rules['slack_channel'] = ['max:255'];
        $rules['color'] = ['required', 'max:255'];

        return $rules;
    }

    /**
     * Map users input from [['user_id' => 2, 'role_id' => 5], ['user_id' => 3, 'role_id' => 6] to
     * [ 2 => ['role_id' => 5], 3 => ['role_id' => 6] to use it later for sync/attach.
     *
     * @return array
     */
    public function mappedUsers()
    {
        return collect($this->input('users'))->keyBy('user_id')
            ->map(function ($user) {
                return array_only($user, 'role_id');
            })->all();
    }

    /**
     * Get base rules.
     *
     * @param int $selected_company_id
     *
     * @return array
     */
    protected function getBaseRules($selected_company_id)
    {
        return [
            'name' => [
                'required',
            ],
            'short_name' => [
                'required',
                'max:15',
            ],
            'users' => [
                'required',
                'array',
                'min:1',
            ],
            'users.*.role_id' => [
                'required',
                Rule::exists('company_role', 'role_id')->where('company_id', $selected_company_id),
            ],
            'users.*.user_id' => [
                'required',
                'distinct',
                Rule::exists('user_company', 'user_id')->where('company_id', $selected_company_id)
                    ->where('status', UserCompanyStatus::APPROVED),
            ],
        ];
    }

    /**
     * Get currently selected company id.
     *
     * @return int
     */
    protected function selectedCompanyId()
    {
        return $this->container['auth']->user()->getSelectedCompanyId();
    }

    /**
     * Get unique rule for name field.
     *
     * @param int $selected_company_id
     *
     * @return \Illuminate\Validation\Rules\Unique
     */
    protected function getUniqueNameRule($selected_company_id)
    {
        return Rule::unique('projects', 'name')
            ->where('company_id', $selected_company_id);
    }

    /**
     * Get unique rule for short_name field.
     *
     * @param int $selected_company_id
     *
     * @return \Illuminate\Validation\Rules\Unique
     */
    protected function getUniqueShortNameRule($selected_company_id)
    {
        return Rule::unique('projects', 'short_name')
            ->where('company_id', $selected_company_id);
    }
}
