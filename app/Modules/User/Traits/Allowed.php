<?php

namespace App\Modules\User\Traits;

use App\Models\Db\Model;
use App\Models\Db\User;
use App\Models\Other\UserCompanyStatus;

trait Allowed
{
    /**
     * Choose only users that are allowed to be displayed for given user (or
     * current user if none user given).
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param \App\Models\Db\Model|int|null $user
     * @param array $excluded_roles Roles that shouldn't be included
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeAllowed($query, $user = null, array $excluded_roles = [])
    {
        // get user by id or use object - if not passed any, we use current
        // user
        if (! $user) {
            $user = auth()->user();
        } elseif (! $user instanceof Model) {
            $user = self::find($user);
        }

        // user has not been found or no company selected - return no results
        /** @var User $user */
        if (! $user || ! $user->getSelectedCompanyId()) {
            return $query->whereRaw('1 = 0');
        }

        // we always choose users from currently selected company only with approved status
        $query->whereHas('companies', function ($q) use ($user, $excluded_roles) {
            $q->where('companies.id', $user->getSelectedCompanyId())
                ->where('status', UserCompanyStatus::APPROVED);
            // if we want to exclude clients we need to add extra query condition
            if ($excluded_roles) {
                $q->whereNotIn('role_id', $excluded_roles);
            }
        });

        // for admins and owners we don't limit results further
        if ($user->isAdmin() || $user->isOwner() || $user->isSystemAdmin()) {
            return $query;
        }

        // for others we will choose only users assigned to same projects
        return $query->where(function ($q) use ($user) {
            $q->where('id', $user->id)
                ->orWhereHas('projects', function ($q) use ($user) {
                    $q->inCompany($user)->whereHas('users', function ($q) use ($user) {
                        $q->where('project_user.user_id', $user->id);
                    });
                });
        });
    }
}
