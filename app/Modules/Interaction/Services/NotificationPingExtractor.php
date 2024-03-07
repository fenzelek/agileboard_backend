<?php

declare(strict_types=1);

namespace App\Modules\Interaction\Services;

use App\Models\Other\Interaction\NotifiableType;
use App\Modules\Interaction\Contracts\IInteractionDTO;
use App\Modules\Interaction\Contracts\IInteractionPing;
use App\Modules\Interaction\Models\Dto\NotificationGroupPingDTO;
use App\Modules\Interaction\Models\Dto\NotificationUserPingDTO;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;

class NotificationPingExtractor
{
    private Container $app;
    private GetProjectUsersFactory $get_project_users_factory;

    public function __construct(Container $app, GetProjectUsersFactory $get_project_users_factory)
    {
        $this->app = $app;
        $this->get_project_users_factory = $get_project_users_factory;
    }

    function extract(IInteractionDTO $interaction): Collection
    {
        $notification_pings = new Collection();

        /** @var IInteractionPing $interaction_ping */
        foreach ($interaction->getInteractionPings() as $interaction_ping) {
            $notification_pings = $notification_pings->merge($this->getNotificationPings($interaction_ping, $interaction));
        }

        return $notification_pings;
    }

    private function getGroupMembers(IInteractionDTO $interaction, int $recipient_group_id): Collection
    {
        return $this->get_project_users_factory->create($recipient_group_id)->get($interaction);
    }

    private function getNotificationPings(IInteractionPing $interaction_ping, IInteractionDTO $interaction): Collection
    {
        switch ($interaction_ping->getNotifiable()) {

            case NotifiableType::USER:
                return new Collection([new NotificationUserPingDTO($interaction, $interaction_ping)]);

            case NotifiableType::GROUP:
                return $this->getGroupNotificationPings($interaction, $interaction_ping);

            default:
                return new Collection();
        }

    }

    private function getGroupNotificationPings(IInteractionDTO $interaction, IInteractionPing $interaction_ping): Collection
    {
        $group_members = $this->getGroupMembers($interaction, $interaction_ping->getRecipientId());
        $notification_pings = new Collection();

        foreach ($group_members as $user) {
            $notification_pings->push(new NotificationGroupPingDTO($interaction, $interaction_ping, $user));
        }

        return $notification_pings;
    }
}
