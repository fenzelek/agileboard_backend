<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\StoryController\Destroy;

use App\Helpers\ErrorCode;
use App\Models\Db\Project;
use App\Models\Db\Story;
use App\Models\Other\RoleType;
use App\Models\Db\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\ProjectHelper;
use Tests\BrowserKitTestCase;

class ValidationDestroyTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ProjectHelper;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var Story
     */
    protected $story;

    public function setUp():void
    {
        parent::setUp();
        $this->user = $this->createUser()->user;
        $this->story = factory(Story::class)->create();
    }

    /** @test */
    public function project_not_exists_return_error_404()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);

        /* **************** send request  ********************/
        $this->delete('/projects/999/stories/' . $this->story->id . '?selected_company_id=' .
            $company->id);

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function user_is_not_belongs_to_user_return_error_401()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        /* **************** send request  ********************/
        $this->delete('/projects/' . $project->id . '/stories/' . $this->story->id .
            '?selected_company_id=999');

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function project_is_not_belongs_to_user_return_error_401()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::DEVELOPER);
        $fake_user = factory(User::class)->create();
        $project = $this->getProject($company, $fake_user);

        /* **************** send request  ********************/
        $this->delete('/projects/' . $project->id . '/stories/' . $this->story->id .
            '?selected_company_id=' . $company->id);

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function story_is_not_belongs_to_project_return_error_401()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::DEVELOPER);
        $project = $this->getProject($company, $this->user->id);

        /* **************** prepare data  ********************/
        $fake_project = factory(Project::class)->create();
        $story = factory(Story::class)->create([
            'project_id' => $fake_project->id,
        ]);

        /* **************** send request  ********************/
        $this->delete('/projects/' . $project->id . '/stories/' . $story->id .
            '?selected_company_id=' . $company->id);

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }
}
