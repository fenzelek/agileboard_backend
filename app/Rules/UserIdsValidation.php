<?php

namespace App\Rules;

use App\Models\Db\Company;
use App\Models\Db\User;
use Illuminate\Contracts\Validation\Rule;

class UserIdsValidation implements Rule
{
    private User $user;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $company_id = $this->user->getSelectedCompanyId();
        $company = Company::where('id', $company_id)->first();
        $company_users = $company->users()->get()->pluck('id');

        return collect($value)->diff($company_users)->isEmpty();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.custom.ids');
    }
}
