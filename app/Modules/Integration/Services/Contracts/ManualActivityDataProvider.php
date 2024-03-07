<?php

namespace App\Modules\Integration\Services\Contracts;

use Illuminate\Support\Carbon;

interface ManualActivityDataProvider
{
    public function getUserId(): int;

    public function getTicketId(): int;

    public function getProjectId(): int;

    public function getFrom(): Carbon;

    public function getTo(): Carbon;

    public function getComment(): ?string;
}
