<?php

namespace App\Rules;

use App\Models\Db\User;
use Illuminate\Contracts\Validation\Rule;

class ProjectUserValidation implements Rule
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
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $project_ids = $this->user->projects()->get()->pluck('id')->all();

        return in_array($value, $project_ids);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.custom.project');
    }
}
