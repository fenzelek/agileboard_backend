<?php

namespace Tests\Unit\App\Modules\Notification\Services;

use App\Models\Db\Project;
use App\Modules\Agile\Events\CreateTicketEvent;
use App\Modules\Notification\Services\EmailChannel;
use App\Modules\Notification\Services\NotificationManager;
use Mockery as m;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationManagerTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function proceed_success()
    {
        Notification::fake();

        $event = m::mock(CreateTicketEvent::class);
        $guard = m::mock(\Illuminate\Contracts\Auth\Guard::class);
        $project = factory(Project::class)->create();
        $event_channels_factory = m::mock(\App\Modules\Notification\Services\EventChannelsFactory::class);

        $event->shouldReceive('getProject')->andReturn($project);
        $event->shouldReceive('getRecipients')->andReturn($project);
        $event_channels_factory->shouldReceive('make')->once()->andReturn([new EmailChannel($event)]);

        (new NotificationManager($guard, $event_channels_factory))->proceed($event);

        Notification::assertSentTo($project, EmailChannel::class);
    }
}
