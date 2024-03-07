<?php

namespace App\Interfaces;

interface PermissibleRelationsInterface
{
    /**
     * Resource can have assigned multiple roles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function roles();

    /**
     * Resource can have assigned multiple users.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function users();
}
