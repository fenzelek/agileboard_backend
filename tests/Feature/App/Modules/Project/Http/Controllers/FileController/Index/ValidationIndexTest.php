<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\FileController\Index;

use App\Helpers\ErrorCode;
use App\Models\Db\File as ModelFile;
use App\Models\Db\Project;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\ProjectHelper;
use Tests\BrowserKitTestCase;

class ValidationIndexTest extends BrowserKitTestCase
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
    public function user_is_not_assigned_to_project_return_invalid()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::DEVELOPER);
        $user_2 = factory(User::class)->create();
        $project = $this->getProject($company, $user_2);
        factory(ModelFile::class)->create();

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id . '/files/?selected_company_id=' .
            $company->id);

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
        factory(ModelFile::class)->create();

        /* **************** send request  ********************/
        $this->get('/projects/' . $project_2->id . '/files/?selected_company_id=' .
            $company->id);

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function project_not_exists_return_invalid()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        factory(ModelFile::class)->create();

        /* **************** send request  ********************/
        $this->get('/projects/999/files/?selected_company_id=' . $company->id);

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function incorrect_fileable_type_invalid()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id, RoleType::OWNER);

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id .
            '/files?fileable_type=php_test&selected_company_id=' .
            $company->id);

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422)->verifyValidationResponse([
            'fileable_type',
        ]);
    }

    /** @test */
    public function fileable_id_not_exists_invalid()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id, RoleType::OWNER);

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id .
            '/files?fileable_type=knowledge_pages&fileable_id=1000&selected_company_id=' .
            $company->id);

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422)->verifyValidationResponse([
            'fileable_id',
        ]);
    }

    /** @test */
    public function incorrect_fileable_id_invalid()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id, RoleType::OWNER);

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id .
            '/files?fileable_type=knowledge_pages&fileable_id=php_test&selected_company_id=' .
            $company->id);

        /* **************** assertions  ********************/
        $this->assertResponseStatus(422)->verifyValidationResponse([
            'fileable_id',
        ]);
    }
}
