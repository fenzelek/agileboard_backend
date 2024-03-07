<?php

declare(strict_types=1);

namespace App\Modules\Notification\Services\InteractionNotification;

use App\Modules\Notification\Exceptions\MissingNotificationKeyException;

class NotificationParser
{
    public function parseCompanyId(array $data): int
    {
        $this->checkIfKeyExists($data, 'company_id');

        return (int) ($data['company_id']);
    }

    public function parseAuthorId(array $data): int
    {
        $this->checkIfKeyExists($data, 'author_id');

        return (int) ($data['author_id']);
    }

    public function parseRecipientId(array $data): int
    {
        $this->checkIfKeyExists($data, 'author_id');

        return (int) ($data['author_id']);
    }


    public function parseProjectId(array $data): int
    {
        $this->checkIfKeyExists($data, 'project_id');

        return (int) ($data['project_id']);
    }

    public function parseActionType(array $data): string
    {
        $this->checkIfKeyExists($data, 'action_type');

        return (string) ($data['action_type']);
    }

    public function parseSourceId(array $data): int
    {
        $this->checkIfKeyExists($data, 'source_id');

        return (int) ($data['source_id']);
    }

    public function parseSourceType(array $data): string
    {
        $this->checkIfKeyExists($data, 'source_type');

        return (string) ($data['source_type']);
    }

    public function parseEventType(array $data): string
    {
        $this->checkIfKeyExists($data, 'event_type');

        return (string) ($data['event_type']);
    }

    public function parseRef(array $data): string
    {
        $this->checkIfKeyExists($data, 'ref');

        return (string) ($data['ref']);
    }

    public function parseMessage(array $data): string
    {
        $this->checkIfKeyExists($data, 'message');

        return (string) ($data['message']);
    }

    public function checkIfKeyExists(array $data, string $key): void
    {
        if (! array_key_exists($key, $data)) {
            throw new MissingNotificationKeyException($key);
        }
    }
}
