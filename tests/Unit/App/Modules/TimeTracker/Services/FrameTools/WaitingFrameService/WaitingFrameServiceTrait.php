<?php

namespace Tests\Unit\App\Modules\TimeTracker\Services\FrameTools\WaitingFrameService;

use App\Models\Db\Company;
use App\Models\Db\Integration\IntegrationProvider;
use App\Models\Db\Package;
use App\Models\Db\Project;
use App\Models\Db\TimeTracker\Frame;
use App\Models\Other\RoleType;

trait WaitingFrameServiceTrait
{
    protected function prepareProject($company)
    {
        $project = factory(Project::class)->create([
            'company_id' => $company->id,
        ]);
        $project->users()->attach($this->user->id);

        return $project;
    }

    protected function prepareCompany()
    {
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::CEP_FREE);
        $company->roles()->attach([4]);

        return $company;
    }

    protected function prepareIntegration(Company $company)
    {
        return  $company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::TIME_TRACKER)->id,
        ]);
    }

    protected function createWaitingFrame($from, $to, $user, $project, int $counter = 0, $transformed = false)
    {
        $frame = factory(Frame::class)->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'counter_сhecks' => $counter,
            'transformed' => $transformed,
            'from' => $from,
            'to' => $to,
            'activity' => 100,
        ]);

        $frame->user()->associate($this->user);
    }

    protected function createWaitingFrames($from, $to, $project)
    {
        $frame = factory(Frame::class)->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'from' => $from,
            'to' => $to,
            'activity' => 100,
        ]);

        $frame->user()->associate($this->user);

        $frame = factory(Frame::class)->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'from' => $from + 1000,
            'to' => $to + 1000,
            'activity' => 70,
        ]);

        $frame->user()->associate($this->user);
    }

    protected function createWaitingFrameHasNoCompany($from, $to)
    {
        factory(Frame::class)->create([
            'from' => $from + 1000,
            'to' => $to + 1000,
            'activity' => 70,
        ]);
    }
}
