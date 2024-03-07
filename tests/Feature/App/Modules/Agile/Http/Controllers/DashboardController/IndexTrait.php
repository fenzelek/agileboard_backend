<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\DashboardController;

use App\Models\Db\Project;
use App\Models\Db\Sprint;
use App\Models\Db\Status;
use App\Models\Db\Story;
use App\Models\Db\Ticket;
use App\Models\Db\TicketType;
use App\Models\Db\User;
use Carbon\Carbon;

trait IndexTrait
{
    public function getExpectedJsonStructure(): array
    {
        return [
            'data' => [
                'YourProjects' => [
                    [
                        'id',
                        'company_id',
                        'name',
                        'short_name',
                        'color',
                    ],
                ],
                'YourTasks' => [
                    [
                        'id',
                        'name',
                        'title',
                        'status_id',
                        'status_name',
                        'sprint_id',
                        'project_id',
                        'project_name',
                        'stories' => [
                            [
                                'color',
                                'name',
                            ],
                        ],
                    ],
                ],
                'LastAdded' => [
                    [
                        'id',
                        'title',
                        'name',
                        'created_at',
                        'type_id',
                        'type_name',
                        'stories' => [
                            [
                                'color',
                                'name',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param int $company_id
     * @param User $user
     * @param Carbon|null $closed_at
     *
     * @return Project;
     */
    protected function createProject($company_id, User $user, Carbon $closed_at = null): Project
    {
        $project = factory(Project::class)->create([
            'company_id' => $company_id,
            'closed_at' => empty($closed_at) ? null : $closed_at->toDateTimeString(),
        ]);

        $project->users()->attach($user);

        return $project;
    }

    /**
     * @param int $project_id
     * @param string $name
     *
     * @return Sprint
     */
    protected function createSprint(int $project_id, string $name): Sprint
    {
        return factory(Sprint::class)->create([
            'project_id' => $project_id,
            'name' => $name,
            'priority' => 1,
            'status' => Sprint::ACTIVE,
        ]);
    }

    /**
     * @param int $project_id
     * @param int $sprint_id
     * @param int $user_id
     * @param  int $status_id = 11
     *
     * @return Ticket
     */
    protected function createTicket(int $project_id, int $sprint_id, int $user_id, int $status_id = 11): Ticket
    {
        return factory(Ticket::class)->create([
            'project_id' => $project_id,
            'sprint_id' => $sprint_id,
            'assigned_id' => $user_id,
            'status_id' => $status_id,
            'name' => 'test',
            'priority' => 1,
            'hidden' => 0,
        ]);
    }

    /**
     * @param int $id
     * @param string $name
     *
     * @return TicketType
     */
    protected function createTicketType(int $id, string $name): TicketType
    {
        return factory(TicketType::class)->create([
            'id' => $id,
            'name' => $name,
        ]);
    }

    /**
     * @param int $project_id
     * @param int $sprint_id
     * @param int $user_id
     *
     * @return Story
     */
    protected function createStory(int $project_id, string $name): Story
    {
        return factory(Story::class)->create([
            'project_id' => $project_id,
            'name' => $name,
            'color' => '#1E88E5',
        ]);
    }

    /**
     * @param int $project_id
     * @param string $name
     * @param int $priority
     *
     * @return Status
     */
    protected function createStatus(int $project_id, string $name, int $priority = 100): Status
    {
        return factory(Status::class)->create([
            'project_id' => $project_id,
            'name' => $name,
            'priority' => $priority,
        ]);
    }
}
