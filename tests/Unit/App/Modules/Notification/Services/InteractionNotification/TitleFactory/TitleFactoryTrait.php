<?php

declare(strict_types=1);

namespace Tests\Unit\App\Modules\Notification\Services\InteractionNotification\TitleFactory;

use App\Models\Db\Ticket;
use App\Models\Db\TicketComment;
use App\Models\Other\Interaction\ActionType;
use App\Models\Other\Interaction\SourceType;
use App\Modules\Notification\Models\Dto\NotificationPingDTO;

trait TitleFactoryTrait
{
    public function makeNotificationPingForTicket(int $ticket_id): NotificationPingDTO
    {
        return new NotificationPingDTO(
            1,
            1,
            1,
            1,
            '',
            ActionType::PING,
            SourceType::TICKET,
            $ticket_id,
            'label',
            '<h1>message</h1>'
        );
    }

    public function makeNotificationPingForTicketComment(int $ticket_comment_id): NotificationPingDTO
    {
        return new NotificationPingDTO(
            1,
            1,
            1,
            1,
            '',
            ActionType::PING,
            SourceType::TICKET_COMMENT,
            $ticket_comment_id,
            'label',
            '<h1>message</h1>'
        );
    }

    public function createTicket(string $ticket_title, string $ticket_name)
    {
        return factory(Ticket::class)->create(['title' => $ticket_title, 'name' => $ticket_name]);
    }

    public function createTicketComment(int $ticket_id)
    {
        return factory(TicketComment::class)->create(['ticket_id' => $ticket_id]);
    }
}
