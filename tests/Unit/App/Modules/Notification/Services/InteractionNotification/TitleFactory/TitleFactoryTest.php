<?php

declare(strict_types=1);

namespace Tests\Unit\App\Modules\Notification\Services\InteractionNotification\TitleFactory;

use App\Models\Db\Ticket;
use App\Models\Db\TicketComment;
use App\Modules\Notification\Services\InteractionNotification\TitleFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TitleFactoryTest extends TestCase
{
    use DatabaseTransactions;
    use TitleFactoryTrait;

    private TitleFactory $title_factory;

    protected function setUp(): void
    {
        parent::setUp();
        Ticket::unsetEventDispatcher();
        $this->title_factory = $this->app->make(TitleFactory::class);
    }


    /**
     * @feature Notification
     * @scenario Get notifications
     * @case Source type is ticket
     *
     * @test
     */
    public function make_WhenSourceTypeIsTicket_ShouldReturnTicketNameAsTitle(): void
    {
        //GIVEN
        $ticket_title = 'AB-1300';
        $ticket_name = 'Ticket name';
        $ticket = $this->createTicket($ticket_title, $ticket_name);
        $notification_ping = $this->makeNotificationPingForTicket($ticket->id);

        //WHEN
        $result = $this->title_factory->make($notification_ping);

        //THEN
        $this->assertSame($result, $ticket_title . ' ' . $ticket_name);
    }

    /**
     * @feature Notification
     * @scenario Get notifications
     * @case Source type is ticket comment
     *
     * @test
     */
    public function make_WhenSourceTypeIsTicketComment_ShouldReturnTicketNameAsTitle(): void
    {
        //GIVEN
        $ticket_title = 'AB-1300';
        $ticket_name = 'Ticket name';
        $ticket_comment = $this->createTicketComment($this->createTicket($ticket_title, $ticket_name)->id);
        $notification_ping = $this->makeNotificationPingForTicketComment($ticket_comment->id);

        //WHEN
        $result = $this->title_factory->make($notification_ping);

        //THEN
        $this->assertSame($result, $ticket_title . ' ' . $ticket_name);
    }

    /**
     * @feature Notification
     * @scenario Get notifications
     * @case Source does not exists
     *
     * @test
     */
    public function make_WhenSourceDoesNotExists_ShouldReturnEmptyString(): void
    {
        //GIVEN
        TicketComment::query()->delete();
        $notification_ping = $this->makeNotificationPingForTicketComment(1);

        //WHEN
        $result = $this->title_factory->make($notification_ping);

        //THEN
        $this->assertSame('', $result);
    }
}
