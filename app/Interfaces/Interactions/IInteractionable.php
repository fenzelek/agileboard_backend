<?php
declare(strict_types=1);

namespace App\Interfaces\Interactions;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface IInteractionable
{
    public function interactions(): MorphMany;
}
