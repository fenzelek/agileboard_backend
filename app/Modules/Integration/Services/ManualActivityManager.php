<?php

namespace App\Modules\Integration\Services;

use App\Models\Db\Integration\IntegrationProvider;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Integration\Integration;
use App\Models\Db\User;
use App\Modules\Integration\Exceptions\InvalidManualActivityTimePeriod;
use App\Modules\Integration\Exceptions\InvalidManualIntegrationForCompany;
use App\Modules\Integration\Services\Contracts\ManualActivityDataProvider;
use App\Modules\Integration\Services\ManualActivityTools\ManualActivityLogger;
use App\Modules\Integration\Services\ManualActivityTools\ManualActivityTimeConverter;
use App\Modules\Integration\Services\ManualActivityTools\ManualActivityTimeValidator;
use Illuminate\Database\Connection;
use Psr\Log\LoggerInterface;

class ManualActivityManager
{
    protected Connection $db;
    protected LoggerInterface $logger;
    private ManualActivityLogger $history_logger;
    private ManualActivityTimeValidator $activity_time_validator;
    private ManualActivityTimeConverter $activity_time_converter;
    private Integration $integration;

    public function __construct(ManualActivityLogger $history_logger, Connection $db, ManualActivityTimeValidator $activity_time_validator, ManualActivityTimeConverter $activity_time_converter, Integration $integration, LoggerInterface $logger)
    {
        $this->history_logger = $history_logger;
        $this->db = $db;
        $this->activity_time_validator = $activity_time_validator;
        $this->activity_time_converter = $activity_time_converter;
        $this->integration = $integration;
        $this->logger = $logger;
    }

    /**
     * @param ManualActivityDataProvider $activity_data_provider
     * @param User $user_author
     *
     * @return Activity[]
     * @throws InvalidManualActivityTimePeriod
     * @throws InvalidManualIntegrationForCompany|\Throwable
     */
    public function addActivity(ManualActivityDataProvider $activity_data_provider, User $user_author): array
    {
        $activities = [];
        if (! $this->activity_time_validator->check($activity_data_provider)) {
            throw new InvalidManualActivityTimePeriod();
        }

        $integration = $this->findIntegration($user_author);
        if (empty($integration)) {
            throw new InvalidManualIntegrationForCompany();
        }

        try {
            return $this->db->transaction(function () use ($integration, $activity_data_provider, $user_author) {
                $history = $this->history_logger->log($activity_data_provider, $user_author);

                $activities =
                    $this->activity_time_converter->convert(
                        $activity_data_provider,
                        $integration,
                        $history
                    );

                if (! count($activities)) {
                    $history->delete();
                }

                return $activities;
            });
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return $activities;
        }
    }

    /**
     * @param User $user
     *
     * @return mixed
     */
    private function findIntegration(User $user)
    {
        $company_id = $user->getSelectedCompanyId();
        $integration = $this->integration->newQuery()
            ->byTimeTracker(IntegrationProvider::MANUAL)
            ->byCompanyId($company_id)
            ->first();

        return $integration;
    }
}
