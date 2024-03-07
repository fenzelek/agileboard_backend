<?php

declare(strict_types=1);

namespace App\Modules\Interaction\Contracts;

interface IInteractionManager
{
    public function addNotifications(IInteractionDTO $interaction): void;
}
