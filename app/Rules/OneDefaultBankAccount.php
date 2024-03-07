<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class OneDefaultBankAccount implements Rule
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
        if (! is_array($value)) {
            return false;
        }

        return collect($value)->map(function ($item) {
            return (int) array_get($item, 'default', 0);
        })->sum() === 1;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The default bank account must be equal one.';
    }
}
