<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\SprintController;

use App\Models\Db\Project;
use App\Models\Db\Sprint;
use App\Models\Db\Ticket;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\ProjectHelper;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use DatabaseTransactions;
    use ProjectHelper;

    private Project $project;

    /**
     * @test
     */
    public function export_WhenUserIsAdmin_ShouldReturnSuccess(): void
    {
        //Given
        $sprint = factory(Sprint::class)->create([
            'project_id' => $this->project->id,
        ]);
        factory(Ticket::class)->create([
            'sprint_id' => $sprint->id,
        ]);
        $url = route('projects.sprint.export', [
            'project' => $this->project->id,
            'sprint' => $sprint->id,
        ]);
        $this->setProjectRole($this->project, RoleType::ADMIN);

        //When
        $response = $this->get($url . '?selected_company_id=' . $this->project->company_id);

        //Then
        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function export_WhenUserIsClient_ShouldReturnUnauthorized(): void
    {
        //Given
        $sprint = factory(Sprint::class)->create([
            'project_id' => $this->project->id,
        ]);
        $url = route('projects.sprint.export', [
            'project' => $this->project->id,
            'sprint' => $sprint->id,
        ]);
        $this->setProjectRole($this->project, RoleType::CLIENT);

        //When
        $response = $this->get($url . '?selected_company_id=' . $this->project->company_id);

        //Then
        $response->assertStatus(401);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $this->project = factory(Project::class)->create(['company_id' => $company->id]);
    }
}
