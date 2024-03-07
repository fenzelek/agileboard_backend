<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\FileController\Download;

use App\Helpers\ErrorCode;
use App\Models\Db\Company;
use App\Models\Db\Role;
use Mockery;
use App\Models\Other\RoleType;
use App\Models\Db\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Exceptions\FileException;
use Tests\Helpers\ProjectHelper;
use Illuminate\Filesystem\Filesystem as File;
use App\Models\Db\File as ModelFile;
use Tests\BrowserKitTestCase;
use Intervention\Image\Facades\Image;

class LogicDownloadTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ProjectHelper;
    /**
     * @var User
     */
    protected $user;

    /**
     * @var Company
     */
    protected $company;

    /**
     * @var File
     */
    protected $file;

    public function setUp():void
    {
        parent::setUp();

        /* **************** setup environments  ********************/
        $this->user = $this->createUser()->user;
    }

    protected function tearDown():void
    {
        parent::tearDown();

        $this->deleteFile();
    }

    /** @test */
    public function file_not_exist_in_storage_throw_exception()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);
        $file = $this->prepareFileDatabase();

        /* **************** create mocks  ********************/
        $directory = '8888/projects/9999';

        $model_store = Mockery::mock(\App\Models\Filesystem\Store::class);
        $model_store->shouldReceive('getPath')
            ->once()
            ->andReturn($directory);

        $model_store->shouldReceive('fileExists')
            ->once()
            ->andThrow(new FileException('File does not exist'));

        app()->instance(\App\Models\Filesystem\Store::class, $model_store);

        /* **************** send request  ********************/
        $this->get(
            '/projects/' . $project->id . '/files/' . $file->id .
            '/download?selected_company_id=' . $company->id
        );

        /* **************** assertions  ********************/
        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function file_return_correctly()
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
            '/download?selected_company_id=' . $company->id
        )->seeStatusCode(200);

        $this->seeHeader('content-type', 'image/jpeg');
        $this->seeHeader(
            'content-disposition',
            'attachment; filename="Przykladowa nazwa pliku!.jpg"'
        );
    }

    /** @test */
    public function image_resize_width_return_correctly()
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
            '/download?width=60&selected_company_id=' . $company->id
        )->seeStatusCode(200);

        $this->seeHeader('content-type', 'image/jpeg');
        $img = Image::make($this->response->getContent());
        $this->assertSame(60, $img->width());
    }

    /** @test */
    public function image_resize_height_return_correctly()
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
            '/download?height=60&selected_company_id=' . $company->id
        )->seeStatusCode(200);

        $this->seeHeader('content-type', 'image/jpeg');
        $img = Image::make($this->response->getContent());
        $this->assertSame(60, $img->height());
    }
}
