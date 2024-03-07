<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\FileController\Update;

use App\Helpers\ErrorCode;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\Role;
use App\Models\Db\Story;
use App\Models\Db\Ticket;
use App\Models\Other\RoleType;
use App\Models\Db\User;
use App\Models\Db\File as ModelFile;
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
    public function project_not_exist_return_invalid()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        /* **************** send request  ********************/
        $this->put('/projects/999/files/1?selected_company_id=' . $company->id);

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function user_not_belongs_to_company_return_invalid()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::DEVELOPER);
        auth()->loginUsingId($this->user->id);
        $project = $this->getProject($company, $this->user->id);
        $file = $this->prepareFileDatabase($role_type = RoleType::OWNER);

        /* **************** send request  ********************/
        $this->put('/projects/' . $project->id . '/files/' . $file->id .
            '?selected_company_id=999');

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function user_not_belongs_to_project_return_invalid()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::DEVELOPER);
        auth()->loginUsingId($this->user->id);

        // prepare data
        $user_2 = factory(User::class)->create();
        $project = $this->getProject($company, $user_2);
        $file = $this->prepareFileDatabase($role_type = RoleType::OWNER);

        /* **************** send request  ********************/
        $this->put('/projects/' . $project->id . '/files/' . $file->id .
            '?selected_company_id=' . $company->id);

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function name_too_many_characters_return_invalid()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $project = $this->getProject($company, $this->user->id);

        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'name' => 'File name',
            'description' => 'File description',
        ]);

        /* **************** send request  ********************/
        $data = [
            'name' => 'Example name file Example name file Example name file  
            Example name file  Example name file  Example name file  Example name file  
            Example name file  Example name file  Example name file  Example name file  
            Example name file  Example name file  Example name file  Example name file  
            Example name file  Example name file  Example name file',
            'description' => ' File description',
            'roles' => [],
            'users' => [],
        ];
        $this->put('/projects/' . $project->id . '/files/' . $file->id .
            '?selected_company_id=' . $company->id, $data);

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'name',
        ]);
    }

    /** @test */
    public function description_too_many_characters_return_invalid()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $project = $this->getProject($company, $this->user->id);

        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'name' => 'File name',
            'description' => 'File description',
        ]);

        /* **************** send request  ********************/
        $data = [
            'name' => 'File name',
            'description' => ' New description New description New description New description
            New description New description New description New description New description
            New description New description New description New description New description
            New description New description New description New description New description',
            'roles' => [],
            'users' => [],
        ];
        $this->put('/projects/' . $project->id . '/files/' . $file->id .
            '?selected_company_id=' . $company->id, $data);

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'description',
        ]);
    }

    /** @test */
    public function assigned_id_role_who_is_not_assigned_to_company_return_invalid()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $company->roles()->attach([
            Role::findByName(RoleType::OWNER)->id,
        ]);

        auth()->loginUsingId($this->user->id);
        $project = $this->getProject($company, $this->user->id);

        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'name' => 'File name',
            'description' => 'File description',
        ]);

        /* **************** send request  ********************/
        $data = [
            'name' => 'File name',
            'description' => 'File description',
            'roles' => [
                Role::findByName(RoleType::ADMIN)->id,
            ],
            'users' => [],
        ];
        $this->put('/projects/' . $project->id . '/files/' . $file->id .
            '?selected_company_id=' . $company->id, $data);

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'roles.0',
        ]);
    }

    /** @test */
    public function assigned_id_user_who_is_not_assigned_to_project_return_invalid()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $project = $this->getProject($company, $this->user->id);

        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'name' => 'File name',
            'description' => 'File description',
        ]);
        //prepare users
        $user_2 = factory(User::class)->create();

        $file->users()->attach([
            $user_2->id,
        ]);

        /* **************** send request  ********************/
        $data = [
            'name' => 'File name',
            'description' => 'File description',
            'roles' => [],
            'users' => [$user_2->id],
        ];
        $this->put('/projects/' . $project->id . '/files/' . $file->id .
            '?selected_company_id=' . $company->id, $data);

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'users.0',
        ]);
    }

    /** @test */
    public function invalid_name_file_return_error_validation()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $company->roles()->attach([Role::findByName(RoleType::OWNER)->id]);

        auth()->loginUsingId($this->user->id);
        $project = $this->getProject($company, $this->user->id);

        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'name' => 'File name',
            'description' => 'File description',
        ]);

        /* **************** send request  ********************/
        $data = [
            'name' => '',
            'description' => 'File description',
            'roles' => [],
            'users' => [],
        ];
        $this->put('/projects/' . $project->id . '/files/' . $file->id .
            '?selected_company_id=' . $company->id, $data);

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'name',
        ]);
    }

    /** @test */
    public function add_to_file_not_exists_page_return_invalid()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $company->roles()->attach([Role::findByName(RoleType::OWNER)->id]);

        auth()->loginUsingId($this->user->id);
        $project = $this->getProject($company, $this->user->id);

        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'name' => 'File name',
            'description' => 'File description',
        ]);

        /* **************** send request  ********************/
        $data = [
            'name' => '',
            'description' => 'File description',
            'roles' => [],
            'users' => [],
            'pages' => [1000],
            'stories' => [],
            'tickets' => [],
        ];

        $this->put('/projects/' . $project->id . '/files/' . $file->id .
            '?selected_company_id=' . $company->id, $data);

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'pages.0',
        ]);
    }

    /** @test */
    public function add_to_file_not_exists_story_return_invalid()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $company->roles()->attach([Role::findByName(RoleType::OWNER)->id]);

        auth()->loginUsingId($this->user->id);
        $project = $this->getProject($company, $this->user->id);

        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'name' => 'File name',
            'description' => 'File description',
        ]);

        /* **************** send request  ********************/
        $data = [
            'name' => '',
            'description' => 'File description',
            'roles' => [],
            'users' => [],
            'pages' => [],
            'stories' => [1000],
            'tickets' => [],
        ];

        $this->put('/projects/' . $project->id . '/files/' . $file->id .
            '?selected_company_id=' . $company->id, $data);

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'stories.0',
        ]);
    }

    /** @test */
    public function add_to_file_not_exists_ticket_return_invalid()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $company->roles()->attach([Role::findByName(RoleType::OWNER)->id]);

        auth()->loginUsingId($this->user->id);
        $project = $this->getProject($company, $this->user->id);

        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'name' => 'File name',
            'description' => 'File description',
        ]);

        /* **************** send request  ********************/
        $data = [
            'name' => '',
            'description' => 'File description',
            'roles' => [],
            'users' => [],
            'pages' => [],
            'stories' => [],
            'tickets' => [1000],
        ];

        $this->put('/projects/' . $project->id . '/files/' . $file->id .
            '?selected_company_id=' . $company->id, $data);

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'tickets.0',
        ]);
    }

    /** @test */
    public function it_gets_validation_error_when_sending_invalid_arrays()
    {
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $company->roles()->attach([Role::findByName(RoleType::OWNER)->id]);

        auth()->loginUsingId($this->user->id);
        $project = $this->getProject($company, $this->user->id);

        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'name' => 'File name',
            'description' => 'File description',
        ]);

        $role = Role::findByName(RoleType::OWNER)->id;
        $user = factory(User::class)->create()->id;
        $project->users()->attach($user);
        $page = factory(KnowledgePage::class)->create(['project_id' => $project->id])->id;
        $story = factory(Story::class)->create(['project_id' => $project->id])->id;
        $ticket = factory(Ticket::class)->create(['project_id' => $project->id])->id;

        $data = [
            'name' => 'Sample',
            'description' => 'File description',
            'roles' => [$role => ['id' => $role]],
            'users' => [$user => ['id' => $user]],
            'pages' => [$page => ['id' => $page]],
            'stories' => [$story => ['id' => $story]],
            'tickets' => [$ticket => ['id' => $ticket]],
        ];

        $this->put('/projects/' . $project->id . '/files/' . $file->id .
            '?selected_company_id=' . $company->id, $data);

        $this->verifyValidationResponse([
            'roles.' . $role,
            'users.' . $user,
            'pages.' . $page,
            'stories.' . $story,
            'tickets.' . $ticket,
        ]);
    }
}
