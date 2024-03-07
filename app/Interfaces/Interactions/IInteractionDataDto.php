<?php

namespace App\Interfaces\Interactions;

interface IInteractionDataDto
{
    public function getActionType(): string;
    public function getInteractionEventType(): string;
    public function getUserId(): int;
    public function getProjectId(): int;
}
