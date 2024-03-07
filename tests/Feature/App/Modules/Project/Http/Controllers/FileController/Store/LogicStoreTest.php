<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\FileController\Store;

use App\Helpers\ErrorCode;
use App\Models\Db\Company;
use App\Models\Db\File;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\Project;
use App\Models\Db\Story;
use App\Models\Db\Ticket;
use Mockery;
use Carbon\Carbon;
use App\Models\Db\File as ModelFile;
use App\Models\Other\RoleType;
use App\Models\Db\Role;
use App\Models\Db\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\ProjectHelper;
use Tests\BrowserKitTestCase;

class LogicStoreTest extends BrowserKitTestCase
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

    protected $company_id;

    public function setUp(): void
    {
        parent::setUp();

        /* **************** setup environments  ********************/
        $this->user = $this->createUser()->user;
    }

    protected function tearDown(): void
    {
        if (file_exists(storage_path('phpunit_tests/test/'))) {
            array_map('unlink', glob(storage_path('phpunit_tests/test/*')));
            rmdir(storage_path('phpunit_tests/test/'));
        }

        if (Storage::disk('company')->exists($this->company_id)) {
            Storage::disk('company')->deleteDirectory($this->company_id);
        }

        parent::tearDown();
    }

    /** @test */
    public function transactionRollback_DatabaseException()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $this->company_id = $company->id;
        $project = $this->getProject($company, $this->user->id);

        $company->roles()->attach([
            Role::findByName(RoleType::OWNER)->id,
        ]);
        $uploadedFile = $this->getFile();

        /* **************** create mocks  ********************/
        $file_raw_id = 1;

        $model_file = Mockery::mock(\App\Models\Db\File::class);
        $model_file->shouldReceive('roles->attach');
        $model_file->shouldReceive('users->attach');
        $model_file->shouldReceive('tickets->attach');
        $model_file->shouldReceive('pages->attach');
        $model_file->shouldReceive('stories->attach');

        $model_file->shouldReceive('create')
            ->once()
            ->andReturn($model_file);

        $model_file->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn($file_raw_id);

        $model_file->shouldReceive('update')
            ->once()
            ->andThrow(new \Exception('Failed update'));

        app()->instance(\App\Models\Db\File::class, $model_file);

        /* **************** send request  ********************/
        $data = [
            'file' => $uploadedFile,
            'roles' => [
                Role::findByName(RoleType::OWNER)->id,
            ],
            'users' => [],
            'pages' => [],
            'stories' => [],
            'tickets' => [],
        ];
        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id,
            $data,
            [],
            ['file' => $uploadedFile]
        );

        /* **************** assertions  ********************/
        $directory = $company->id . '/projects/' . $project->id;

        $this->assertFalse(Storage::disk('company')->exists($directory . '/' . $file_raw_id .
            '.jpg'));
        $this->verifyErrorResponse(500, ErrorCode::API_ERROR);
    }

    /** @test */
    public function transactionRollback_StorageException()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $this->company_id = $company->id;
        $project = $this->getProject($company, $this->user->id);

        $company->roles()->attach([
            Role::findByName(RoleType::OWNER)->id,
        ]);
        $uploadedFile = $this->getFile();

        /* **************** create mocks  ********************/
        $mock_storage = Mockery::mock(\App\Modules\Project\Services\Storage::class);
        $mock_storage->shouldReceive('save')
            ->once()
            ->andThrow(new \Exception('Failed save'));

        app()->instance(\App\Modules\Project\Services\Storage::class, $mock_storage);
        $count_records_initial = ModelFile::count();

        /* **************** send request  ********************/
        $data = [
            'file' => $uploadedFile,
            'roles' => [
                Role::findByName(RoleType::OWNER)->id,
            ],
            'users' => [],
            'pages' => [],
            'stories' => [],
            'tickets' => [],
        ];
        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id,
            $data,
            [],
            ['file' => $uploadedFile]
        );

        /* **************** assertions  ********************/
        $count_records = ModelFile::count();
        $this->assertEquals($count_records_initial, $count_records);
        $this->verifyErrorResponse(500, ErrorCode::API_ERROR);
    }

    /** @test */
    public function fileRoles_RoleNotDuplication()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $this->company_id = $company->id;
        $project = $this->getProject($company, $this->user->id);

        $company->roles()->attach([
            Role::findByName(RoleType::OWNER)->id,
            Role::findByName(RoleType::ADMIN)->id,
        ]);
        $uploadedFile = $this->getFile();

        /* **************** send request  ********************/
        $data = [
            'file' => $uploadedFile,
            'roles' => [
                Role::findByName(RoleType::OWNER)->id,
                Role::findByName(RoleType::OWNER)->id,
                Role::findByName(RoleType::ADMIN)->id,
            ],
            'users' => [],
            'pages' => [],
            'stories' => [],
            'tickets' => [],
        ];
        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id,
            $data,
            [],
            ['file' => $uploadedFile]
        );

        $this->seeStatusCode(201)->isJson();
        $json = $this->decodeResponseJson()['data'];

        /* **************** assertions  ********************/
        $roles = ModelFile::find($json['id'])->roles->pluck('id');

        $this->assertEquals(2, $roles->count());
        $this->assertTrue($roles->contains(Role::findByName(RoleType::OWNER)->id));
        $this->assertTrue($roles->contains(Role::findByName(RoleType::ADMIN)->id));
    }

    /** @test */
    public function fileRoles_validMultiRoles()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $this->company_id = $company->id;
        $project = $this->getProject($company, $this->user->id);

        $company->roles()->attach([
            Role::findByName(RoleType::OWNER)->id,
            Role::findByName(RoleType::ADMIN)->id,
            Role::findByName(RoleType::DEALER)->id,
        ]);
        $uploadedFile = $this->getFile();

        /* **************** send request  ********************/
        $data = [
            'file' => $uploadedFile,
            'roles' => [
                Role::findByName(RoleType::OWNER)->id,
                Role::findByName(RoleType::ADMIN)->id,
                Role::findByName(RoleType::DEALER)->id,
            ],
            'users' => [],
            'pages' => [],
            'stories' => [],
            'tickets' => [],
        ];
        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id,
            $data,
            [],
            ['file' => $uploadedFile]
        );

        $this->seeStatusCode(201)->isJson();
        $json = $this->decodeResponseJson()['data'];

        /* **************** assertions  ********************/
        $this->assertEquals(false, ModelFile::find($json['id'])->temp);

        $roles = ModelFile::find($json['id'])->roles->pluck('id');

        $this->assertEquals(3, $roles->count());
        $this->assertTrue($roles->contains(Role::findByName(RoleType::OWNER)->id));
        $this->assertTrue($roles->contains(Role::findByName(RoleType::ADMIN)->id));
        $this->assertTrue($roles->contains(Role::findByName(RoleType::DEALER)->id));
    }

    /** @test */
    public function fileRoles_validEmptyRoles()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $this->company_id = $company->id;
        $project = $this->getProject($company, $this->user->id);

        $company->roles()->attach([
            Role::findByName(RoleType::OWNER)->id,
        ]);
        $uploadedFile = $this->getFile();

        /* **************** send request  ********************/
        $data = [
            'file' => $uploadedFile,
            'roles' => [],
            'users' => [],
            'pages' => [],
            'stories' => [],
            'tickets' => [],
        ];
        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id,
            $data,
            [],
            ['file' => $uploadedFile]
        );

        $this->seeStatusCode(201)->isJson();
        $json = $this->decodeResponseJson()['data'];

        /* **************** assertions  ********************/
        $roles = ModelFile::find($json['id'])->roles->pluck('id');
        $this->assertEquals(0, $roles->count());
    }

    /** @test */
    public function it_allows_to_not_send_relationships_arrays()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $this->company_id = $company->id;
        $project = $this->getProject($company, $this->user->id);

        $company->roles()->attach([
            Role::findByName(RoleType::OWNER)->id,
        ]);
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

        $this->seeStatusCode(201)->isJson();
        $json = $this->decodeResponseJson()['data'];

        /* **************** assertions  ********************/
        $file = ModelFile::find($json['id']);

        $this->assertEquals(0, $file->roles()->count());
        $this->assertEquals(0, $file->users()->count());
        $this->assertEquals(0, $file->pages()->count());
        $this->assertEquals(0, $file->stories()->count());
        $this->assertEquals(0, $file->tickets()->count());
    }

    /** @test */
    public function fileTable_RowIsCreated()
    {
        /* **************** setup environments  ********************/
        $now = Carbon::parse('2017-03-26 14:00:00');
        Carbon::setTestNow($now);

        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $this->company_id = $company->id;
        $project = $this->getProject($company, $this->user->id);

        $company->roles()->attach([
            Role::findByName(RoleType::OWNER)->id,
        ]);
        $uploadedFile = $this->getFile();

        /* **************** send request  ********************/
        $data = [
            'file' => $uploadedFile,
            'description' => 'phpunit-test',
            'temp' => '1',
            'roles' => [
                Role::findByName(RoleType::OWNER)->id,
            ],
            'users' => [],
            'pages' => [],
            'stories' => [],
            'tickets' => [],
        ];
        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id,
            $data,
            [],
            ['file' => $uploadedFile]
        );

        $this->seeStatusCode(201)->isJson();
        $json = $this->decodeResponseJson()['data'];

        /* **************** assertions  ********************/
        $file = ModelFile::find($json['id']);

        $this->assertEquals(
            pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME),
            $file['name']
        );
        $this->assertEquals($uploadedFile->getSize(), $file['size']);
        $this->assertEquals($uploadedFile->getClientOriginalExtension(), $file['extension']);
        $this->assertEquals($data['description'], $file['description']);
        $this->assertEquals($data['temp'], $file['temp']);
        $this->assertEquals($this->user->id, $file['user_id']);
        $this->assertEquals($project->id, $file['project_id']);
        $this->assertEquals($now, $file['created_at']);
        $this->assertEquals($now, $file['updated_at']);
    }

    /** @test */
    public function fileSaving_DuplicatedFileName_savedProperlyByIteration()
    {
        /* **************** setup environments  ********************/
        $now = Carbon::parse('2017-03-26 14:00:00');
        Carbon::setTestNow($now);
        $timestamp = Carbon::parse($now)->timestamp . '_';

        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $this->company_id = $company->id;
        $project = $this->getProject($company, $this->user->id);

        $company->roles()->attach([
            Role::findByName(RoleType::OWNER)->id,
        ]);
        $uploadedFile = $this->getFile(
            'test.php',
            'text/plain',
            null,
            'phpunit_test.php'
        );
        $data = [
            'file' => $uploadedFile,
            'roles' => [
                Role::findByName(RoleType::OWNER)->id,
            ],
            'users' => [],
            'pages' => [],
            'stories' => [],
            'tickets' => [],
        ];

        /* **************** prepare and save fake file  ********************/
        $fake_file = ModelFile::create([
            'storage_name' => 'phpunit-test',
        ]);
        $fake_storage_name = $fake_file->id + 1;
        $directory = $company->id . '/projects/' . $project->id;
        Storage::disk('company')->putFileAs($directory, $uploadedFile, $fake_storage_name);

        /* **************** send request  ********************/
        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id,
            $data,
            [],
            ['file' => $uploadedFile]
        );
        $this->seeStatusCode(201)->isJson();
        $json = $this->decodeResponseJson()['data'];

        /* **************** assertions  ********************/
        $storage_name = ModelFile::find($json['id'])->storage_name;
        $expected_name = $timestamp . $json['id'];

        $this->assertEquals($expected_name, $storage_name);
    }

    /** @test */
    public function fileSaving_FileExtension_NotAllowed()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $this->company_id = $company->id;
        $project = $this->getProject($company, $this->user->id);

        $company->roles()->attach([
            Role::findByName(RoleType::OWNER)->id,
            Role::findByName(RoleType::ADMIN)->id,
        ]);

        $uploadedFile = $this->getFile(
            'test.php',
            'text/plain',
            null,
            'phpunit_test.php'
        );
        $data = [
            'file' => $uploadedFile,
            'roles' => [
                Role::findByName(RoleType::OWNER)->id,
                Role::findByName(RoleType::ADMIN)->id,
            ],
            'users' => [],
            'pages' => [],
            'stories' => [],
            'tickets' => [],
        ];

        /* **************** send request  ********************/
        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id,
            $data,
            [],
            ['file' => $uploadedFile]
        );
        $this->seeStatusCode(201)->isJson();
        $json = $this->decodeResponseJson()['data'];

        /* **************** assertions  ********************/
        $storage_name = ModelFile::find($json['id'])->storage_name;
        $this->assertFalse(mb_strpos($storage_name, '.'));
    }

    /** @test */
    public function fileSaving_FileExtension_Allowed()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $this->company_id = $company->id;
        $project = $this->getProject($company, $this->user->id);

        $company->roles()->attach([
            Role::findByName(RoleType::OWNER)->id,
            Role::findByName(RoleType::ADMIN)->id,
        ]);

        $uploadedFile = $this->getFile();
        $data = [
            'file' => $uploadedFile,
            'roles' => [
                Role::findByName(RoleType::OWNER)->id,
                Role::findByName(RoleType::ADMIN)->id,
            ],
            'users' => [],
            'pages' => [],
            'stories' => [],
            'tickets' => [],
        ];

        /* **************** send request  ********************/
        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id,
            $data,
            [],
            ['file' => $uploadedFile]
        );
        $this->seeStatusCode(201)->isJson();
        $json = $this->decodeResponseJson()['data'];

        /* **************** assertions  ********************/
        $result = 'jpg';
        $storage_name = ModelFile::find($json['id'])->storage_name;
        $extension = explode('.', $storage_name);

        $this->assertEquals(end($extension), $result);
    }

    /** @test */
    public function fileUsers_UserNotDuplication()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $this->company_id = $company->id;
        $project = $this->getProject($company, $this->user->id);

        $company->roles()->attach([
            Role::findByName(RoleType::OWNER)->id,
        ]);
        $uploadedFile = $this->getFile();

        $user_2 = factory(User::class)->create();
        $project->users()->attach([$user_2->id]);
        /* **************** send request  ********************/
        $data = [
            'file' => $uploadedFile,
            'users' => [
                $user_2->id,
                $user_2->id,
            ],
            'roles' => [],
            'pages' => [],
            'stories' => [],
            'tickets' => [],
        ];
        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id,
            $data,
            [],
            ['file' => $uploadedFile]
        );

        $this->seeStatusCode(201)->isJson();
        $json = $this->decodeResponseJson()['data'];

        /* **************** assertions  ********************/
        $users = ModelFile::find($json['id'])->users->pluck('id');

        $this->assertEquals(1, $users->count());
        $this->assertTrue($users->contains($user_2->id));
    }

    /** @test */
    public function fileUsers_validMultiUsers()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $this->company_id = $company->id;
        $project = $this->getProject($company, $this->user->id);

        $company->roles()->attach([
            Role::findByName(RoleType::OWNER)->id,
        ]);
        $uploadedFile = $this->getFile();

        $user_2 = factory(User::class)->create();
        $user_3 = factory(User::class)->create();
        $project->users()->attach([$user_2->id, $user_3->id]);

        /* **************** send request  ********************/
        $data = [
            'file' => $uploadedFile,
            'roles' => [
                Role::findByName(RoleType::OWNER)->id,
            ],
            'users' => [
                $user_2->id,
                $user_3->id,
            ],
            'pages' => [],
            'stories' => [],
            'tickets' => [],
        ];
        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id,
            $data,
            [],
            ['file' => $uploadedFile]
        );

        $this->seeStatusCode(201)->isJson();
        $json = $this->decodeResponseJson()['data'];

        /* **************** assertions  ********************/
        $users = ModelFile::find($json['id'])->users->pluck('id');

        $this->assertEquals(2, $users->count());
        $this->assertTrue($users->contains($user_2->id));
        $this->assertTrue($users->contains($user_3->id));
    }

    /** @test */
    public function fileUsers_validEmptyUsers()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $this->company_id = $company->id;
        $project = $this->getProject($company, $this->user->id);
        factory(File::class)->create([
            'project_id' => $project->id,
            'size' => 1024 * 1024 * 1024 * 5,
        ]);

        $company->roles()->attach([
            Role::findByName(RoleType::OWNER)->id,
        ]);
        $uploadedFile = $this->getFile();

        /* **************** send request  ********************/
        $data = [
            'file' => $uploadedFile,
            'users' => [],
            'roles' => [],
            'pages' => [],
            'stories' => [],
            'tickets' => [],

        ];
        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id,
            $data,
            [],
            ['file' => $uploadedFile]
        );

        $this->seeStatusCode(201)->isJson();
        $json = $this->decodeResponseJson()['data'];

        /* **************** assertions  ********************/
        $users = ModelFile::find($json['id'])->users->pluck('id');

        $this->assertEquals(0, $users->count());
    }

    /** @test */
    public function fileRoles_validStructure()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $this->company_id = $company->id;
        $project = $this->getProject($company, $this->user->id);

        $uploadedFile = $this->getFile();

        /* **************** send request  ********************/
        $data = [
            'file' => $uploadedFile,
            'users' => [],
            'roles' => [],
            'pages' => [],
            'stories' => [],
            'tickets' => [],
        ];
        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id,
            $data,
            [],
            ['file' => $uploadedFile]
        );
        $this->seeStatusCode(201)->isJson();

        /* **************** assertions  ********************/
        $this->assertCorrectStructure($this->decodeResponseJson()['data']);
    }

    /** @test */
    public function rolePermissionRemove_validPermissionDeveloper()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::DEVELOPER);
        $this->company_id = $company->id;
        auth()->loginUsingId($this->user->id);

        $user_2 = factory(User::class)->create();
        $project_2 = factory(Project::class)->create([
            'id' => 9998,
            'company_id' => $this->company->id,
            'name' => 'Test remove project 9998',
            'short_name' => 'trp 9998',
        ]);
        $project_2->users()->attach($user_2->id);
        $project_2->users()
            ->attach($this->user->id, ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);

        $uploadedFile = $this->getFile();

        /* **************** send request  ********************/
        $data = [
            'file' => $uploadedFile,
            'users' => [],
            'roles' => [],
            'pages' => [],
            'stories' => [],
            'tickets' => [],
        ];
        $this->call(
            'post',
            '/projects/' . $project_2->id . '/files?selected_company_id=' . $company->id,
            $data,
            [],
            ['file' => $uploadedFile]
        );

        $this->seeStatusCode(201)->isJson();

        /* **************** assertions  ********************/
        $this->assertCorrectStructure($this->decodeResponseJson()['data']);
    }

    /** @test */
    public function add_to_file_duplicated_resources_return_single_resources()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $this->company_id = $company->id;
        $project = $this->getProject($company, $this->user->id);

        $company->roles()->attach([
            Role::findByName(RoleType::OWNER)->id,
        ]);
        $uploadedFile = $this->getFile();

        $page = factory(KnowledgePage::class)->create(['project_id' => $project->id]);
        $story = factory(Story::class)->create(['project_id' => $project->id]);
        $ticket = factory(Ticket::class)->create(['project_id' => $project->id]);

        /* **************** send request  ********************/
        $data = [
            'file' => $uploadedFile,
            'pages' => [$page->id, $page->id],
            'stories' => [$story->id, $story->id],
            'tickets' => [$ticket->id, $ticket->id],
            'users' => [],
            'roles' => [],
        ];

        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id,
            $data,
            [],
            ['file' => $uploadedFile]
        );
        $this->seeStatusCode(201)->isJson();

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];
        $model = ModelFile::find($json['id']);

        $model_pages = $model->pages->pluck('id');
        $this->assertEquals(1, $model_pages->count());
        $this->assertTrue($model_pages->contains($page->id));

        $model_stories = $model->stories->pluck('id');
        $this->assertEquals(1, $model_stories->count());
        $this->assertTrue($model_stories->contains($story->id));

        $model_tickets = $model->tickets->pluck('id');
        $this->assertEquals(1, $model_tickets->count());
        $this->assertTrue($model_tickets->contains($ticket->id));
    }

    /** @test */
    public function add_to_file_two_resources_return_valid()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $this->company_id = $company->id;
        $project = $this->getProject($company, $this->user->id);

        $company->roles()->attach([
            Role::findByName(RoleType::OWNER)->id,
        ]);
        $uploadedFile = $this->getFile();

        $page_1 = factory(KnowledgePage::class)->create(['project_id' => $project->id]);
        $page_2 = factory(KnowledgePage::class)->create(['project_id' => $project->id]);
        $story_1 = factory(Story::class)->create(['project_id' => $project->id]);
        $story_2 = factory(Story::class)->create(['project_id' => $project->id]);
        $ticket_1 = factory(Ticket::class)->create(['project_id' => $project->id]);
        $ticket_2 = factory(Ticket::class)->create(['project_id' => $project->id]);

        /* **************** send request  ********************/
        $data = [
            'file' => $uploadedFile,
            'pages' => [$page_1->id, $page_2->id],
            'stories' => [$story_1->id, $story_2->id],
            'tickets' => [$ticket_1->id, $ticket_2->id],
            'users' => [],
            'roles' => [],
        ];

        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id,
            $data,
            [],
            ['file' => $uploadedFile]
        );
        $this->seeStatusCode(201)->isJson();

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];
        $model = ModelFile::find($json['id']);

        $model_pages = $model->pages->pluck('id');
        $this->assertEquals(2, $model_pages->count());
        $this->assertTrue($model_pages->contains($page_1->id));
        $this->assertTrue($model_pages->contains($page_2->id));

        $model_stories = $model->stories->pluck('id');
        $this->assertEquals(2, $model_stories->count());
        $this->assertTrue($model_stories->contains($story_1->id));
        $this->assertTrue($model_stories->contains($story_2->id));

        $model_tickets = $model->tickets->pluck('id');
        $this->assertEquals(2, $model_tickets->count());
        $this->assertTrue($model_tickets->contains($ticket_1->id));
        $this->assertTrue($model_tickets->contains($ticket_2->id));
    }

    /** @test */
    public function add_to_file_resources_return_valid()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $this->company_id = $company->id;
        $project = $this->getProject($company, $this->user->id);

        $company->roles()->attach([
            Role::findByName(RoleType::OWNER)->id,
        ]);
        $uploadedFile = $this->getFile();

        $page = factory(KnowledgePage::class)->create(['project_id' => $project->id]);
        $story = factory(Story::class)->create(['project_id' => $project->id]);
        $ticket = factory(Ticket::class)->create(['project_id' => $project->id]);

        /* **************** send request  ********************/
        $data = [
            'file' => $uploadedFile,
            'roles' => [Role::findByName(RoleType::OWNER)->id],
            'users' => [$this->user->id],
            'pages' => [$page->id],
            'stories' => [$story->id],
            'tickets' => [$ticket->id],
        ];

        $this->call(
            'post',
            '/projects/' . $project->id . '/files?selected_company_id=' . $company->id,
            $data,
            [],
            ['file' => $uploadedFile]
        );

        $this->seeStatusCode(201)->isJson();

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];
        $file = ModelFile::find($json['id']);

        $this->assertEquals([
            'id' => $file['id'],
            'project_id' => $file['project_id'],
            'user_id' => $file['user_id'],
            'name' => $file['name'],
            'size' => $file['size'],
            'extension' => $file['extension'],
            'description' => $file['description'],
            'created_at' => $file['created_at'],
            'updated_at' => $file['updated_at'],
            'owner' => [
                'data' => [
                    'id' => $this->user->id,
                    'email' => $this->user->email,
                    'first_name' => $this->user->first_name,
                    'last_name' => $this->user->last_name,
                    'avatar' => $this->user->avatar,
                ],
            ],
            'roles' => [
                'data' => [
                    [
                        'id' => Role::findByName(RoleType::OWNER)->id,
                        'name' => Role::findByName(RoleType::OWNER)->name,
                    ],
                ],
            ],
            'users' => [
                'data' => [
                    [
                        'id' => $this->user->id,
                        'email' => $this->user->email,
                        'first_name' => $this->user->first_name,
                        'last_name' => $this->user->last_name,
                        'avatar' => $this->user->avatar,
                    ],
                ],
            ],
            'pages' => [
                'data' => [
                    [
                        'id' => $page->id,
                        'name' => $page->name,
                    ],
                ],
            ],
            'stories' => [
                'data' => [
                    [
                        'id' => $story->id,
                        'name' => $story->name,
                    ],
                ],
            ],
            'tickets' => [
                'data' => [
                    [
                        'id' => $ticket->id,
                        'name' => $ticket->name,
                        'title' => $ticket->title,
                    ],
                ],
            ],
        ], $json);
    }

    /**
     * Check the json structure with values.
     *
     * @param $json
     */
    protected function assertCorrectStructure($json)
    {
        $file = ModelFile::find($json['id']);

        $this->assertEquals([
            'id' => $file['id'],
            'project_id' => $file['project_id'],
            'user_id' => $file['user_id'],
            'owner' => [
                'data' => [
                    'id' => $this->user->id,
                    'email' => $this->user->email,
                    'first_name' => $this->user->first_name,
                    'last_name' => $this->user->last_name,
                    'avatar' => $this->user->avatar,
                ],
            ],
            'name' => $file['name'],
            'size' => $file['size'],
            'extension' => $file['extension'],
            'description' => $file['description'],
            'created_at' => $file['created_at'],
            'updated_at' => $file['updated_at'],
            'roles' => [
                'data' => [],
            ],
            'users' => [
                'data' => [],
            ],
            'pages' => [
                'data' => [],
            ],
            'stories' => [
                'data' => [],
            ],
            'tickets' => [
                'data' => [],
            ],
        ], $json);
    }
}
