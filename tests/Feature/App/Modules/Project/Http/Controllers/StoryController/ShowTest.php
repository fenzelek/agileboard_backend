<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\StoryController;

use App\Helpers\ErrorCode;
use App\Models\Db\Story;
use App\Models\Other\RoleType;
use App\Models\Db\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\ProjectHelper;
use Tests\BrowserKitTestCase;

class ShowTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ProjectHelper;

    /**
     * @var User
     */
    protected $user;

    public function setUp():void
    {
        parent::setUp();
        $this->user = $this->createUser()->user;
    }

    /** @test */
    public function project_not_exists_return_error_404()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);

        /* **************** prepare data  ********************/
        $story = $this->prepareStory();

        /* **************** send request  ********************/
        $this->get('/projects/999/stories/' . $story->id . '?selected_company_id=' .
            $company->id);

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function project_not_belongs_to_company_return_error_401()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        /* **************** prepare data  ********************/
        $story = $this->prepareStory();

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id . '/stories/' . $story->id .
            '?selected_company_id=999');

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function project_not_belongs_to_user_return_error_401()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::DEVELOPER);
        $fake_user = factory(User::class)->create();
        $project = $this->getProject($company, $fake_user);

        /* **************** prepare data  ********************/
        $story = $this->prepareStory();

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id . '/stories/' . $story->id .
            '?selected_company_id=' . $company->id);

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function story_not_belongs_to_project_return_error_401()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $fake_user = factory(User::class)->create();
        $project = $this->getProject($company, $fake_user);

        /* **************** prepare data  ********************/
        $story = $this->prepareStory(2);

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id . '/stories/' . $story->id .
            '?selected_company_id=' . $company->id);

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function it_returns_story_when_everything_is_fine_for_owner()
    {
        $this->verifyStoryForRole(RoleType::OWNER);
    }

    /** @test */
    public function it_returns_story_when_everything_is_fine_for_admin()
    {
        $this->verifyStoryForRole(RoleType::ADMIN);
    }

    /** @test */
    public function it_returns_story_when_everything_is_fine_for_dealer()
    {
        $this->verifyStoryForRole(RoleType::DEALER);
    }

    /** @test */
    public function it_returns_story_when_everything_is_fine_for_developer()
    {
        $this->verifyStoryForRole(RoleType::DEVELOPER);
    }

    /** @test */
    public function it_returns_story_when_everything_is_fine_for_client()
    {
        $this->verifyStoryForRole(RoleType::CLIENT);
    }

    protected function verifyStoryForRole($role_slug)
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole($role_slug);
        $project = $this->getProject($company, $this->user->id, $role_slug);

        /* **************** prepare data  ********************/
        $story = $this->prepareStory($project->id);

        $this->get('/projects/' . $project->id . '/stories/' . $story->id .
            '?selected_company_id=' . $company->id)->seeStatusCode(200);

        /* **************** assertions  ********************/
        $this->assertEquals($story->fresh()->toArray(), $this->decodeResponseJson()['data']);
    }

    /**
     * Create story in database.
     *
     * @param int $project_id
     *
     * @return mixed
     */
    protected function prepareStory($project_id = 1, $priority = 1)
    {
        return factory(Story::class)->create([
            'project_id' => $project_id,
            'name' => 'Example story - phpunit',
            'priority' => $priority,
        ]);
    }
}
