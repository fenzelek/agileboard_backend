<?php

namespace App\Modules\Integration\Services\ManualActivityTools;

use App\Models\Db\Integration\TimeTracking\ManualActivityHistory;
use App\Models\Db\User;
use App\Modules\Integration\Services\Contracts\ManualActivityDataProvider;

class ManualActivityLogger
{
    private ManualActivityHistory $logger;

    public function __construct(ManualActivityHistory $logger)
    {
        $this->logger = $logger;
    }

    public function log(ManualActivityDataProvider $activity_data_provider, User $user_author): ManualActivityHistory
    {
        $logger = $this->logger->newInstance();
        $logger->user_id = $activity_data_provider->getUserId();
        $logger->author_id = $user_author->id;
        $logger->project_id = $activity_data_provider->getProjectId();
        $logger->ticket_id = $activity_data_provider->getTicketId();
        $logger->from = $activity_data_provider->getFrom();
        $logger->to = $activity_data_provider->getTo();

        $logger->save();

        return $logger;
    }
}
