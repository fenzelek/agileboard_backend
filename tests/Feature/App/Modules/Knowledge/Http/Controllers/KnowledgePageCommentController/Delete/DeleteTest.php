<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Knowledge\Http\Controllers\KnowledgePageCommentController\Delete;

use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseTransactions;
    use DeleteTrait;

    /**
     * @feature KnowledgePageComment
     * @scenario Delete comment
     * @case User authorized in project
     *
     * @dataProvider authorizedRoleDataProvider
     *
     * @test
     */
    public function destroy_WhenUserAuthorizedInProject_success(string $project_role_type): void
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::EMPLOYEE);
        $page = $this->createKnowledgePageInUserProject($this->user, $project_role_type, $company->id);
        $comment = $this->addComment($page);

        //WHEN
        $response = $this
            ->actingAs($this->user, 'api')
            ->json(
                'DELETE',
                route('knowledge-page-comment.destroy', [
                    'project' => $page->project_id,
                    'page_comment' => $comment->id,
                ]),
                [
                    'selected_company_id' => $company->id,
                ]
            );

        //THEN
        $response->assertOk();
        $this->assertDatabaseMissing('knowledge_page_comments', [
            'id' => $comment->id,
        ]);
    }

    /**
     * @feature KnowledgePageComment
     * @scenario Delete comment
     * @case User not authorized in project
     *
     * @dataProvider unauthorizedRoleDataProvider
     *
     * @test
     */
    public function destroy_WhenUserNotAuthorizedInProject_unauthorized(string $project_role_type): void
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::EMPLOYEE);
        $page = $this->createKnowledgePageInUserProject($this->user, $project_role_type, $company->id);
        $comment = $this->addComment($page);

        //WHEN
        $response = $this
            ->actingAs($this->user, 'api')
            ->json(
                'DELETE',
                route('knowledge-page-comment.destroy', [
                    'project' => $page->project_id,
                    'page_comment' => $comment->id,
                ]),
                [
                    'selected_company_id' => $company->id,
                ]
            );

        //THEN
        $response->assertUnauthorized();
    }
}
