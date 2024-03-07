<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\FileController\Destroy;

use App\Helpers\ErrorCode;
use App\Models\Db\Role;
use App\Models\Other\RoleType;
use App\Models\Db\File as ModelFile;
use App\Models\Db\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\Helpers\ProjectHelper;
use Tests\BrowserKitTestCase;

class LogicDestroyTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ProjectHelper;

    /**
     * @var User
     */
    protected $user;

    public function setUp():void
    {
        parent::setUp();

        /* **************** setup environments  ********************/
        $this->user = $this->createUser()->user;
        auth()->loginUsingId($this->user->id);
    }

    protected function tearDown():void
    {
        parent::tearDown();

        $this->deleteFile();
    }

    /** @test */
    public function transactionRollback_DatabaseException_tableFiles()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);
        $this->prepareFile($company->id);

        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'storage_name' => '1.jpg',
        ]);

        $roles = collect([Role::findByName(RoleType::OWNER)->id]);
        $users = collect([$this->user->id]);

        /* **************** create mocks  ********************/
        $model_file = Mockery::mock(ModelFile::class)->makePartial();
        $model_file->shouldReceive('where')->andReturn($model_file);
        $model_file->shouldReceive('first')->andReturn($model_file);
        $model_file->shouldReceive('getAttribute')->with('id')->andReturn($file->id);
        $model_file->shouldReceive('getAttribute')->with('project_id')->andReturn($project->id);
        $model_file->shouldReceive('firstOrFail')->andReturn($model_file);
        $model_file->shouldReceive('roles->pluck')->andReturn($roles);
        $model_file->shouldReceive('users->pluck')->andReturn($users);
        $model_file->shouldReceive('delete')->once()
            ->andThrow(new \Exception('Failed to delete records from database'));

        app()->instance(ModelFile::class, $model_file);

        /* **************** send request  ********************/
        $this->delete('/projects/' . $project->id . '/files/' . $file->id .
            '?selected_company_id=' . $company->id);

        /* **************** assertions  ********************/
        $this->assertEquals(1, ModelFile::count());

        $directory = $company->id . '/projects/' . $project->id;
        $this->assertTrue(Storage::disk('company')->exists($directory . '/' . $file->storage_name));
        $this->verifyErrorResponse(500, ErrorCode::API_ERROR);
    }

    /** @test */
    public function transactionRollback_StorageException()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);
        $this->prepareFile($company->id);
        $file = $this->prepareFileDatabase();

        /* **************** create mocks  ********************/
        $mock_storage = Mockery::mock(\App\Modules\Project\Services\Storage::class);
        $mock_storage->shouldReceive('remove')
            ->once()
            ->andThrow(new \Exception('Failed to delete file from disk'));

        app()->instance(\App\Modules\Project\Services\Storage::class, $mock_storage);
        $count_records_initial = ModelFile::count();

        /* **************** send request  ********************/
        $this->delete('/projects/' . $project->id . '/files/' . $file->id .
            '?selected_company_id=' . $company->id);

        /* **************** assertions  ********************/
        $this->assertEquals($count_records_initial, ModelFile::count());
        $this->verifyErrorResponse(500, ErrorCode::API_ERROR);
    }

    /** @test */
    public function fileRemove_valid()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);
        $this->prepareFile($company->id);
        $file = $this->prepareFileDatabase();

        /* **************** send request  ********************/
        $this->delete('/projects/' . $project->id . '/files/' . $file->id .
            '?selected_company_id=' . $company->id)->seeStatusCode(204);

        /* **************** assertions  ********************/
        $this->assertEquals(0, ModelFile::count());
        $directory = $company->id . '/projects/' . $project->id;
        $this->assertFalse(Storage::disk('company')->exists($directory . '/1.jpg'));
    }
}
