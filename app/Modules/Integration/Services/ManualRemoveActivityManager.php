<?php

namespace App\Modules\Integration\Services;

use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\IntegrationProvider;
use App\Modules\Integration\Exceptions\InvalidIdsException;
use App\Modules\Integration\Exceptions\InvalidManualIntegrationForCompany;
use App\Modules\Integration\Services\Contracts\ManualActivityValidator;
use App\Modules\Integration\Services\Contracts\RemoveActivityProvider;
use App\Modules\Integration\Services\ManualActivityTools\ManualActivityRemove;
use Illuminate\Database\Connection;
use Psr\Log\LoggerInterface;

class ManualRemoveActivityManager
{
    protected Connection $db;
    private Integration $integration;
    private LoggerInterface $logger;
    private ManualActivityRemove $activity_remover;
    private ManualActivityValidator $activity_validator;

    /**
     * @param Connection $db
     * @param Integration $integration
     * @param LoggerInterface $logger
     * @param ManualActivityRemove $activity_remover
     */
    public function __construct(Connection $db, Integration $integration, LoggerInterface $logger, ManualActivityRemove $activity_remover, ManualActivityValidator $activity_validator)
    {
        $this->db = $db;
        $this->integration = $integration;
        $this->logger = $logger;
        $this->activity_remover = $activity_remover;
        $this->activity_validator = $activity_validator;
    }

    /**
     * @param RemoveActivityProvider $activity_data_provider
     *
     * @return array
     * @throws InvalidManualIntegrationForCompany
     * @throws \Throwable
     */
    public function removeActivities(RemoveActivityProvider $activity_data_provider): array
    {
        if (! $this->findIntegration($activity_data_provider->getCompanyId())) {
            throw new InvalidManualIntegrationForCompany();
        }

        if (! $this->activity_validator->validate($activity_data_provider)) {
            throw new InvalidIdsException();
        }

        try {
            $this->db->transaction(function () use ($activity_data_provider) {
                $this->activity_remover->remove($activity_data_provider);
            });
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), [$activity_data_provider->getActivitiesIds()]);

            return $activity_data_provider->getActivitiesIds();
        }

        return [];
    }

    /**
     * @return mixed
     */
    private function findIntegration(int $company_id)
    {
        $integration = $this->integration->newQuery()
            ->byTimeTracker(IntegrationProvider::MANUAL)
            ->byCompanyId($company_id)
            ->first();

        return $integration;
    }
}
