<?php

namespace App\Models\Db;

trait PermissibleRelations
{
    /**
     * Resource can have assigned multiple roles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function roles()
    {
        return $this->morphToMany(Role::class, 'permissionable', 'permission_role')
            ->withTimestamps();
    }

    /**
     * Resource can have assigned multiple users.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function users()
    {
        return $this->morphToMany(User::class, 'permissionable', 'permission_user')
            ->withTimestamps();
    }

    /**
     * Takes resources to which the user is assigned.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Project $project
     * @param User $user
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAssignedToUser($query, Project $project, User $user)
    {
        $resource_query = $query->where('project_id', $project->id);

        if (! $user->isOwnerOrAdmin()) {
            $resource_query = $resource_query->where(function ($query) use ($project, $user) {
                $query->where(function ($query) {
                    // if roles and users are empty, user will have permission to this resource
                    $query->doesntHave('roles')->doesntHave('users');
                })->orWhere(function ($query) use ($project, $user) {
                    // if user's role or user id have assigned to resource, user will have access
                    $query->whereHas('roles', function ($query) use ($project, $user) {
                        $query->where('role_id', $user->getRoleInProject($project));
                    })->orWhereHas('users', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    });
                });
            });
        }

        return $resource_query;
    }
}
