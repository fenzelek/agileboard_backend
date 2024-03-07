<?php

namespace Tests\Helpers;

use App\Models\Db\User;
use App\Models\Db\Project;
use Carbon\Carbon;
use Illuminate\Support\Collection as SupportCollection;

trait CreateProjects
{
    protected function verifyProjects(SupportCollection $base, SupportCollection $projects)
    {
        $this->assertCount($base->count(), $projects);

        $this->assertEquals($base->pluck('id'), $projects->pluck('id'));
        $this->assertEquals($base->pluck('company_id'), $projects->pluck('company_id'));
        $this->assertEquals($base->pluck('name'), $projects->pluck('name'));
        $this->assertEquals($base->pluck('short_name'), $projects->pluck('short_name'));
        $this->assertEquals($base->pluck('time_tracking_visible_for_clients'), $projects->pluck('time_tracking_visible_for_clients'));
        $this->assertEquals($base->pluck('language'), $projects->pluck('language'));
        $this->assertEquals($base->pluck('color'), $projects->pluck('color'));
        $this->assertEquals($base->pluck('closed_at'), $projects->pluck('closed_at'));
        $this->assertEquals($base->pluck('created_at'), $projects->pluck('created_at'));
        $this->assertEquals($base->pluck('updated_at'), $projects->pluck('updated_at'));
        $this->assertEmpty($projects->where('deleted_at', $this->now->toDateTimeString()));
    }

    protected function projectForUser($role, $filter = null)
    {
        $this->createProjectsForUser($this->new_company->id, $this->user);
        if ($role == 'developer') {
            $this->createProjectsForUser($this->company->id, $this->user);
            $projects = $this->createProjectsForUser($this->company->id, $this->developer);

            return $projects->where('closed_at', null)
                ->where('deleted_at', null);
        }
        if ($role == 'admin') {
            $projects = $this->createProjectsForUser($this->company->id, $this->user);
            $temp = $this->createProjectsForUser($this->company->id, $this->developer);

            if ($filter == 'opened') {
                return $projects->merge($temp)->where('closed_at', null)
                    ->where('deleted_at', null)->sortBy('id');
            }
            if ($filter == 'closed') {
                return $projects->merge($temp)->where('closed_at', $this->now->toDateTimeString())
                    ->where('deleted_at', null)->sortBy('id');
            }

            return $projects->merge($temp)->where('deleted_at', null)->sortBy('id');
        }
    }

    protected function createProjectsForUser($company_id, User $user)
    {
        $projects = factory(Project::class, 2)->create(['company_id' => $company_id]);
        $temp = factory(Project::class, 2)->create([
            'company_id' => $company_id,
            'closed_at' => Carbon::now()->toDateTimeString(),
        ]);
        $projects = $projects->merge($temp);

        $temp = factory(Project::class, 2)->create([
            'company_id' => $company_id,
            'closed_at' => Carbon::now()->toDateTimeString(),
            'deleted_at' => Carbon::now()->toDateTimeString(),
        ]);
        $projects = $projects->merge($temp);

        foreach ($projects as $project) {
            $project->users()->attach($user);
        }

        return $projects;
    }

    protected function getStructure()
    {
        return [
            'data' => [
                [
                    'id',
                    'company_id',
                    'name',
                    'short_name',
                    'closed_at',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ],
            ],
            'meta' => [
                'pagination' => [
                    'total',
                    'count',
                    'per_page',
                    'current_page',
                    'total_pages',
                    'links',
                ],
            ],
        ];
    }
}
