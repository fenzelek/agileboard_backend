<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\FileController\Download;

use App\Helpers\ErrorCode;
use App\Models\Db\Project;
use App\Models\Other\RoleType;
use App\Models\Db\Role;
use App\Models\Db\User;
use App\Models\Db\File as ModelFile;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\ProjectHelper;
use Tests\BrowserKitTestCase;

class ValidationDownloadTest extends BrowserKitTestCase
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
        parent::tearDown();

        $this->deleteFile();
    }

    /** @test */
    public function file_is_not_assigned_to_project_return_invalid()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);
        $file = factory(ModelFile::class)->create([
            'project_id' => 999,
        ]);

        /* **************** send request  ********************/
        $this->get(
            '/projects/' . $project->id . '/files/' . $file->id . '/download?selected_company_id=' .
            $company->id
        );

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function user_is_not_assigned_to_project_return_invalid()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::DEVELOPER);
        $user_2 = factory(User::class)->create();
        $project = $this->getProject($company, $user_2);
        $file = factory(ModelFile::class)->create();

        /* **************** send request  ********************/
        $this->get(
            '/projects/' . $project->id . '/files/' . $file->id . '/download?selected_company_id=' .
            $company->id
        );

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function project_is_not_assigned_to_company_return_invalid()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project_2 = factory(Project::class)->create();
        $file = factory(ModelFile::class)->create();

        /* **************** send request  ********************/
        $this->get(
            '/projects/' . $project_2->id . '/files/' . $file->id . '/download?selected_company_id=' .
            $company->id
        );

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function project_not_exists_return_invalid()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $file = factory(ModelFile::class)->create();

        /* **************** send request  ********************/
        $this->get(
            '/projects/999/files/' . $file->id . '/download?selected_company_id=' . $company->id
        );

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function incorrect_file_id_return_invalid()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        /* **************** send request  ********************/
        $this->get(
            '/projects/' . $project->id . '/files/1/download?selected_company_id=' .
            $company->id
        );

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function not_permission_to_file_return_invalid()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::DEALER);
        $project = $this->getProject($company, $this->user->id, RoleType::DEALER);
        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'storage_name' => '1.jpg',
        ]);
        $file->roles()->attach(Role::findByName(RoleType::DEVELOPER)->id);

        /* **************** send request  ********************/
        $this->get(
            '/projects/' . $project->id . '/files/' . $file->id . '/download?selected_company_id=' .
            $company->id
        );

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function wrong_width_height()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => 9999,
            'name' => 'Przykładowa nazwa pliku!',
            'storage_name' => '1.jpg',
            'extension' => 'jpg',
        ]);

        $file->roles()->attach(Role::findByName(RoleType::OWNER)->id);
        $file->users()->attach($this->user->id);

        $this->prepareFile($company->id);

        /* **************** send request  ********************/
        $this->get(
            '/projects/' . $project->id . '/files/' . $file->id .
            '/download?width=10&height=10&selected_company_id=' . $company->id
        );

        $this->verifyErrorResponse(422, ErrorCode::VALIDATION_FAILED, ['width', 'height']);
    }
}
