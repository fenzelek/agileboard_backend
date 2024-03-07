<?php

declare(strict_types=1);

namespace App\Modules\Notification\Services;

use App\Interfaces\Interactions\INotificationPingDTO;
use App\Models\Notification\Contracts\IInteractionNotificationManager;
use App\Models\Notification\Contracts\ISendResult;
use App\Models\Other\Interaction\ActionType;
use App\Models\Other\Interaction\InteractionEventType;
use App\Models\Other\Interaction\SourceType;
use App\Modules\Notification\Models\Descriptors\FailReason;
use App\Modules\Notification\Models\Dto\SendResult;
use App\Modules\Notification\Notifications\InteractionNotification;
use Illuminate\Support\Facades\Notification;

class InteractionNotificationManager implements IInteractionNotificationManager
{
    private InteractionQuery $interaction_query;

    public function __construct(InteractionQuery $interaction_query)
    {
        $this->interaction_query = $interaction_query;
    }

    public function notify(INotificationPingDTO $interaction): ISendResult
    {
        $result = $this->validate($interaction);
        if (! $result->success()) {
            return $result;
        }
        $notifiable = $this->interaction_query->findUser($interaction->getRecipientId());

        Notification::send($notifiable, new InteractionNotification($interaction));

        return $result;
    }

    private function validate(INotificationPingDTO $interaction): SendResult
    {
        if (! in_array($interaction->getSourceType(), SourceType::all())) {
            return new SendResult(false, FailReason::INVALID_DOCUMENT_TYPE);
        }
        if (! in_array($interaction->getEventType(), InteractionEventType::all())) {
            return new SendResult(false, FailReason::INVALID_EVENT_TYPE);
        }
        if (! in_array($interaction->getActionType(), ActionType::all())) {
            return new SendResult(false, FailReason::INVALID_ACTION_TYPE);
        }
        if (! $this->interaction_query->userExists($interaction->getAuthorId())) {
            return new SendResult(false, FailReason::AUTHOR_DOES_NOT_EXISTS);
        }
        if (! $this->interaction_query->userExists($interaction->getRecipientId())) {
            return new SendResult(false, FailReason::RECIPIENT_DOES_NOT_EXISTS);
        }

        return new SendResult(true);
    }
}
