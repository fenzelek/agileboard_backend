<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\FileController\Destroy;

use App\Helpers\ErrorCode;
use App\Models\Db\File as ModelFile;
use App\Models\Db\Project;
use App\Models\Db\Role;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\Filesystem as File;
use Tests\BrowserKitTestCase;
use Tests\Helpers\ProjectHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ValidationDestroyTest extends BrowserKitTestCase
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
        $this->delete(
            '/projects/' . $project->id . '/files/' . $file->id . '?selected_company_id=' .
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
        $this->delete(
            '/projects/' . $project->id . '/files/' . $file->id . '?selected_company_id=' .
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
        $this->delete(
            '/projects/' . $project_2->id . '/files/' . $file->id . '?selected_company_id=' .
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
        $this->delete(
            '/projects/999/files/' . $file->id . '?selected_company_id=' . $company->id
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
        $this->delete(
            '/projects/' . $project->id . '/files/1?selected_company_id=' .
            $company->id
        );

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function permission_to_remove_when_empty_roles_and_empty_users_return_valid()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::DEALER);
        $project = $this->getProject($company, $this->user->id, RoleType::DEALER);
        $this->prepareFile($company->id);

        // preparing file record in database
        $file = $this->prepareFileDatabase(RoleType::DEALER);

        /* **************** assertions  ********************/
        $this->assertEquals(1, ModelFile::count());
        $this->assertEquals(1, $file->roles()->count());
        $this->assertEquals(1, $file->users()->count());

        /* **************** send request  ********************/
        $this->delete(
            '/projects/' . $project->id . '/files/' . $file->id . '?selected_company_id=' .
            $company->id
        )->seeStatusCode(204);

        /* **************** assertions  ********************/
        $this->assertEquals(0, ModelFile::count());
        $this->assertEquals(0, $file->roles()->count());
        $this->assertEquals(0, $file->users()->count());
        $directory = $company->id . '/projects/' . $project->id;
        $this->assertFalse(Storage::disk('company')->exists($directory . '/' . $file->storage_name));
    }

    /** @test */
    public function permission_to_remove_when_valid_roles_and_empty_users_return_valid()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::DEALER);
        $project = $this->getProject($company, $this->user->id, RoleType::DEALER);
        $this->prepareFile($company->id);

        // preparing file record in database
        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'storage_name' => '1.jpg',
        ]);
        $file->roles()->attach(
            Role::findByName(RoleType::DEALER)->id
        );

        /* **************** assertions  ********************/
        $this->assertEquals(1, ModelFile::count());
        $this->assertEquals(1, $file->roles()->count());

        /* **************** send request  ********************/
        $this->delete(
            '/projects/' . $project->id . '/files/' . $file->id . '?selected_company_id=' .
            $company->id
        )->seeStatusCode(204);

        /* **************** assertions  ********************/
        $this->assertEquals(0, ModelFile::count());
        $this->assertEquals(0, $file->roles()->count());
        $directory = $company->id . '/projects/' . $project->id;
        $this->assertFalse(Storage::disk('company')->exists($directory . '/' . $file->storage_name));
    }

    /** @test */
    public function permission_to_remove_when_empty_roles_and_valid_users_return_valid()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::DEALER);
        $project = $this->getProject($company, $this->user->id);
        $this->prepareFile($company->id);

        // preparing file record in database
        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'storage_name' => '1.jpg',
        ]);
        $file->users()->attach($this->user->id);

        /* **************** assertions  ********************/
        $this->assertEquals(1, ModelFile::count());
        $this->assertEquals(1, $file->users()->count());
        /* **************** send request  ********************/
        $this->delete(
            '/projects/' . $project->id . '/files/' . $file->id . '?selected_company_id=' .
            $company->id
        )->seeStatusCode(204);

        /* **************** assertions  ********************/
        $this->assertEquals(0, ModelFile::count());
        $this->assertEquals(0, $file->users()->count());
        $directory = $company->id . '/projects/' . $project->id;
        $this->assertFalse(Storage::disk('company')->exists($directory . '/' . $file->storage_name));
    }

    /** @test */
    public function permission_to_remove_when_valid_roles_and_invalid_users_return_valid()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::DEALER);
        $project = $this->getProject($company, $this->user->id, RoleType::DEALER);

        // preparing user record in database
        $user_2 = factory(User::class)->create();

        // preparing file record in database
        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'storage_name' => '1.jpg',
        ]);
        $file->roles()->attach(Role::findByName(RoleType::DEALER)->id);
        $file->users()->attach($user_2->id);

        /* **************** assertions  ********************/
        $this->assertEquals(1, ModelFile::count());
        $this->assertEquals(1, $file->roles()->count());
        $this->assertEquals(1, $file->users()->count());

        /* **************** send request  ********************/
        $this->delete(
            '/projects/' . $project->id . '/files/' . $file->id . '?selected_company_id=' .
            $company->id
        )->seeStatusCode(204);

        /* **************** assertions  ********************/
        $this->assertEquals(0, ModelFile::count());
        $this->assertEquals(0, $file->roles()->count());
        $this->assertEquals(0, $file->users()->count());
        $directory = $company->id . '/projects/' . $project->id;
        $this->assertFalse(Storage::disk('company')->exists($directory . '/' . $file->storage_name));
    }

    /** @test */
    public function permission_to_remove_when_invalid_roles_and_valid_users_return_valid()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::DEALER);
        $project = $this->getProject($company, $this->user->id, RoleType::DEALER);

        // preparing file record in database
        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'storage_name' => '1.jpg',
        ]);
        $file->roles()->attach(Role::findByName(RoleType::DEVELOPER)->id);
        $file->users()->attach($this->user->id);

        /* **************** assertions  ********************/
        $this->assertEquals(1, ModelFile::count());
        $this->assertEquals(1, $file->roles()->count());
        $this->assertEquals(1, $file->users()->count());

        /* **************** send request  ********************/
        $this->delete(
            '/projects/' . $project->id . '/files/' . $file->id . '?selected_company_id=' .
            $company->id
        )->seeStatusCode(204);

        /* **************** assertions  ********************/
        $this->assertEquals(0, ModelFile::count());
        $directory = $company->id . '/projects/' . $project->id;
        $this->assertFalse(Storage::disk('company')->exists($directory . '/' . $file->storage_name));
    }

    /** @test */
    public function permission_to_remove_when_invalid_roles_and_empty_users_return_invalid()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::DEALER);
        $project = $this->getProject($company, $this->user->id, RoleType::DEALER);
        $this->prepareFile($company->id);

        // preparing file record in database
        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'storage_name' => '1.jpg',
        ]);
        $file->roles()->attach(Role::findByName(RoleType::DEVELOPER)->id);

        /* **************** send request  ********************/
        $this->delete(
            '/projects/' . $project->id . '/files/' . $file->id . '?selected_company_id=' .
            $company->id
        );

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function permission_to_remove_when_empty_roles_and_invalid_users_return_invalid()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::DEALER);
        $project = $this->getProject($company, $this->user->id, RoleType::DEALER);
        $this->prepareFile($company->id);

        // preparing user record in database
        $user_2 = factory(User::class)->create();

        // preparing file record in database
        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'storage_name' => '1.jpg',
        ]);
        $file->users()->attach($user_2->id);

        /* **************** assertions  ********************/
        $this->assertEquals(1, ModelFile::count());

        /* **************** send request  ********************/
        $this->delete(
            '/projects/' . $project->id . '/files/' . $file->id . '?selected_company_id=' .
            $company->id
        );

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function permission_to_remove_when_invalid_roles_and_invalid_users_return_invalid()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::DEALER);
        $project = $this->getProject($company, $this->user->id, RoleType::DEALER);

        // preparing user record in database
        $user_2 = factory(User::class)->create();

        // preparing file record in database
        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'storage_name' => '1.jpg',
        ]);
        $file->roles()->attach(Role::findByName(RoleType::DEVELOPER)->id);
        $file->users()->attach($user_2->id);

        /* **************** send request  ********************/
        $this->delete(
            '/projects/' . $project->id . '/files/' . $file->id . '?selected_company_id=' .
            $company->id
        );

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function permission_to_remove_not_assigned_owner_return_invalid()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);

        // preparing user record in database
        $user_2 = factory(User::class)->create();
        $project_2 = factory(Project::class)->create([
            'id' => 9998,
            'company_id' => $company->id,
            'name' => 'Test remove project 9998',
            'short_name' => 'trp 9998',
        ]);
        $project_2->users()->attach($user_2->id, ['role_id' => Role::findByName(RoleType::DEALER)->id]);

        // create storage path
        $this->prepareFile($company->id, 9998);

        // copy file to storage
        $realFile = storage_path('phpunit_tests/samples/phpunit_test.jpg');
        $uploadedLocation = storage_path('company/' . $company->id . '/projects/9998/2.jpg');
        copy($realFile, $uploadedLocation);

        // preparing file record in database
        $file = factory(ModelFile::class)->create([
            'id' => 2,
            'project_id' => $project_2->id,
            'storage_name' => '2.jpg',
            'user_id' => $user_2->id,
        ]);
        $dealer_role = Role::findByName(RoleType::DEALER)->id;
        $file->roles()->attach($dealer_role);
        $file->users()->attach($user_2->id);

        /* **************** assertions  ********************/
        $this->assertEquals(1, ModelFile::count());
        $this->assertEquals(1, $file->roles()->count());
        $this->assertEquals(1, $file->users()->count());

        /* **************** send request  ********************/
        $this->delete(
            '/projects/' . $project_2->id . '/files/' . $file->id . '?selected_company_id=' .
            $company->id
        );

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }
}
