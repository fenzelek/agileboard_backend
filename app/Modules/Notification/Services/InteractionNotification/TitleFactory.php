<?php

declare(strict_types=1);

namespace App\Modules\Notification\Services\InteractionNotification;

use App\Models\Other\Interaction\SourceType;
use App\Modules\Notification\Models\Dto\NotificationPingDTO;
use UnexpectedValueException;

class TitleFactory
{
    private TicketQuery $ticket_query;
    private KnowledgePageQuery $knowledge_page_query;

    public function __construct(TicketQuery $ticket_query, KnowledgePageQuery $knowledge_page_query)
    {
        $this->ticket_query = $ticket_query;
        $this->knowledge_page_query = $knowledge_page_query;
    }

    public function make(NotificationPingDTO $notification_ping): string
    {
        switch ($notification_ping->getSourceType()) {
            case SourceType::TICKET:
                return $this->makeForTicket($notification_ping);
            case SourceType::TICKET_COMMENT:
                return $this->makeForTicketComment($notification_ping);
            case SourceType::KNOWLEDGE_PAGE:
                return $this->makeForKnowledgePage($notification_ping);
            case SourceType::KNOWLEDGE_PAGE_COMMENT:
                return $this->makeForKnowledgePageComment($notification_ping);
            default:
                throw new UnexpectedValueException('Unexpected source type');
        }
    }

    private function makeForTicket(NotificationPingDTO $notification_ping): string
    {
        $ticket = $this->ticket_query->getTicketWithTrashed($notification_ping->getSourceId());

        return $ticket ? $ticket->title . ' ' . $ticket->name ?? '' : '';
    }

    private function makeForTicketComment(NotificationPingDTO $notification_ping): string
    {
        $comment = $this->ticket_query->getComment($notification_ping->getSourceId());
        $ticket = $comment ? $comment->ticket : null;

        return $ticket ? $ticket->title . ' ' . $ticket->name ?? '' : '';
    }

    private function makeForKnowledgePage(NotificationPingDTO $notification_ping): string
    {
        $page = $this->knowledge_page_query->getPageWithTrashed($notification_ping->getSourceId());

        return $page ? $page->name??'' : '';
    }

    private function makeForKnowledgePageComment(NotificationPingDTO $notification_ping): string
    {
        $page_comment = $this->knowledge_page_query->getComment($notification_ping->getSourceId());
        $page = $page_comment ? $page_comment->knowledgePage : null;

        return $page ? $page->name??'' : '';
    }
}
