<?php

namespace App\Modules\Integration\Services\TimeTracking\NoteMatcher\Contracts;

use Illuminate\Support\Collection;

interface Matcher
{
    /**
     * Match activities with notes.
     *
     * @return Collection[\App\Models\Other\Integration\TimeTracking\Activity]
     */
    public function match();
}
