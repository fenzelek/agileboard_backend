<?php

declare(strict_types=1);

namespace Tests\Unit\App\Modules\Agile\Services\SprintExportService;

use App\Models\Db\Ticket;
use App\Models\Db\User;
use App\Modules\Agile\Models\TicketExportDto;
use App\Modules\Agile\Services\SprintExportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SprintExportServiceTest extends TestCase
{
    use DatabaseTransactions;
    use SprintExportServiceTrait;

    private SprintExportService $service;

    /**
     * @test
     */
    public function makeExport_ShouldReturnValidSprintExport(): void
    {
        //Given
        $sprint_name = 'Sprint1';
        $user_first_name = 'Paweł';
        $user_last_name = 'Kowalski';
        $ticket_title = null;
        $ticket_name = null;
        $ticket_estimated_seconds = 60;
        $ticket_tracked_seconds = [20, 30];

        $user = factory(User::class)->create([
            'first_name' => $user_first_name,
            'last_name' => $user_last_name,
        ]);
        $sprint = $this->createClosedSprintWithProject($sprint_name);
        $this->createSprintTicket(
            $sprint,
            $user->id,
            $ticket_name,
            $ticket_title,
            $ticket_estimated_seconds,
            $ticket_tracked_seconds
        );

        //When
        $result = $this->service->makeExport($sprint->project, $sprint);

        //Then
        $this->assertCount(1, $result->getTickets());
        /** @var TicketExportDto $first_ticket */
        $ticket_dto = $result->getTickets()->first();
        $this->assertTicketExportDtoCorrect([
            'user_first_name' => $user_first_name,
            'user_last_name' => $user_last_name,
            'name' => $ticket_name??'',
            'title' => $ticket_title??'',
            'estimated_seconds' => $ticket_estimated_seconds,
            'tracked_seconds' => array_sum($ticket_tracked_seconds),
        ], $ticket_dto);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(SprintExportService::class);
        Ticket::unsetEventDispatcher();
    }
}
