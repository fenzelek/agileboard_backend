<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\StoryController\Store;

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

class ValidationStoreTest extends BrowserKitTestCase
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

        /* **************** send request  ********************/
        $this->post('/projects/999/stories?selected_company_id=' . $company->id);

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
        $this->post('/projects/' . $project->id . '/stories?selected_company_id=999');

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
        $this->post('/projects/' . $project->id . '/stories?selected_company_id=' .
            $company->id);

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

        /* **************** send request  ********************/
        $data = [
            'name' => '',
            'files' => [],
            'tickets' => [],
        ];

        $this->post('/projects/' . $project->id . '/stories?selected_company_id=' .
            $company->id, $data);

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

        /* **************** send request  ********************/
        $data = [
            'name' => 'Example story - phpunitphpunitphpunitphpunitphpunitphpunitphpunitphpunit
            phpunitphpunitphpunitphpunitphpunitphpunitphpunitphpunitphpunitphpunitphpunitphpunit
            phpunitphpunitphpunitphpunitphpunitphpunitphpunitphpunitphpunitphpunitphpunitphpunit
            phpunitphpunitphpunit',
            'files' => [],
            'tickets' => [],
        ];

        $this->post('/projects/' . $project->id . '/stories?selected_company_id=' .
            $company->id, $data);

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
        factory(Story::class)->create([
            'project_id' => $project->id,
            'name' => 'Example story - phpunit',
        ]);

        /* **************** send request  ********************/
        $data = [
            'name' => 'Example story - phpunit',
            'files' => [],
            'tickets' => [],
        ];

        $this->post('/projects/' . $project->id . '/stories?selected_company_id=' .
            $company->id, $data);

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'name',
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
        factory(Story::class)->create([
            'project_id' => $project->id,
            'name' => 'Example story - phpunit',
        ]);

        $fake_project = factory(Project::class)->create();
        $fake_file = factory(File::class)->create([
            'project_id' => $fake_project->id,
        ]);

        /* **************** send request  ********************/
        $data = [
            'name' => 'Example story',
            'files' => [$fake_file->id],
            'tickets' => [],
        ];

        $this->post('/projects/' . $project->id . '/stories?selected_company_id=' .
            $company->id, $data);

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'files.0',
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
        factory(Story::class)->create([
            'project_id' => $project->id,
            'name' => 'Example story - phpunit',
        ]);

        $fake_project = factory(Project::class)->create();
        $fake_page = factory(KnowledgePage::class)->create([
            'project_id' => $fake_project->id,
        ]);

        /* **************** send request  ********************/
        $data = [
            'name' => 'Example story',
            'files' => [],
            'tickets' => [],
            'knowledge_pages' => [$fake_page->id],
        ];

        $this->post('/projects/' . $project->id . '/stories?selected_company_id=' .
            $company->id, $data);

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'knowledge_pages.0',
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
        factory(Story::class)->create([
            'project_id' => $project->id,
            'name' => 'Example story - phpunit',
        ]);

        $fake_project = factory(Project::class)->create();
        $fake_ticket = factory(Ticket::class)->create([
            'project_id' => $fake_project->id,
        ]);

        /* **************** send request  ********************/
        $data = [
            'name' => 'Example story',
            'files' => [],
            'tickets' => [$fake_ticket->id],
        ];

        $this->post('/projects/' . $project->id . '/stories?selected_company_id=' .
            $company->id, $data);

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'tickets.0',
        ]);
    }

    /** @test */
    public function invalid_the_same_id_file_return_error_422()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        /* **************** prepare data  ********************/
        factory(Story::class)->create([
            'project_id' => $project->id,
            'name' => 'Example story - phpunit',
        ]);

        $file = factory(File::class)->create([
            'project_id' => $project->id,
        ]);

        /* **************** send request  ********************/
        $data = [
            'name' => 'Example story',
            'files' => [$file->id, $file->id],
            'tickets' => [],
        ];

        $this->post('/projects/' . $project->id . '/stories?selected_company_id=' .
            $company->id, $data);

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'files.0',
            'files.1',
        ]);
    }

    /** @test */
    public function invalid_the_same_id_ticket_return_error_422()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        /* **************** prepare data  ********************/
        factory(Story::class)->create([
            'project_id' => $project->id,
            'name' => 'Example story - phpunit',
        ]);

        $ticket = factory(Ticket::class)->create([
            'project_id' => $project->id,
        ]);

        /* **************** send request  ********************/
        $data = [
            'name' => 'Example story',
            'files' => [],
            'tickets' => [$ticket->id, $ticket->id],
        ];

        $this->post('/projects/' . $project->id . '/stories?selected_company_id=' .
            $company->id, $data);

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'tickets.0',
            'tickets.1',
        ]);
    }

    /** @test */
    public function invalid_the_same_id_page_return_error_422()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        /* **************** prepare data  ********************/
        factory(Story::class)->create([
            'project_id' => $project->id,
            'name' => 'Example story - phpunit',
        ]);

        $page = factory(KnowledgePage::class)->create([
            'project_id' => $project->id,
        ]);

        /* **************** send request  ********************/
        $data = [
            'name' => 'Example story',
            'files' => [],
            'tickets' => [],
            'knowledge_pages' => [$page->id, $page->id],
        ];

        $this->post('/projects/' . $project->id . '/stories?selected_company_id=' .
            $company->id, $data);

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'knowledge_pages.0',
            'knowledge_pages.1',
        ]);
    }
}
