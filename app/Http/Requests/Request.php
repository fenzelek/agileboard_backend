<?php

namespace App\Http\Requests;

use App\Models\Db\Module;
use App\Models\Db\User;
use Illuminate\Foundation\Http\FormRequest;

abstract class Request extends FormRequest
{
    /**
     * By default we authorize all requests.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Check given application setting for company.
     *
     * @param string
     * @return bool
     */
    protected function checkApplicationSetting($application_setting_type)
    {
        if (Module::where('slug', $application_setting_type)->first()) {
            return auth()->user()->selectedCompany()->appSettings($application_setting_type);
        }

        return false;
    }

    /**
     * Get current user.
     *
     * @return User
     */
    protected function currentUser()
    {
        return $this->container['auth']->user();
    }
}
