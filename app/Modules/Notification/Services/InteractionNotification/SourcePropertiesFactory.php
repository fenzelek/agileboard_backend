<?php

namespace App\Modules\Notification\Services\InteractionNotification;

use App\Models\Other\Interaction\SourcePropertyType;
use App\Models\Other\Interaction\SourceType;
use App\Modules\Notification\Models\Dto\NotificationPingDTO;
use App\Modules\Notification\Models\Dto\SourceProperty;
use UnexpectedValueException;

class SourcePropertiesFactory
{
    private TicketQuery $ticket_query;
    private KnowledgePageQuery $knowledge_page_query;

    public function __construct(TicketQuery $ticket_query, KnowledgePageQuery $knowledge_page_query)
    {
        $this->ticket_query = $ticket_query;
        $this->knowledge_page_query = $knowledge_page_query;
    }

    /**
     * @return SourceProperty[]
     */
    public function make(NotificationPingDTO $notification_ping): ?array
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

    private function makeForTicket(NotificationPingDTO $notification_ping): ?array
    {
        $ticket = $this->ticket_query->getTicketWithTrashed($notification_ping->getSourceId());
        if ($ticket===null) {
            return null;
        }

        return [
            new SourceProperty(SourcePropertyType::TICKET, (string) $ticket->id),
        ];
    }

    private function makeForTicketComment(NotificationPingDTO $notification_ping): ?array
    {
        $comment = $this->ticket_query->getComment($notification_ping->getSourceId());
        if ($comment === null || $comment->ticket === null) {
            return null;
        }

        return [
            new SourceProperty(SourcePropertyType::TICKET, (string) $comment->ticket_id),
            new SourceProperty(SourcePropertyType::TICKET_COMMENT, (string) $comment->id),
        ];
    }

    private function makeForKnowledgePage(NotificationPingDTO $notification_ping): ?array
    {
        $page = $this->knowledge_page_query->getPageWithTrashed($notification_ping->getSourceId());
        if ($page===null) {
            return null;
        }

        return [
            new SourceProperty(SourcePropertyType::KNOWLEDGE_PAGE, (string) $page->id),
        ];
    }

    private function makeForKnowledgePageComment(NotificationPingDTO $notification_ping): ?array
    {
        $page_comment = $this->knowledge_page_query->getComment($notification_ping->getSourceId());
        if ($page_comment===null || $page_comment->knowledgePage === null) {
            return null;
        }

        return [
            new SourceProperty(SourcePropertyType::KNOWLEDGE_PAGE, (string) $page_comment->knowledge_page_id),
            new SourceProperty(SourcePropertyType::KNOWLEDGE_PAGE_COMMENT, (string) $page_comment->id),
        ];
    }
}
