<?php

namespace App\Rules;

use App\Models\Db\BlacklistDomain;
use Illuminate\Contracts\Validation\Rule;

class Blacklist implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
        $arr = explode('@', $value);
        if (count($arr) != 2) {
            return true;
        }

        if (BlacklistDomain::where('domain', $arr[1])->first()) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.custom.email.blacklist');
    }
}
