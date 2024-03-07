<?php

namespace App\Modules\User\Traits;

trait Fillable
{
    /**
     * Mutator to set password.
     *
     * @param string $value
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }
}
