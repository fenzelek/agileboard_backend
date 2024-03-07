<?php
declare(strict_types=1);

namespace App\Modules\Interaction\Contracts;

use Illuminate\Support\Collection;

interface IUsersGroupMembers
{
    public function get(IInteractionDTO $interaction): Collection;
}