<?php

namespace App\Modules\Integration\Services\TimeTracking\Contracts;

use App\Modules\Integration\Services\Contracts\Integration;
use Illuminate\Support\Collection;

interface TimeTracking extends Integration
{
    /**
     * Get projects.
     *
     * @return Collection[\App\Models\Other\Integration\TimeTracking\Project]
     */
    public function projects();

    /**
     * Get users.
     *
     * @return Collection[\App\Models\Other\Integration\TimeTracking\User]
     */
    public function users();

    /**
     * Get notes.
     *
     * @return Collection[\App\Models\Other\Integration\TimeTracking\Note]
     */
    public function notes();

    /**
     * Get activities.
     *
     * @return Collection[\App\Models\Other\Integration\TimeTracking\Activity]
     */
    public function activities();

    /**
     * Verify whether it should already track time.
     *
     * @return bool
     */
    public function isReadyToRun();
}
