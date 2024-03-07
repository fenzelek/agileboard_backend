<?php

declare(strict_types=1);

namespace App\Interfaces\Involved;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface IHasInvolved
{
    public function involved(): MorphMany;

    public function getSourceType(): string;

    public function getSourceId(): int;
}
