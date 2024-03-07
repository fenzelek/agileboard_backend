<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\FileController\Store;

use App\Helpers\ErrorCode;
use App\Models\Db\File;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\Story;
use App\Models\Db\Ticket;
use App\Models\Other\RoleType;
use App\Models\Db\Role;
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

    protected function tearDown():void
    {
        if (file_exists(storage_path('phpunit_tests/test/'))) {
            array_map('unlink', glob(storage_path('phpunit_tests/test/*')));
            rmdir(storage_path('phpunit_tests/test/'));
        }

        parent::tearDown();
    }

    /** @test */
    public function isFileSend_invalidFile()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $project = $this->getProject($company, $this->user->id);

        /* **************** send request  ********************/
        $data = [
            'file' => 'phpunit-test',
        ];

        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id,
            $data
        );

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'file',
        ]);
    }

    /** @test */
    public function allowedSize_invalidFileSize()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $project = $this->getProject($company, $this->user->id);
        $uploadedFile = $this->getFile(
            'phpunit_test.txt',
            'text/plain',
            null,
            'phpunit_test.txt'
        );

        /* **************** send request  ********************/
        $data = [
            'file' => $uploadedFile,
            'roles' => [Role::findByName(RoleType::OWNER)->id],
        ];

        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id,
            $data,
            [],
            ['file' => $uploadedFile]
        );

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'file',
        ]);
    }

    /** @test */
    public function reached_limit_of_disc_space()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $project = $this->getProject($company, $this->user->id);
        factory(File::class)->create(['project_id' => $project->id, 'size' => 1024 * 1024 * 1024 * 10]);
        $uploadedFile = $this->getFile();

        /* **************** send request  ********************/
        $data = [
            'file' => $uploadedFile,
        ];

        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id,
            $data,
            [],
            ['file' => $uploadedFile]
        );

        /* **************** assertions  ********************/
        $this->assertResponseStatus(409);
    }

    /** @test */
    public function isFile_notFile()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $project = $this->getProject($company, $this->user->id);
        $uploadedFile = $this->getFile();

        /* **************** send request  ********************/
        $data = [
            'file' => '',
        ];

        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id,
            $data,
            [],
            ['file' => $uploadedFile]
        );

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'file',
        ]);
    }

    /** @test */
    public function allowedCompanyRoles_invalidRole()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);
        auth()->loginUsingId($this->user->id);
        $company->roles()->attach([
            Role::findByName(RoleType::OWNER)->id,
            Role::findByName(RoleType::ADMIN)->id,
            Role::findByName(RoleType::DEALER)->id,
        ]);
        $uploadedFile = $this->getFile();

        /* **************** send request  ********************/
        $data = [
            'roles' => [Role::findByName(RoleType::DEVELOPER)->id],
        ];

        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id,
            $data,
            [],
            ['file' => $uploadedFile]
        );

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'roles.0',
        ]);
    }

    /** @test */
    public function projectExist_notExist()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        /* **************** send request  ********************/
        $this->call(
            'post',
            '/projects/999/files?selected_company_id=' . $company->id
        );

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function isUserCompany_companyIsNotBelongsToUser()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $project = $this->getProject($company, $this->user->id);

        /* **************** send request  ********************/
        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=999'
        );

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function isUserProject_projectIsNotBelongsToUser()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::DEVELOPER);
        auth()->loginUsingId($this->user->id);

        $user_2 = factory(User::class)->create();
        $project = $this->getProject($company, $user_2);

        /* **************** send request  ********************/
        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id
        );

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function isLoggedUser_notLogged()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user);

        /* **************** send request  ********************/
        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id
        );

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(401, ErrorCode::AUTH_INVALID_TOKEN);
    }

    /** @test */
    public function allowedProjectUser_invalidUserId()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::DEVELOPER);
        $project = $this->getProject($company, $this->user->id);
        auth()->loginUsingId($this->user->id);

        $company->roles()->attach([
            Role::findByName(RoleType::ADMIN)->id,
            Role::findByName(RoleType::DEVELOPER)->id,
        ]);
        $uploadedFile = $this->getFile();
        $user_2 = factory(User::class)->create();

        /* **************** send request  ********************/
        $data = [
            'file' => $uploadedFile,
            'roles' => [Role::findByName(RoleType::ADMIN)->id],
            'users' => [$user_2->id],
        ];

        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id,
            $data,
            [],
            ['file' => $uploadedFile]
        );

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'users.0',
        ]);
    }

    /** @test */
    public function description_too_many_characters_return_invalid()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::DEVELOPER);
        $project = $this->getProject($company, $this->user->id);
        auth()->loginUsingId($this->user->id);

        $uploadedFile = $this->getFile();

        /* **************** send request  ********************/
        $data = [
            'file' => $uploadedFile,
            'description' => ' New description New description New description New description
            New description New description New description New description New description
            New description New description New description New description New description
            New description New description New description New description New description',
            'temp' => 2,
        ];

        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id,
            $data,
            [],
            ['file' => $uploadedFile]
        );

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'description',
            'temp',
        ]);
    }

    /** @test */
    public function add_to_file_not_exists_page_return_invalid()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        $company->roles()->attach([
            Role::findByName(RoleType::OWNER)->id,
        ]);
        $uploadedFile = $this->getFile();

        /* **************** send request  ********************/
        $data = [
            'file' => $uploadedFile,
            'pages' => [1000],
        ];

        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id,
            $data,
            [],
            ['file' => $uploadedFile]
        );

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
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        $company->roles()->attach([
            Role::findByName(RoleType::OWNER)->id,
        ]);
        $uploadedFile = $this->getFile();

        /* **************** send request  ********************/
        $data = [
            'file' => $uploadedFile,
            'stories' => [1000],
        ];

        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id,
            $data,
            [],
            ['file' => $uploadedFile]
        );

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
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        $company->roles()->attach([
            Role::findByName(RoleType::OWNER)->id,
        ]);
        $uploadedFile = $this->getFile();

        /* **************** send request  ********************/
        $data = [
            'file' => $uploadedFile,
            'tickets' => [1000],
        ];

        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id,
            $data,
            [],
            ['file' => $uploadedFile]
        );

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'tickets.0',
        ]);
    }

    /** @test */
    public function it_returns_error_when_sending_invalid_arrays()
    {
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);
        auth()->loginUsingId($this->user->id);
        $company->roles()->attach([
            Role::findByName(RoleType::OWNER)->id,
            Role::findByName(RoleType::ADMIN)->id,
            Role::findByName(RoleType::DEALER)->id,
        ]);
        $uploadedFile = $this->getFile();

        $role = Role::findByName(RoleType::OWNER)->id;
        $user = factory(User::class)->create()->id;
        $project->users()->attach($user);
        $page = factory(KnowledgePage::class)->create(['project_id' => $project->id])->id;
        $story = factory(Story::class)->create(['project_id' => $project->id])->id;
        $ticket = factory(Ticket::class)->create(['project_id' => $project->id])->id;

        $data = [
            'roles' => [$role => ['id' => $role]],
            'users' => [$user => ['id' => $user]],
            'pages' => [$page => ['id' => $page]],
            'stories' => [$story => ['id' => $story]],
            'tickets' => [$ticket => ['id' => $ticket]],
            'file' => $uploadedFile,
        ];

        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id,
            $data,
            [],
            ['file' => $uploadedFile]
        );

        $this->verifyValidationResponse([
            'roles.' . $role,
            'users.' . $user,
            'pages.' . $page,
            'stories.' . $story,
            'tickets.' . $ticket,
        ]);
    }
}
