<?php

declare(strict_types=1);

namespace App\Modules\Notification\Models\Descriptors;

class FailReason
{
    const RECIPIENT_DOES_NOT_EXISTS = 'Recipient does not exists';
    const AUTHOR_DOES_NOT_EXISTS = 'Author does not exists';
    const SOURCE_DOES_NOT_EXISTS = 'Source does not exists';
    const INVALID_DOCUMENT_TYPE = 'Invalid document type';
    const INVALID_EVENT_TYPE = 'Invalid event type';
    const INVALID_ACTION_TYPE = 'Invalid action type';
    const NO_CORRESPONDING_NOTIFICATION_TYPE = 'No corresponding notification type for interaction';

    const INVALID_NOTIFICATION_IDS = 'Invalid notification ids';

    public static function all(): array
    {
        return [
            self::RECIPIENT_DOES_NOT_EXISTS,
            self::AUTHOR_DOES_NOT_EXISTS,
            self::SOURCE_DOES_NOT_EXISTS,
            self::INVALID_EVENT_TYPE,
            self::INVALID_ACTION_TYPE,
            self::INVALID_NOTIFICATION_IDS,
        ];
    }
}
