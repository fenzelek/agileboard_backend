<?php

namespace App\Interfaces\Interactions;

interface INotificationPingDTO
{
    public function getAuthorId(): int;

    public function getProjectId(): int;

    public function getEventType(): string;

    public function getActionType(): string;

    public function getSourceType(): string;

    public function getSourceId(): int;

    public function getRef(): ?string;

    public function getMessage(): ?string;

    public function getSelectedCompanyId(): int;

    public function getRecipientId(): int;
}
