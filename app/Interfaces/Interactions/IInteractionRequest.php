<?php
declare(strict_types=1);

namespace App\Interfaces\Interactions;

use App\Modules\Interaction\Contracts\IInteractionPing;
use Illuminate\Support\Collection;

interface IInteractionRequest
{
    /**
     * @return IInteractionPing[]|Collection
     */
    public function getInteractionPings(): Collection;

    public function getSelectedCompanyId(): int;
}
