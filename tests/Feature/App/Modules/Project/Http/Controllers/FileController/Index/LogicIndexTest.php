<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\FileController\Index;

use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\Project;
use App\Models\Db\Role;
use App\Models\Db\Story;
use App\Models\Db\Ticket;
use App\Models\Other\RoleType;
use App\Models\Db\File as ModelFile;
use App\Models\Db\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\ProjectHelper;
use Tests\BrowserKitTestCase;

class LogicIndexTest extends BrowserKitTestCase
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

    /** @test */
    public function user_has_permission_to_file_return_valid()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::DEALER);
        $project = $this->getProject($company, $this->user->id, RoleType::DEALER);
        $file = $this->prepareFileDatabase(
            1,
            $project->id,
            '1.jpg',
            RoleType::DEALER
        );

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id .
            '/files?selected_company_id=' .
            $company->id)->seeStatusCode(200)->seeJsonStructure($this->data())->isJson();

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];
        $this->assertData([$file], $json);
    }

    /** @test */
    public function user_has_permission_to_own_files_return_valid()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::DEALER);
        $project = $this->getProject($company, $this->user->id, RoleType::DEALER);

        // prepare records in table files
        $file_1 = $this->prepareFileDatabase(
            1,
            $project->id,
            '1.jpg',
            RoleType::DEALER
        );
        $file_2 = $this->prepareFileDatabase(
            2,
            $project->id,
            '2.jpg',
            RoleType::DEALER
        );
        //temp file
        $file_3 = $this->prepareFileDatabase(
            3,
            $project->id,
            '3.jpg',
            RoleType::DEALER,
            true
        );

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id .
            '/files?fileable_type=&fileable_id=&selected_company_id=' .
            $company->id)->seeStatusCode(200)->seeJsonStructure($this->data())->isJson();

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];
        $this->assertData([$file_1, $file_2], $json, 2);
    }

    /** @test */
    public function user_has_permission_to_different_files_return_valid()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::DEALER);
        $project = $this->getProject($company, $this->user->id, RoleType::DEALER);

        // prepare records in table files
        $file_1 = $this->prepareFileDatabase(
            1,
            $project->id,
            '1.jpg',
            RoleType::DEALER
        );

        // prepare fake date
        $user_2 = factory(user::class)->create();
        $file_2 = factory(ModelFile::class)->create([
            'id' => 2,
            'project_id' => $project->id,
            'storage_name' => '2.jpg',
        ]);
        $file_2->roles()->attach(Role::findByName(RoleType::DEVELOPER)->id);
        $file_2->users()->attach($user_2->id);

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id .
            '/files?fileable_type=&fileable_id=&selected_company_id=' .
            $company->id)->seeStatusCode(200)->seeJsonStructure($this->data())->isJson();

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];
        $this->assertData([$file_1], $json);
    }

    /** @test */
    public function permission_to_remove_when_invalid_roles_and_valid_users_return_valid()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::DEALER);
        $project = $this->getProject($company, $this->user->id, RoleType::DEALER);

        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'storage_name' => '1.jpg',
        ]);
        $file->roles()->attach(Role::findByName(RoleType::DEVELOPER)->id);
        $file->users()->attach($this->user->id);

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id .
            '/files?fileable_type=&fileable_id=&selected_company_id=' .
            $company->id)->seeStatusCode(200)->seeJsonStructure($this->data())->isJson();

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];
        $this->assertData([$file], $json);
    }

    /** @test */
    public function permission_to_remove_when_valid_roles_and_invalid_users_return_valid()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::DEALER);
        $project = $this->getProject($company, $this->user->id, RoleType::DEALER);

        // prepare fake date
        $user_2 = factory(user::class)->create();
        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'storage_name' => '1.jpg',
        ]);

        $file->roles()->attach(Role::findByName(RoleType::DEALER)->id);
        $file->users()->attach($user_2->id);

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id .
            '/files?fileable_type=&fileable_id=&selected_company_id=' .
            $company->id)->seeStatusCode(200)->seeJsonStructure($this->data())->isJson();

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];
        $this->assertData([$file], $json);
    }

    /** @test */
    public function permission_to_remove_when_invalid_roles_and_invalid_users_return_invalid()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::DEALER);
        $project = $this->getProject($company, $this->user->id, RoleType::DEALER);

        // prepare fake date
        $user_2 = factory(user::class)->create();
        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'storage_name' => '1.jpg',
        ]);

        $file->roles()->attach(Role::findByName(RoleType::DEVELOPER)->id);
        $file->users()->attach($user_2->id);

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id .
            '/files?fileable_type=&fileable_id=&selected_company_id=' .
            $company->id)->seeStatusCode(200);

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];
        $this->assertEquals([], $json);
    }

    /** @test */
    public function permission_to_remove_assigned_owner_return_valid()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id, RoleType::DEVELOPER);

        $user_2 = factory(user::class)->create();
        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'storage_name' => '1.jpg',
        ]);

        $file->roles()->attach(Role::findByName(RoleType::DEVELOPER)->id);
        $file->users()->attach($user_2->id);

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id .
            '/files?fileable_type=&fileable_id=&selected_company_id=' .
            $company->id)->seeStatusCode(200)->seeJsonStructure($this->data())->isJson();

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];
        $this->assertData([$file], $json);
    }

    /** @test */
    public function listed_files_assigned_to_other_project_return_empty_array()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::DEALER);
        $project = $this->getProject($company, $this->user->id, RoleType::DEALER);
        $project_2 = factory(Project::class)->create();

        $this->prepareFileDatabase(
            1,
            $project_2->id,
            '1.jpg',
            RoleType::DEALER
        );

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id .
            '/files?fileable_type=&fileable_id=&selected_company_id='
            . $company->id)->seeStatusCode(200);

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];
        $this->assertEquals(0, count($json));
    }

    /** @test */
    public function return_data_is_valid()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::DEALER);
        $project = $this->getProject($company, $this->user->id, RoleType::DEALER);

        $file = factory(ModelFile::class)->create([
            'project_id' => $project->id,
            'name' => 'Example file',
            'size' => 1000,
            'extension' => 'jpg',
            'description' => 'Example description',
        ]);
        $file->roles()->attach(Role::findByName(RoleType::DEALER)->id);
        $file->users()->attach($this->user->id);

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id .
            '/files?fileable_type=&fileable_id=&selected_company_id=' .
            $company->id)->seeStatusCode(200);

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];
        $this->assertData([$file], $json);
    }

    /** @test */
    public function return_json_structure_is_valid()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::DEALER);
        $project = $this->getProject($company, $this->user->id, RoleType::DEALER);
        $file = $this->prepareFileDatabase(
            1,
            $project->id,
            '1.jpg',
            RoleType::DEALER
        );

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id .
            '/files?fileable_type=&fileable_id=&selected_company_id=' .
            $company->id)->seeStatusCode(200)->seeJsonStructure($this->data())->isJson();

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];

        $this->assertEquals([
            0 => [
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
                    'data' => $this->getExpectedUserSimpleResponse($this->user),
                ],
            ],
        ], $json);
    }

    /** @test */
    public function return_json_search_in_name()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::DEALER);
        $project = $this->getProject($company, $this->user->id, RoleType::DEALER);
        $file = $this->prepareFileDatabase(
            1,
            $project->id,
            'rrter.jpg',
            RoleType::DEALER
        );
        $this->prepareFileDatabase(2, $project->id, '1.jpg', RoleType::DEALER);

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id .
            '/files?search=te&selected_company_id=' .
            $company->id)->seeStatusCode(200);

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];

        $this->assertEquals([
            0 => [
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
                    'data' => $this->getExpectedUserSimpleResponse($this->user),
                ],
            ],
        ], $json);
    }

    /** @test */
    public function return_json_search_in_description()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::DEALER);
        $project = $this->getProject($company, $this->user->id, RoleType::DEALER);
        $file = $this->prepareFileDatabase(
            1,
            $project->id,
            '1.jpg',
            RoleType::DEALER,
            false,
            'rrte44'
        );
        $this->prepareFileDatabase(2, $project->id, '2.jpg', RoleType::DEALER, false, 'asdasd');

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id .
            '/files?search=te&selected_company_id=' .
            $company->id)->seeStatusCode(200);

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];

        $this->assertEquals([
            0 => [
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
                    'data' => $this->getExpectedUserSimpleResponse($this->user),
                ],
            ],
        ], $json);
    }

    /** @test */
    public function return_json_filter_by_images()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::DEALER);
        $project = $this->getProject($company, $this->user->id, RoleType::DEALER);
        $file = $this->prepareFileDatabase(1, $project->id, '1.jpg', RoleType::DEALER);
        $this->prepareFileDatabase(2, $project->id, '2.doc', RoleType::DEALER);

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id .
            '/files?file_type=images&selected_company_id=' .
            $company->id)->seeStatusCode(200);

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];

        $this->assertEquals([
            0 => [
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
                    'data' => $this->getExpectedUserSimpleResponse($this->user),
                ],
            ],
        ], $json);
    }

    /** @test */
    public function return_json_filter_by_other_file()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::DEALER);
        $project = $this->getProject($company, $this->user->id, RoleType::DEALER);
        $file = $this->prepareFileDatabase(1, $project->id, '1.zip', RoleType::DEALER);
        $this->prepareFileDatabase(2, $project->id, '2.doc', RoleType::DEALER);

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id .
            '/files?file_type=other&selected_company_id=' .
            $company->id)->seeStatusCode(200);

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];

        $this->assertEquals([
            0 => [
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
                    'data' => $this->getExpectedUserSimpleResponse($this->user),
                ],
            ],
        ], $json);
    }

    /** @test */
    public function return_files_assigned_to_specific_page()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id, RoleType::OWNER);

        /* **************** prepare files records in the database ********************/
        // prepare file not assigned to resources
        $file_1 = $this->createFile($project, 1);

        // prepare file assigned to resources
        $file_2 = $this->createFile($project, 2);

        $file_3 = $this->createFile($project, 3);

        // prepare correct resource
        $page_1 = factory(KnowledgePage::class)->create(['id' => 1000, 'project_id' => $project->id]);
        $page_2 = factory(KnowledgePage::class)->create(['id' => 2000, 'project_id' => $project->id]);
        $file_2->pages()->attach([$page_1->id]);
        $file_3->pages()->attach([$page_2->id]);

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id .
            '/files?fileable_type=knowledge_pages&fileable_id=' . $page_1->id . '&selected_company_id=' .
            $company->id)->seeStatusCode(200)->seeJsonStructure($this->data())->isJson();

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];
        $this->assertEquals(1, count($json));

        $this->assertEquals([
            0 => [
                'id' => $file_2['id'],
                'project_id' => $file_2['project_id'],
                'user_id' => $file_2['user_id'],
                'name' => $file_2['name'],
                'size' => $file_2['size'],
                'extension' => $file_2['extension'],
                'description' => $file_2['description'],
                'created_at' => $file_2['created_at'],
                'updated_at' => $file_2['updated_at'],
                'owner' => [
                    'data' => $this->getExpectedUserSimpleResponse($this->user),
                ],
            ],
        ], $json);
    }

    /** @test */
    public function return_files_assigned_to_pages()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id, RoleType::OWNER);

        /* **************** prepare files records in the database ********************/
        // prepare file not assigned to resources
        $file_1 = $this->createFile($project, 1);

        // prepare file assigned to resources
        $file_2 = $this->createFile($project, 2);

        $file_3 = $this->createFile($project, 3);

        // prepare correct resource
        $page_1 = factory(KnowledgePage::class)->create(['id' => 1000, 'project_id' => $project->id]);
        $page_2 = factory(KnowledgePage::class)->create(['id' => 2000, 'project_id' => $project->id]);
        $file_2->pages()->attach([$page_1->id]);
        $file_3->pages()->attach([$page_2->id]);

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id .
            '/files?fileable_type=knowledge_pages&selected_company_id=' .
            $company->id)->seeStatusCode(200)->seeJsonStructure($this->data())->isJson();

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];
        $this->assertEquals(2, count($json));

        $this->assertEquals([
            0 => [
                'id' => $file_2['id'],
                'project_id' => $file_2['project_id'],
                'user_id' => $file_2['user_id'],
                'name' => $file_2['name'],
                'size' => $file_2['size'],
                'extension' => $file_2['extension'],
                'description' => $file_2['description'],
                'created_at' => $file_2['created_at'],
                'updated_at' => $file_2['updated_at'],
                'owner' => [
                    'data' => $this->getExpectedUserSimpleResponse($this->user),
                ],
            ],
            1 => [
                'id' => $file_3['id'],
                'project_id' => $file_3['project_id'],
                'user_id' => $file_3['user_id'],
                'name' => $file_3['name'],
                'size' => $file_3['size'],
                'extension' => $file_3['extension'],
                'description' => $file_3['description'],
                'created_at' => $file_3['created_at'],
                'updated_at' => $file_3['updated_at'],
                'owner' => [
                    'data' => $this->getExpectedUserSimpleResponse($this->user),
                ],
            ],
        ], $json);
    }

    /** @test */
    public function return_files_assigned_to_specific_story()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id, RoleType::OWNER);

        /* **************** prepare files records in the database ********************/
        // prepare file not assigned to resources
        $file_1 = $this->createFile($project, 1);

        // prepare file assigned to resources
        $file_2 = $this->createFile($project, 2);

        $file_3 = $this->createFile($project, 3);

        // prepare correct resource
        $story_1 = factory(Story::class)->create(['id' => 1000, 'project_id' => $project->id]);
        $story_2 = factory(Story::class)->create(['id' => 2000, 'project_id' => $project->id]);
        $file_2->stories()->attach([$story_1->id]);
        $file_3->stories()->attach([$story_2->id]);

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id .
            '/files?fileable_type=stories&fileable_id=' . $story_1->id . '&selected_company_id=' .
            $company->id)->seeStatusCode(200)->seeJsonStructure($this->data())->isJson();

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];
        $this->assertEquals(1, count($json));

        $this->assertEquals([
            0 => [
                'id' => $file_2['id'],
                'project_id' => $file_2['project_id'],
                'user_id' => $file_2['user_id'],
                'name' => $file_2['name'],
                'size' => $file_2['size'],
                'extension' => $file_2['extension'],
                'description' => $file_2['description'],
                'created_at' => $file_2['created_at'],
                'updated_at' => $file_2['updated_at'],
                'owner' => [
                    'data' => $this->getExpectedUserSimpleResponse($this->user),
                ],
            ],
        ], $json);
    }

    /** @test */
    public function return_files_assigned_to_stories()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id, RoleType::OWNER);

        /* **************** prepare files records in the database ********************/
        // prepare file not assigned to resources
        $file_1 = $this->createFile($project, 1);

        // prepare file assigned to resources
        $file_2 = $this->createFile($project, 2);

        $file_3 = $this->createFile($project, 3);

        // prepare correct resource
        $story_1 = factory(Story::class)->create(['id' => 1000, 'project_id' => $project->id]);
        $story_2 = factory(Story::class)->create(['id' => 2000, 'project_id' => $project->id]);
        $file_2->stories()->attach([$story_1->id]);
        $file_3->stories()->attach([$story_2->id]);

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id .
            '/files?fileable_type=stories&fileable_id=&selected_company_id=' .
            $company->id)->seeStatusCode(200)->seeJsonStructure($this->data())->isJson();

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];
        $this->assertEquals(2, count($json));

        $this->assertEquals([
            0 => [
                'id' => $file_2['id'],
                'project_id' => $file_2['project_id'],
                'user_id' => $file_2['user_id'],
                'name' => $file_2['name'],
                'size' => $file_2['size'],
                'extension' => $file_2['extension'],
                'description' => $file_2['description'],
                'created_at' => $file_2['created_at'],
                'updated_at' => $file_2['updated_at'],
                'owner' => [
                    'data' => $this->getExpectedUserSimpleResponse($this->user),
                ],
            ],
            1 => [
                'id' => $file_3['id'],
                'project_id' => $file_3['project_id'],
                'user_id' => $file_3['user_id'],
                'name' => $file_3['name'],
                'size' => $file_3['size'],
                'extension' => $file_3['extension'],
                'description' => $file_3['description'],
                'created_at' => $file_3['created_at'],
                'updated_at' => $file_3['updated_at'],
                'owner' => [
                    'data' => $this->getExpectedUserSimpleResponse($this->user),
                ],
            ],
        ], $json);
    }

    /** @test */
    public function return_files_assigned_to_specific_ticket()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id, RoleType::OWNER);

        /* **************** prepare files records in the database ********************/
        // prepare file not assigned to resources
        $file_1 = $this->createFile($project, 1);

        // prepare file assigned to resources
        $file_2 = $this->createFile($project, 2);

        $file_3 = $this->createFile($project, 3);

        // prepare correct resource
        $ticket_1 = factory(Ticket::class)->create(['id' => 1000, 'project_id' => $project->id]);
        $ticket_2 = factory(Ticket::class)->create(['id' => 2000, 'project_id' => $project->id]);
        $file_2->tickets()->attach([$ticket_1->id]);
        $file_3->tickets()->attach([$ticket_2->id]);

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id .
            '/files?fileable_type=tickets&fileable_id=' . $ticket_1->id . '&selected_company_id=' .
            $company->id)->seeStatusCode(200)->seeJsonStructure($this->data())->isJson();

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];
        $this->assertEquals(1, count($json));

        $this->assertEquals([
            0 => [
                'id' => $file_2['id'],
                'project_id' => $file_2['project_id'],
                'user_id' => $file_2['user_id'],
                'name' => $file_2['name'],
                'size' => $file_2['size'],
                'extension' => $file_2['extension'],
                'description' => $file_2['description'],
                'created_at' => $file_2['created_at'],
                'updated_at' => $file_2['updated_at'],
                'owner' => [
                    'data' => $this->getExpectedUserSimpleResponse($this->user),
                ],
            ],
        ], $json);
    }

    /** @test */
    public function return_files_assigned_to_tickets()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id, RoleType::OWNER);

        /* **************** prepare files records in the database ********************/
        // prepare file not assigned to resources
        $file_1 = $this->createFile($project, 1);

        // prepare file assigned to resources
        $file_2 = $this->createFile($project, 2);

        $file_3 = $this->createFile($project, 3);

        // prepare correct resource
        $ticket_1 = factory(Ticket::class)->create(['id' => 1000, 'project_id' => $project->id]);
        $ticket_2 = factory(Ticket::class)->create(['id' => 2000, 'project_id' => $project->id]);
        $file_2->tickets()->attach([$ticket_1->id]);
        $file_3->tickets()->attach([$ticket_2->id]);

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id .
            '/files?fileable_type=tickets&fileable_id=&selected_company_id=' .
            $company->id)->seeStatusCode(200)->seeJsonStructure($this->data())->isJson();

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];
        $this->assertEquals(2, count($json));

        $this->assertEquals([
            0 => [
                'id' => $file_2['id'],
                'project_id' => $file_2['project_id'],
                'user_id' => $file_2['user_id'],
                'name' => $file_2['name'],
                'size' => $file_2['size'],
                'extension' => $file_2['extension'],
                'description' => $file_2['description'],
                'created_at' => $file_2['created_at'],
                'updated_at' => $file_2['updated_at'],
                'owner' => [
                    'data' => $this->getExpectedUserSimpleResponse($this->user),
                ],
            ],
            1 => [
                'id' => $file_3['id'],
                'project_id' => $file_3['project_id'],
                'user_id' => $file_3['user_id'],
                'name' => $file_3['name'],
                'size' => $file_3['size'],
                'extension' => $file_3['extension'],
                'description' => $file_3['description'],
                'created_at' => $file_3['created_at'],
                'updated_at' => $file_3['updated_at'],
                'owner' => [
                    'data' => $this->getExpectedUserSimpleResponse($this->user),
                ],
            ],
        ], $json);
    }

    /** @test */
    public function return_files_not_assigned_to_any_resources()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id, RoleType::OWNER);

        /* **************** prepare files records in the database ********************/
        // prepare file not assigned to resources
        $file_1 = $this->createFile($project, 1);

        // prepare file assigned to resources
        $file_2 = $this->createFile($project, 2);

        // prepare correct resource
        $ticket_1 = factory(Ticket::class)->create(['id' => 1000, 'project_id' => $project->id]);
        $file_2->tickets()->attach([$ticket_1->id]);

        $page_1 = factory(KnowledgePage::class)->create(['id' => 1000, 'project_id' => $project->id]);
        $file_2->pages()->attach([$page_1->id]);

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id .
            '/files?fileable_type=none&fileable_id=&selected_company_id=' .
            $company->id)->seeStatusCode(200)->seeJsonStructure($this->data())->isJson();

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];
        $this->assertEquals(1, count($json));

        $this->assertEquals([
            0 => [
                'id' => $file_1['id'],
                'project_id' => $file_1['project_id'],
                'user_id' => $file_1['user_id'],
                'name' => $file_1['name'],
                'size' => $file_1['size'],
                'extension' => $file_1['extension'],
                'description' => $file_1['description'],
                'created_at' => $file_1['created_at'],
                'updated_at' => $file_1['updated_at'],
                'owner' => [
                    'data' => $this->getExpectedUserSimpleResponse($this->user),
                ],
            ],
        ], $json);
    }

    /** @test */
    public function return_no_files_not_assigned_to_any_resources_if_user_doesnt_have_permission_for_this_file()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::DEVELOPER);
        $project = $this->getProject($company, $this->user->id, RoleType::DEALER);
        $other_user = factory(User::class)->create();

        /* **************** prepare files records in the database ********************/
        // prepare file not assigned to resources
        $file_1 = $this->createFile($project, 1);
        // here we make sure user has no permission for this file anymore (we assign other user)
        $file_1->users()->sync([$other_user->id]);

        // prepare file assigned to resources
        $file_2 = $this->createFile($project, 2);
        // here we make sure user has no permission for this file anymore (we assign other user)
        $file_2->users()->sync([$other_user->id]);

        // prepare correct resource
        $ticket_1 = factory(Ticket::class)->create(['id' => 1000, 'project_id' => $project->id]);
        $file_2->tickets()->attach([$ticket_1->id]);

        $page_1 = factory(KnowledgePage::class)->create(['id' => 1000, 'project_id' => $project->id]);
        $file_2->pages()->attach([$page_1->id]);

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id .
            '/files?fileable_type=none&fileable_id=&selected_company_id=' .
            $company->id)->seeStatusCode(200)->isJson();

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];
        $this->assertEquals(0, count($json));

        $this->assertEquals([], $json);
    }

    protected function createFile(Project $project, $id)
    {
        $file = factory(ModelFile::class)->create([
            'id' => $id,
            'project_id' => $project->id,
            'storage_name' => $id . '.jpg',
            'user_id' => $this->user->id,
        ]);
        $file->users()->attach($this->user->id);

        return $file;
    }

    /**
     * Json Structure.
     *
     * @return array
     */
    protected function data()
    {
        return [
            'data' => [
                [
                    'id',
                    'project_id',
                    'user_id',
                    'name',
                    'size',
                    'extension',
                    'description',
                    'created_at',
                    'updated_at',
                ],
            ],
            'meta' => [
                'pagination' => [
                    'total',
                    'count',
                    'per_page',
                    'current_page',
                    'total_pages',
                    'links',
                ],
            ],
        ];
    }

    /**
     * Compared returned records.
     */
    protected function assertData($files, $json, $count_json = 1)
    {
        $this->assertEquals($count_json, count($json));

        $i = 0;
        foreach ($files as $file) {
            $this->assertEquals($file['project_id'], $json[$i]['project_id']);
            $this->assertEquals($file['name'], $json[$i]['name']);
            $this->assertEquals($file['size'], $json[$i]['size']);
            $this->assertEquals($file['extension'], $json[$i]['extension']);
            $this->assertEquals($file['description'], $json[$i]['description']);
            $i++;
        }
    }
}
