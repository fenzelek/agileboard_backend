<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Knowledge\Http\Controllers\KnowledgePageCommentController\Update;

use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\Project;
use App\Models\Other\Interaction\NotifiableType;
use App\Models\Other\KnowledgePageCommentType;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseTransactions;
    use UpdateTrait;

    /**
     * @feature KnowledgePageComment
     * @scenario Update comment
     * @case User is authorized in project
     *
     * @dataProvider authorizedRoleDataProvider
     *
     * @test
     */
    public function update_WhenAuthorizedInProject_Success(string $project_role_type): void
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::EMPLOYEE);
        $page = $this->createKnowledgePageInUserProject($this->user, $project_role_type, $company->id);
        $comment = $this->addComment($page);
        $request_data = $this->prepareRequest($company->id);

        //WHEN
        $response = $this
            ->actingAs($this->user, 'api')
            ->json(
                'PUT',
                route('knowledge-page-comment.update', [
                    'project' => $page->project_id,
                    'page_comment' => $comment->id,
                ]),
                $request_data
            );

        //THEN
        $response->assertOk();
        $this->assertDatabaseCount('notifications', 1);
    }

    /**
     * @feature KnowledgePageComment
     * @scenario Update comment
     * @case User not in project
     *
     * @test
     */
    public function update_WhenNotInProject_Unauthorized(): void
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::EMPLOYEE);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $page = factory(KnowledgePage::class)->create(['project_id' => $project->id, 'creator_id' => $this->user->id]);
        $comment = $this->addComment($page);

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
                'PUT',
                route('knowledge-page-comment.update', [
                    'project' => $page->project_id,
                    'page_comment' => $comment->id,
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
    public function update_WhenInProjectAndNotAuthorizedRole_Unauthorized(string $project_role_type): void
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::EMPLOYEE);
        $page = $this->createKnowledgePageInUserProject($this->user, $project_role_type, $company->id);
        $comment = $this->addComment($page);
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
                'PUT',
                route('knowledge-page-comment.update', [
                    'project' => $page->project_id,
                    'page_comment' => $comment->id,
                ]),
                $request_data
            );

        //THEN
        $response->assertUnauthorized();
    }
}
