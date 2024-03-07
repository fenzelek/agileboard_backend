<?php

namespace App\Modules\Agile\Services\Widgets;

use App\Models\Db\Project;
use App\Models\Db\User;
use App\Modules\Agile\Contracts\IWidget;
use Illuminate\Support\Collection;

class YourProjects implements IWidget
{
    /**
     * @param User $user
     *
     * @return mixed
     */

    public function get(User $user): Collection
    {
        $projects = Project::inCompany($user)->orderBy('id');

        return $projects
            ->participedIn($user)
            ->open()
            ->get();
    }

    public function getName(): string
    {
        return __('YourProjects');
    }
}
