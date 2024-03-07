<?php

namespace Tests\Unit\App\Modules\Agile\Listeners;

use App\Models\Db\Project;
use App\Models\Db\TicketRealization;
use App\Modules\Agile\Events\DeleteTicketEvent;
use App\Modules\Agile\Listeners\RealizationTicketListener;
use Mockery as m;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RealizationTicketListenerTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function disabled()
    {
        $ticket_realization = m::mock(TicketRealization::class);
        $event = m::mock(DeleteTicketEvent::class);
        $project = factory(Project::class)->create(['status_for_calendar_id' => null]);
        $event->shouldReceive('getProject')->andReturn($project);

        (new RealizationTicketListener($ticket_realization))->handle($event);
    }
}
