<?php

namespace Tests\Unit\App\Modules\Notification\Services;

use App\Helpers\EventTypes;
use App\Models\Db\Project;
use App\Modules\Agile\Events\CreateTicketEvent;
use App\Modules\Notification\Services\EventChannelsFactory;
use App\Modules\Notification\Services\EventChannelsSettings;
use Mockery as m;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EventChannelsFactoryTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function make_email_enabled_success()
    {
        $eventChannelsSettings = m::mock(EventChannelsSettings::class);
        $event = m::mock(CreateTicketEvent::class);
        $project = factory(Project::class)->create(['email_notification_enabled' => true]);
        $event->shouldReceive('getType')->andReturn(EventTypes::TICKET_STORE);
        $event->shouldReceive('getProject')->andReturn($project);
        $eventChannelsSettings->shouldReceive('get')->once()->andReturn([EventChannelsSettings::MAIL]);

        $channels = (new EventChannelsFactory($eventChannelsSettings))->make($event);

        $this->assertSame(1, count($channels));
        $this->assertSame(\App\Modules\Notification\Services\EmailChannel::class, get_class($channels[0]));
    }

    /** @test */
    public function make_email_disabled_success()
    {
        $eventChannelsSettings = m::mock(EventChannelsSettings::class);
        $event = m::mock(CreateTicketEvent::class);
        $project = factory(Project::class)->create(['email_notification_enabled' => false]);
        $event->shouldReceive('getType')->andReturn(EventTypes::TICKET_STORE);
        $event->shouldReceive('getProject')->andReturn($project);
        $eventChannelsSettings->shouldReceive('get')->once()->andReturn([EventChannelsSettings::MAIL]);

        $channels = (new EventChannelsFactory($eventChannelsSettings))->make($event);

        $this->assertSame(0, count($channels));
    }

    /** @test */
    public function make_slack_enabled_success()
    {
        $eventChannelsSettings = m::mock(EventChannelsSettings::class);
        $event = m::mock(CreateTicketEvent::class);
        $project = factory(Project::class)->create(['slack_notification_enabled' => true]);
        $event->shouldReceive('getType')->andReturn(EventTypes::TICKET_STORE);
        $event->shouldReceive('getProject')->andReturn($project);
        $eventChannelsSettings->shouldReceive('get')->once()->andReturn([EventChannelsSettings::SLACK]);

        $channels = (new EventChannelsFactory($eventChannelsSettings))->make($event);

        $this->assertSame(1, count($channels));
        $this->assertSame(\App\Modules\Notification\Services\SlackChannel::class, get_class($channels[0]));
    }

    /** @test */
    public function make_slack_disabled_success()
    {
        $eventChannelsSettings = m::mock(EventChannelsSettings::class);
        $event = m::mock(CreateTicketEvent::class);
        $project = factory(Project::class)->create(['slack_notification_enabled' => false]);
        $event->shouldReceive('getType')->andReturn(EventTypes::TICKET_STORE);
        $event->shouldReceive('getProject')->andReturn($project);
        $eventChannelsSettings->shouldReceive('get')->once()->andReturn([EventChannelsSettings::SLACK]);

        $channels = (new EventChannelsFactory($eventChannelsSettings))->make($event);

        $this->assertSame(0, count($channels));
    }

    /** @test */
    public function make_slack_empty_url_success()
    {
        $eventChannelsSettings = m::mock(EventChannelsSettings::class);
        $event = m::mock(CreateTicketEvent::class);
        $project = factory(Project::class)->create(['slack_notification_enabled' => true, 'slack_webhook_url' => '']);
        $event->shouldReceive('getType')->andReturn(EventTypes::TICKET_STORE);
        $event->shouldReceive('getProject')->andReturn($project);
        $eventChannelsSettings->shouldReceive('get')->once()->andReturn([EventChannelsSettings::SLACK]);

        $channels = (new EventChannelsFactory($eventChannelsSettings))->make($event);

        $this->assertSame(0, count($channels));
    }

    /** @test */
    public function make_broadcast_success()
    {
        $eventChannelsSettings = m::mock(EventChannelsSettings::class);
        $event = m::mock(CreateTicketEvent::class);
        $project = factory(Project::class)->create([]);
        $event->shouldReceive('getType')->andReturn(EventTypes::TICKET_STORE);
        $event->shouldReceive('getProject')->andReturn($project);
        $event->shouldReceive('getBroadcastData')->andReturn([]);
        $eventChannelsSettings->shouldReceive('get')->once()->andReturn([EventChannelsSettings::BROADCAST]);

        $channels = (new EventChannelsFactory($eventChannelsSettings))->make($event);

        $this->assertSame(1, count($channels));
        $this->assertSame(\App\Modules\Notification\Services\BroadcastChannel::class, get_class($channels[0]));
    }
}
