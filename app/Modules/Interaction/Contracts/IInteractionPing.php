<?php

declare(strict_types=1);

namespace App\Modules\Interaction\Contracts;

interface IInteractionPing
{
    public function getRecipientId(): int;

    public function getNotifiable(): string;

    public function getRef(): ?string;

    public function getMessage(): ?string;
}
