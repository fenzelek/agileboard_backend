<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\StoryController\Update;

use App\Helpers\ErrorCode;
use App\Models\Db\File;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\Project;
use App\Models\Db\Story;
use App\Models\Db\Ticket;
use App\Models\Other\RoleType;
use App\Models\Db\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\ProjectHelper;
use Tests\BrowserKitTestCase;

class ValidationUpdateTest extends BrowserKitTestCase
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
        $this->put('/projects/999/stories/' . $story->id . '?selected_company_id=' .
            $company->id);

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function project_is_not_belongs_to_company_return_error_401()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        /* **************** prepare data  ********************/
        $story = $this->prepareStory();

        /* **************** send request  ********************/
        $this->put('/projects/' . $project->id . '/stories/' . $story->id .
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

        /* **************** prepare data  ********************/
        $story = $this->prepareStory();

        /* **************** send request  ********************/
        $this->put('/projects/' . $project->id . '/stories/' . $story->id .
            '?selected_company_id=' . $company->id);

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function story_is_not_belongs_to_project_return_error_401()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $fake_user = factory(User::class)->create();
        $project = $this->getProject($company, $fake_user);

        /* **************** prepare data  ********************/
        $story = $this->prepareStory(2);

        /* **************** send request  ********************/
        $this->put('/projects/' . $project->id . '/stories/' . $story->id .
            '?selected_company_id=' . $company->id);

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function invalid_empty_name_return_error_422()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        /* **************** prepare data  ********************/
        $story = $this->prepareStory($project->id);

        /* **************** send request  ********************/
        $data = [
            'name' => '',
            'priority' => 1,
            'files' => [],
            'tickets' => [],
            'knowledge_pages' => [],
        ];

        $this->put('/projects/' . $project->id . '/stories/' . $story->id .
            '?selected_company_id=' . $company->id, $data);

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'name',
        ]);
    }

    /** @test */
    public function invalid_to_long_name_return_error_422()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        /* **************** prepare data  ********************/
        $story = $this->prepareStory($project->id);

        /* **************** send request  ********************/
        $data = [
            'name' => 'Example story - phpunitphpunitphpunitphpunitphpunitphpunitphpunitphpunit
            phpunitphpunitphpunitphpunitphpunitphpunitphpunitphpunitphpunitphpunitphpunitphpunit
            phpunitphpunitphpunitphpunitphpunitphpunitphpunitphpunitphpunitphpunitphpunitphpunit
            phpunitphpunitphpunit',
            'priority' => 1,
            'files' => [],
            'tickets' => [],
            'knowledge_pages' => [],
        ];

        $this->put('/projects/' . $project->id . '/stories/' . $story->id .
            '?selected_company_id=' . $company->id, $data);

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'name',
        ]);
    }

    /** @test */
    public function invalid_unique_name_return_error_422()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        /* **************** prepare data  ********************/
        $this->prepareStory($project->id);
        $story = $this->prepareStory($project->id);

        /* **************** send request  ********************/
        $data = [
            'name' => 'Example story - phpunit',
            'priority' => 1,
            'files' => [],
            'tickets' => [],
            'knowledge_pages' => [],
        ];

        $this->put('/projects/' . $project->id . '/stories/' . $story->id .
            '?selected_company_id=' . $company->id, $data);

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'name',
        ]);
    }

    /** @test */
    public function invalid_empty_priority_return_error_422()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        /* **************** prepare data  ********************/
        $story = $this->prepareStory($project->id);

        /* **************** send request  ********************/
        $data = [
            'name' => 'Example story - phpunit',
            'priority' => '',
            'files' => [],
            'tickets' => [],
            'knowledge_pages' => [],
        ];

        $this->put('/projects/' . $project->id . '/stories/' . $story->id .
            '?selected_company_id=' . $company->id, $data);

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'priority',
        ]);
    }

    /** @test */
    public function invalid_string_priority_return_error_422()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        /* **************** prepare data  ********************/
        $story = $this->prepareStory($project->id);

        /* **************** send request  ********************/
        $data = [
            'name' => 'Example story - phpunit',
            'priority' => 'phpunit',
            'files' => [],
            'tickets' => [],
            'knowledge_pages' => [],
        ];

        $this->put('/projects/' . $project->id . '/stories/' . $story->id .
            '?selected_company_id=' . $company->id, $data);

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'priority',
        ]);
    }

    /** @test */
    public function invalid_unique_priority_return_error_422()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        /* **************** prepare data  ********************/
        $this->prepareStory($project->id);
        $story = $this->prepareStory($project->id);

        /* **************** send request  ********************/
        $data = [
            'name' => 'Example story - phpunit',
            'priority' => 1,
            'files' => [],
            'tickets' => [],
            'knowledge_pages' => [],
        ];

        $this->put('/projects/' . $project->id . '/stories/' . $story->id .
            '?selected_company_id=' . $company->id, $data);

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'priority',
        ]);
    }

    /** @test */
    public function invalid_file_id_return_error_422()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        /* **************** prepare data  ********************/
        $story = $this->prepareStory($project->id);

        $fake_project = factory(Project::class)->create();
        $fake_file = factory(File::class)->create([
            'project_id' => $fake_project->id,
        ]);

        /* **************** send request  ********************/
        $data = [
            'name' => 'Example story',
            'priority' => 1,
            'files' => [$fake_file->id],
            'tickets' => [],
            'knowledge_pages' => [],
        ];

        $this->put('/projects/' . $project->id . '/stories/' . $story->id .
            '?selected_company_id=' . $company->id, $data);

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'files.0',
        ]);
    }

    /** @test */
    public function invalid_ticket_id_return_error_422()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        /* **************** prepare data  ********************/
        $this->prepareStory($project->id);
        $story = $this->prepareStory($project->id);

        $fake_project = factory(Project::class)->create();
        $fake_ticket = factory(Ticket::class)->create([
            'project_id' => $fake_project->id,
        ]);

        /* **************** send request  ********************/
        $data = [
            'name' => 'Example story',
            'priority' => 1,
            'files' => [],
            'tickets' => [$fake_ticket->id],
            'knowledge_pages' => [],
        ];

        $this->put('/projects/' . $project->id . '/stories/' . $story->id .
            '?selected_company_id=' . $company->id, $data);

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'tickets.0',
        ]);
    }

    /** @test */
    public function invalid_page_id_return_error_422()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        /* **************** prepare data  ********************/
        $this->prepareStory($project->id);
        $story = $this->prepareStory($project->id);

        $fake_project = factory(Project::class)->create();
        $fake_page = factory(KnowledgePage::class)->create([
            'project_id' => $fake_project->id,
        ]);

        /* **************** send request  ********************/
        $data = [
            'name' => 'Example story',
            'priority' => 1,
            'files' => [],
            'tickets' => [],
            'knowledge_pages' => [$fake_page->id],
        ];

        $this->put('/projects/' . $project->id . '/stories/' . $story->id .
            '?selected_company_id=' . $company->id, $data);

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'knowledge_pages.0',
        ]);
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
