<?php
declare(strict_types=1);

namespace App\Modules\Interaction\Services;

use App\Models\Db\Project;
use App\Modules\Interaction\Contracts\IInteractionDTO;
use App\Modules\Interaction\Contracts\IUsersGroupMembers;
use Illuminate\Support\Collection;

class GetProjectUsers implements IUsersGroupMembers
{
    private Project $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function get(IInteractionDTO $interaction): Collection
    {
        return $this->project->find($interaction->getProjectId())
            ->users()->get();
    }
}