<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Knowledge\Http\Controllers\KnowledgePageCommentController\Store;

use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\Project;
use App\Models\Other\Interaction\NotifiableType;
use App\Models\Other\KnowledgePageCommentType;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use DatabaseTransactions;
    use StoreTrait;

    /**
     * @feature KnowledgePageComment
     * @scenario Store new comment
     * @case User is authorized in project
     *
     * @dataProvider authorizedRoleDataProvider
     *
     * @test
     */
    public function store_WhenAuthorizedInProject_Success(string $project_role_type): void
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::EMPLOYEE);
        $page = $this->createKnowledgePageInUserProject($this->user, $project_role_type, $company->id);
        $request_data = $this->prepareRequestData($company->id);

        //WHEN
        $response = $this
            ->actingAs($this->user, 'api')
            ->json(
                'POST',
                route('knowledge-page-comment.store', [
                    'project' => $page->project_id,
                    'page' => $page->id,
                ]),
                $request_data
            );

        //THEN
        $response->assertStatus(201);
        $this->assertDatabaseCount('interactions', 1);
        $this->assertDatabaseCount('notifications', 1);
    }

    /**
     * @feature KnowledgePageComment
     * @scenario Store new comment
     * @case User not in project
     *
     * @test
     */
    public function store_WhenNotInProject_Unauthorized(): void
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::DEVELOPER);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $page = factory(KnowledgePage::class)->create(['project_id' => $project->id, 'creator_id' => $this->user->id]);

        $request_data = [
            'selected_company_id' => $company->id,
            'type' => KnowledgePageCommentType::GLOBAL,
            'text' => 'Some comment',
            'ref' => 'Ref',
        ];

        //WHEN
        $response = $this
            ->actingAs($this->user, 'api')
            ->json(
                'POST',
                route('knowledge-page-comment.store', [
                    'project' => $page->project_id,
                    'page' => $page->id,
                ]),
                $request_data
            );

        $response->assertUnauthorized();
    }

    /**
     * @feature KnowledgePageComment
     * @scenario Store new comment
     * @case User in project and does not have role
     *
     * @dataProvider unauthorizedRoleDataProvider
     * @test
     */
    public function store_WhenInProjectAndNotAuthorizedRole_Unauthorized(string $project_role_type): void
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::EMPLOYEE);
        $page = $this->createKnowledgePageInUserProject($this->user, $project_role_type, $company->id);
        $request_data = [
             'selected_company_id' => $company->id,
             'type' => KnowledgePageCommentType::GLOBAL,
             'text' => 'Some comment',
             'ref' => 'Ref',
         ];

        //WHEN
        $response = $this
             ->actingAs($this->user, 'api')
             ->json(
                 'POST',
                 route('knowledge-page-comment.store', [
                     'project' => $page->project_id,
                     'page' => $page->id,
                 ]),
                 $request_data
             );

        //THEN
        $response->assertUnauthorized();
    }
}
