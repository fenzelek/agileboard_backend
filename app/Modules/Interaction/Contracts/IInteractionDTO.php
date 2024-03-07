<?php

declare(strict_types=1);

namespace App\Modules\Interaction\Contracts;

use Illuminate\Support\Collection;

interface IInteractionDTO
{
    public function getAuthorId(): int;

    public function getProjectId(): int;

    public function getEventType(): string;

    public function getActionType(): string;

    public function getSourceType(): string;

    public function getSourceId(): int;

    public function getCompanyId(): int;

    public function getInteractionPings(): Collection;
}
