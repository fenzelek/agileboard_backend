<?php

namespace Tests\Unit\App\Modules\Notification\Services;

use App\Models\Db\Project;
use App\Modules\Agile\Events\CreateTicketEvent;
use App\Modules\Notification\Services\BroadcastChannel;
use App\Modules\Notification\Services\EventChannelsSettings;
use Mockery as m;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BroadcastChannelTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function via_success()
    {
        $event = m::mock(CreateTicketEvent::class);
        $event->shouldReceive('getBroadcastData')->andReturn([]);
        $project = factory(Project::class)->create();
        $event->shouldReceive('getProject')->andReturn($project);

        $return = (new BroadcastChannel($event))->via(null);

        $this->assertSame(EventChannelsSettings::BROADCAST, $return[0]);
    }

    /** @test */
    public function broadcastOn_success()
    {
        $event = m::mock(CreateTicketEvent::class);
        $event->shouldReceive('getBroadcastData')->andReturn([]);
        $project = factory(Project::class)->create();
        $event->shouldReceive('getProject')->andReturn($project);

        $return = (new BroadcastChannel($event))->broadcastOn();

        $this->assertSame([], $return);
    }

    /** @test */
    public function toBroadcast_success()
    {
        $event = m::mock(CreateTicketEvent::class);
        $event->shouldReceive('getBroadcastData')->andReturn([]);
        $event->shouldReceive('getBroadcastChannel')->andReturn('test');
        $project = factory(Project::class)->create();
        $event->shouldReceive('getProject')->andReturn($project);
        $event->shouldReceive('getMessage')->andReturn(['content' => 'test']);

        $return = (new BroadcastChannel($event))->toBroadcast(null);

        $this->assertSame(\Illuminate\Notifications\Messages\BroadcastMessage::class, get_class($return));
    }
}
