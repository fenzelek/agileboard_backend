<?php

namespace App\Modules\Integration\Services;

use App\Models\Db\Company;
use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\IntegrationProvider;
use App\Models\Db\TimeTracker\Frame;
use App\Modules\Integration\Services\TimeTrackerFrames\FrameActivityMaker;
use App\Modules\Integration\Services\TimeTrackerFrames\FrameManager;
use Illuminate\Database\Connection;
use Psr\Log\LoggerInterface;

class TimeTrackerFrameIntegrator
{
    protected FrameManager $frame_manager;
    protected LoggerInterface $logger;
    protected Connection $db;
    private FrameActivityMaker $frame_activity_maker;
    private Integration $integration;

    public function __construct(FrameActivityMaker $frame_activity_maker, Integration $integration, LoggerInterface $logger, Connection $db, FrameManager $frame_manager)
    {
        $this->integration = $integration;
        $this->frame_activity_maker = $frame_activity_maker;
        $this->logger = $logger;
        $this->db = $db;
        $this->frame_manager = $frame_manager;
    }

    public function addActivity(Frame $frame): void
    {
        $frame = $this->frame_manager->setCounterChecksOf($frame);

        $company = $frame->getCompany();
        if (empty($company)) {
            return;
        }

        $integration = $this->findIntegration($company);
        if (empty($integration)) {
            return;
        }

        try {
            $this->db->transaction(function () use ($frame, $integration) {
                $this->frame_activity_maker->addActivity($frame, $integration);
            });
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $frame->toArray());
        }
    }

    /**
     * @param Company $company
     *
     * @return mixed
     */
    private function findIntegration(Company $company)
    {
        $integration = $this->integration->newQuery()
            ->byTimeTracker(IntegrationProvider::TIME_TRACKER)
            ->byCompanyId($company->id)
            ->first();

        return $integration;
    }
}
