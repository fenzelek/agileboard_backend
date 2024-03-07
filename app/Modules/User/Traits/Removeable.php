<?php

namespace App\Modules\User\Traits;

trait Removeable
{
    /**
     * Whether is deleted or not.
     *
     * @return bool
     */
    public function isDeleted()
    {
        return (bool) $this->deleted;
    }
}
