<?php

namespace App\Models\CustomCollections;

use Illuminate\Database\Eloquent\Collection;

class UsersCollection extends Collection
{
    /**
     * @param $project_id
     * @return static
     */
    public function getProjectRelated($project_id)
    {
        return $this->filter(function ($workload_user) use ($project_id) {
            if ($this->isUserProject($workload_user, $project_id)) {
                return $workload_user;
            }
        });
    }

    /**
     * @param $workload_user
     * @param $project_id
     * @return bool
     */
    private function isUserProject($workload_user, $project_id): bool
    {
        return in_array($project_id, $workload_user->projects->pluck('id')->toArray());
    }
}
