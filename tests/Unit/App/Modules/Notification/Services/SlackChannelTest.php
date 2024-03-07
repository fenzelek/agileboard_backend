<?php

namespace Tests\Unit\App\Modules\Notification\Services;

use App\Models\Db\Project;
use App\Modules\Agile\Events\CreateTicketEvent;
use App\Modules\Notification\Services\EventChannelsSettings;
use App\Modules\Notification\Services\SlackChannel;
use Mockery as m;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SlackChannelTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function via_success()
    {
        $event = m::mock(CreateTicketEvent::class);

        $return = (new SlackChannel($event))->via(null);

        $this->assertSame(EventChannelsSettings::SLACK, $return[0]);
    }

    /** @test */
    public function toSlack_success()
    {
        $event = m::mock(CreateTicketEvent::class);
        $project = factory(Project::class)->create();
        $event->shouldReceive('getMessage')->andReturn(['content' => 'test', 'url_title' => 'test', 'url' => 'http://example']);
        $event->shouldReceive('getProject')->andReturn($project);

        $return = (new SlackChannel($event))->toSlack(null);

        $this->assertSame(\Illuminate\Notifications\Messages\SlackMessage::class, get_class($return));
    }
}
