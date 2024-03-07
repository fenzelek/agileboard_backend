<?php

declare(strict_types=1);

namespace App\Modules\Notification\Services\InteractionNotification;

use App\Models\Db\User;
use App\Modules\Notification\Models\Dto\InteractionNotification;
use App\Modules\Notification\Models\Dto\NotificationPingDTO;

class InteractionFormatter
{
    private User $user;

    private NotificationParser $parser;

    private TitleFactory $title_factory;

    private SourcePropertiesFactory $source_properties_factory;

    public function __construct(
        User $user,
        NotificationParser $parser,
        TitleFactory $title_factory,
        SourcePropertiesFactory $source_properties_factory
    ) {
        $this->user = $user;
        $this->parser = $parser;
        $this->title_factory = $title_factory;
        $this->source_properties_factory = $source_properties_factory;
    }

    public function format(array $data, int $company_id): InteractionNotification
    {
        $notification_ping = new NotificationPingDTO(
            $company_id,
            $this->parser->parseAuthorId($data),
            $this->parser->parseRecipientId($data),
            $this->parser->parseProjectId($data),
            $this->parser->parseEventType($data),
            $this->parser->parseActionType($data),
            $this->parser->parseSourceType($data),
            $this->parser->parseSourceId($data),
            $this->parser->parseRef($data),
            $this->parser->parseMessage($data)
        );

        return $this->formatToRead($notification_ping);
    }

    public function formatToRead(NotificationPingDTO $notification_ping): InteractionNotification
    {
        return new InteractionNotification(
            $notification_ping->getProjectId(),
            $notification_ping->getActionType(),
            $notification_ping->getEventType(),
            $notification_ping->getSourceType(),
            $this->title_factory->make($notification_ping),
            $this->getAuthorName($notification_ping->getAuthorId()),
            $this->source_properties_factory->make($notification_ping),
            $notification_ping->getRef(),
            $notification_ping->getMessage()
        );
    }

    private function getAuthorName(int $author_id): string
    {
        /** @var User $user */
        $user = $this->user->newQuery()->find($author_id);
        return $user ? $user->first_name . ' ' . $user->last_name : '';
    }
}
