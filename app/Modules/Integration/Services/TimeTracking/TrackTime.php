<?php

namespace App\Modules\Integration\Services\TimeTracking;

use App\Models\Db\Company;
use App\Models\Db\Integration\Integration;
use App\Models\Other\ModuleType;
use App\Modules\Integration\Services\Factory;
use App\Modules\Integration\Services\TimeTracking\Processor\ActivitiesProcessor;
use App\Modules\Integration\Services\TimeTracking\Processor\NotesProcessor;
use App\Modules\Integration\Services\TimeTracking\Processor\ProjectsProcessor;
use App\Modules\Integration\Services\TimeTracking\Processor\UsersProcessor;
use Exception;
use Illuminate\Support\Collection;
use DB;

class TrackTime
{
    /**
     * @var Integration
     */
    protected $integration;

    /**
     * Time tracking handler (for example Hubstaff, Upwork etc.).
     *
     * @var TimeTracking|null
     */
    protected $handler;

    /**
     * Project mappings.
     *
     * @var Collection|null
     */
    protected $project_mappings;

    /**
     * User mappings.
     *
     * @var Collection|null
     */
    protected $user_mappings;

    /**
     * @var ActivitiesProcessor
     */
    protected $activities_processor;

    /**
     * @var NotesProcessor
     */
    protected $notes_processor;

    /**
     * @var ProjectsProcessor
     */
    protected $projects_processor;

    /**
     * @var UsersProcessor
     */
    protected $users_processor;

    /**
     * TrackTime constructor.
     *
     * @param ActivitiesProcessor $activities_processor
     * @param NotesProcessor $notes_processor
     * @param ProjectsProcessor $projects_processor
     * @param UsersProcessor $users_processor
     */
    public function __construct(
        ActivitiesProcessor $activities_processor,
        NotesProcessor $notes_processor,
        ProjectsProcessor $projects_processor,
        UsersProcessor $users_processor
    ) {
        $this->activities_processor = $activities_processor;
        $this->notes_processor = $notes_processor;
        $this->projects_processor = $projects_processor;
        $this->users_processor = $users_processor;

        $this->initializeMappings();
    }

    /**
     * Fetch all data for integration.
     *
     * @param Integration $integration
     */
    public function fetch(Integration $integration)
    {
        $company = Company::find($integration->company_id);

        if (! $company || ! $company->appSettings(ModuleType::PROJECTS_INTEGRATIONS_HUBSTAFF)) {
            return false;
        }

        $this->setIntegrationAndHandler($integration);

        // if it's not ready to run, we have nothing to do
        if (! $this->handler->isReadyToRun()) {
            return;
        }

        $this->initializeMappings();

        //  fetch and save all the data
        $this->fetchProjects()
            ->fetchUsers()
            ->fetchNotes()
            ->fetchActivities();
    }

    /**
     * Set integration.
     *
     * @param Integration $integration
     *
     * @return $this
     */
    public function setIntegration(Integration $integration)
    {
        $this->setIntegrationAndHandler($integration);

        return $this;
    }

    /**
     * Verify whether integration data is correct. When verifying it will save projects and users to
     * have them immediately available for any other usage.
     *
     * @param Integration $integration
     *
     * @return bool
     */
    public function verify(Integration $integration)
    {
        $this->setIntegrationAndHandler($integration);

        try {
            $this->fetchProjects()->fetchUsers();
        } catch (Exception $e) {
            // we don't log here any error. This function is only to verify if data is correct
            // so we assume sometimes they won't be
            return false;
        }

        return true;
    }

    /**
     * Get projects from handler, save them and save additional information.
     *
     * @param Integration $integration
     *
     * @return $this
     */
    public function fetchProjects(Integration $integration = null)
    {
        if ($integration) {
            $this->setIntegrationAndHandler($integration);
        }

        DB::transaction(function () {
            $this->saveProjects($this->handler->projects())->saveInfo();
        });

        return $this;
    }

    /**
     * Get users from handler, save them and save additional information.
     *
     * @return $this
     */
    public function fetchUsers()
    {
        DB::transaction(function () {
            $this->saveUsers($this->handler->users())->saveInfo();
        });

        return $this;
    }

    /**
     * Get notes from handler, save them and save additional information.
     *
     * @return $this
     */
    public function fetchNotes()
    {
        DB::transaction(function () {
            $this->saveNotes($this->handler->notes())->saveInfo();
        });

        return $this;
    }

    /**
     * Get activities from handler, save them and save additional information.
     *
     * @return $this
     */
    public function fetchActivities()
    {
        DB::transaction(function () {
            $this->saveActivities($this->handler->activities())->saveInfo();
        });

        return $this;
    }

    /**
     * Initialize mappings.
     */
    protected function initializeMappings()
    {
        $this->project_mappings = collect();
        $this->user_mappings = collect();
    }

    /**
     * Set integration and integration handler.
     *
     * @param Integration $integration
     */
    protected function setIntegrationAndHandler(Integration $integration)
    {
        $this->integration = $integration;
        $this->handler = Factory::make(
            $integration->provider,
            $integration->settings,
            $integration->info
        );
    }

    /**
     * Save integration information data.
     */
    protected function saveInfo()
    {
        $this->integration->update(['info' => $this->handler->getInfo()]);
    }

    /**
     * Save projects into database.
     *
     * @param Collection $projects
     *
     * @return $this
     */
    protected function saveProjects(Collection $projects)
    {
        $this->project_mappings = $this->projects_processor->save($this->integration, $projects);

        return $this;
    }

    /**
     * Save users into database.
     *
     * @param Collection $users
     *
     * @return $this
     */
    protected function saveUsers(Collection $users)
    {
        $this->user_mappings = $this->users_processor->save($this->integration, $users);

        return $this;
    }

    /**
     * Save notes into database.
     *
     * @param Collection $notes
     *
     * @return $this
     */
    protected function saveNotes(Collection $notes)
    {
        $this->notes_processor->save($this->integration, $notes);

        return $this;
    }

    /**
     * Save activities into database.
     *
     * @param Collection $activities
     *
     * @return $this
     */
    protected function saveActivities(Collection $activities)
    {
        $this->activities_processor->save(
            $this->integration,
            $activities,
            $this->user_mappings,
            $this->project_mappings
        );

        return $this;
    }
}
