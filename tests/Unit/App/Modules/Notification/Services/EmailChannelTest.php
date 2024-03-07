<?php

namespace Tests\Unit\App\Modules\Notification\Services;

use App\Models\Db\Project;
use App\Models\Db\User;
use App\Modules\Agile\Events\CreateTicketEvent;
use App\Modules\Notification\Services\EmailChannel;
use App\Modules\Notification\Services\EventChannelsSettings;
use Mockery as m;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EmailChannelTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function via_success()
    {
        $event = m::mock(CreateTicketEvent::class);

        $return = (new EmailChannel($event))->via(null);

        $this->assertSame(EventChannelsSettings::MAIL, $return[0]);
    }

    /** @test */
    public function toMail_success()
    {
        $event = m::mock(CreateTicketEvent::class);
        $project = factory(Project::class)->create();
        $user = factory(User::class)->create();
        $event->shouldReceive('getProject')->once()->andReturn($project);
        $event->shouldReceive('getMessage')->andReturn(['title' => 'test', 'content' => 'test', 'url_title' => 'test', 'url' => 'http://example']);
        $event->shouldReceive('getRecipients')->andReturn([]);

        $return = (new EmailChannel($event))->toMail($user);

        $this->assertSame(\App\Modules\Notification\Services\Mailable::class, get_class($return));
    }
}
