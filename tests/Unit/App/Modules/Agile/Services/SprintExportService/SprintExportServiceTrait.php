<?php

declare(strict_types=1);

namespace Tests\Unit\App\Modules\Agile\Services\SprintExportService;

use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Project;
use App\Models\Db\Sprint;
use App\Models\Db\Ticket;
use App\Modules\Agile\Models\TicketExportDto;

trait SprintExportServiceTrait
{
    protected function createClosedSprintWithProject(string $sprint_name): Sprint
    {
        $project = factory(Project::class)->create();

        return factory(Sprint::class)->create([
            'project_id' => $project->id,
            'name' => $sprint_name,
            'status' => Sprint::CLOSED,
        ]);
    }

    /**
     * @param int[] $tracked_times
     */
    protected function createSprintTicket(Sprint $sprint, int $user_id, ?string $name, ?string $title, int $estimate_time, array $tracked_times): Ticket
    {
        $ticket = factory(Ticket::class)->create([
            'assigned_id' => $user_id,
            'sprint_id' => $sprint->id,
            'estimate_time' => $estimate_time,
            'name' => $name,
            'title' => $title,
        ]);

        foreach ($tracked_times as $tracked_time) {
            factory(Activity::class)->create([
                'ticket_id' => $ticket->id,
                'tracked' => $tracked_time,
            ]);
        }

        return $ticket;
    }

    protected function assertTicketExportDtoCorrect(array $expected, TicketExportDto $actual_dto): void
    {
        $this->assertSame($expected['title'], $actual_dto->getTitle());
        $this->assertSame($expected['tracked_seconds'], $actual_dto->getTrackedSeconds());
        $this->assertSame($expected['estimated_seconds'], $actual_dto->getEstimatedSeconds());
        $this->assertSame($expected['name'], $actual_dto->getName());
        $this->assertSame($expected['user_first_name'], $actual_dto->getUserFirstName());
        $this->assertSame($expected['user_last_name'], $actual_dto->getUserLastName());
    }
}
