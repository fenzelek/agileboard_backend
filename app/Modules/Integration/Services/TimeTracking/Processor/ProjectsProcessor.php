<?php

namespace App\Modules\Integration\Services\TimeTracking\Processor;

use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\TimeTracking\Project;
use Illuminate\Support\Collection;

class ProjectsProcessor
{
    /**
     * @var Project
     */
    protected $project;

    /**
     * ProjectsProcessor constructor.
     *
     * @param Project $project
     */
    public function __construct(Project $project)
    {
        $this->mappings = collect();
        $this->project = $project;
    }

    /**
     * Save projects.
     *
     * @param Integration $integration
     * @param Collection $projects
     *
     * @return Collection
     */
    public function save(Integration $integration, Collection $projects)
    {
        $projects->each(function ($project) use ($integration) {
            /** @var \App\Models\Other\Integration\TimeTracking\Project $project */
            $project_model = $this->project->where('integration_id', $integration->id)
                ->where('external_project_id', $project->getExternalId())->first();

            // if project already exists we will update its name
            if ($project_model) {
                $project_model->update([
                    'external_project_name' => $project->getExternalName(),
                ]);
            } else {
                // otherwise new project will be created
                $project_model = $this->project->create([
                    'integration_id' => $integration->id,
                    'project_id' => null,
                    'external_project_id' => $project->getExternalId(),
                    'external_project_name' => $project->getExternalName(),
                ]);
            }

            $this->mappings->put($project->getExternalId(), $project_model->id);
        });

        return $this->mappings;
    }
}
