<?php

namespace Tests\Feature\App\Modules\Knowledge\Http\Controllers\KnowledgePageController\Show;

use App\Helpers\ErrorCode;
use App\Models\Db\File;
use App\Models\Db\Knowledge\KnowledgeDirectory;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\KnowledgePageComment;
use App\Models\Db\Project;
use App\Models\Db\Role;
use App\Models\Db\Story;
use App\Models\Db\User;
use App\Models\Other\KnowledgePageCommentType;
use App\Models\Other\RoleType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\BrowserKitTestCase;
use Tests\Helpers\ProjectHelper;

class KnowledgePageControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;
    use ProjectHelper;
    use KnowledgePageControllerTrait;

    protected $company;
    protected $now;
    protected $project;
    protected $request_data;
    protected $developer;

    /**
     * @var Collection
     */
    protected $stories;

    public function setUp():void
    {
        parent::setUp();
        $this->now = Carbon::parse('2017-01-02 08:15:12');
        Carbon::setTestNow($this->now);

        $this->createUser();
        $this->be($this->user);

        $this->company = $this->createCompanyWithRole(RoleType::ADMIN);
        $this->developer = factory(User::class)->create();
        $this->assignUsersToCompany(User::where('id', $this->developer->id)->get(), $this->company);

        $this->project = factory(Project::class)->create(['company_id' => $this->company->id]);

        $this->stories = factory(Story::class, 6)->create(['project_id' => $this->project->id]);

        $this->request_data = [
            'knowledge_directory_id' => null,
            'name' => 'Test',
            'content' => 'Lorem ipsum',
            'pinned' => false,
            'stories' => [$this->stories[0]->id, $this->stories[2]->id, $this->stories[3]->id],
        ];
    }

    /** @test */
    public function show_it_shows_page_for_admin()
    {
        $this->setProjectRole();
        $page = factory(KnowledgePage::class)->create([
            'project_id' => $this->project->id,
            'name' => 'Test',
            'content' => 'Lorem ipsum',
            'creator_id' => $this->user->id,
            'knowledge_directory_id' => null,
        ]);

        $this->get(
            'project/' . $this->project->id .
            '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData();

        $this->verifyShowPage($response->data, $page, $this->user);
    }

    /** @test */
    public function show_it_shows_page_in_directory_for_developer()
    {
        $directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $page = factory(KnowledgePage::class)->create([
            'project_id' => $this->project->id,
            'name' => 'Test',
            'content' => 'Lorem ipsum',
            'creator_id' => $this->user->id,
            'knowledge_directory_id' => $directory->id,
        ]);

        $project_developer = $this->developer;
        $admin_role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $admin_role_id]);
        $this->be($project_developer);

        $this->get(
            'project/' . $this->project->id .
            '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData();

        $this->verifyShowPage($response->data, $page, $this->user, $directory);
    }

    /** @test */
    public function show_it_shows_page_with_users_roles_permissions_and_stories__and_files_in_directory_for_developer()
    {
        $directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $page = factory(KnowledgePage::class)->create([
            'project_id' => $this->project->id,
            'name' => 'Test',
            'content' => 'Lorem ipsum',
            'creator_id' => $this->user->id,
            'knowledge_directory_id' => $directory->id,
        ]);

        $project_developer = $this->developer;
        $admin_role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $admin_role_id]);
        $this->be($project_developer);

        $files = factory(File::class, 4)->make();
        $page->files()->saveMany($files);

        $stories = [$this->stories[0]->id, $this->stories[3]->id, $this->stories[4]->id];

        $page->stories()->attach($stories);

        $roles_ids = [
            Role::findByName(RoleType::ADMIN)->id,
            Role::findByName(RoleType::DEVELOPER)->id,
        ];
        $page->roles()->attach($roles_ids);
        $page->users()->attach([
            $this->user->id,
            $project_developer->id,
        ]);

        $this->get(
            'project/' . $this->project->id .
            '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData()->data;

        $this->verifyShowPage($response, $page, $this->user, $directory);
        $this->assertEquals(
            collect($response->users->data)->pluck('id')->toArray(),
            [$this->user->id, $project_developer->id]
        );
        $this->assertEquals(collect($response->roles->data)->pluck('id')->toArray(), $roles_ids);
        $this->assertEqualsCanonicalizing($stories, collect($response->stories->data)->pluck('id')->toArray(), '');
        $this->assertCount(count($files), $response->files->data);
        $this->assertEqualsCanonicalizing($files->pluck('id')->all(), collect($response->files->data)->pluck('id')->toArray(), '');
    }

    /** @test */
    public function show_it_shows_page_in_directory_with_roles_permissions_with_error()
    {
        $directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $page = factory(KnowledgePage::class)->create([
            'project_id' => $this->project->id,
            'name' => 'Test',
            'content' => 'Lorem ipsum',
            'creator_id' => $this->user->id,
            'knowledge_directory_id' => $directory->id,
        ]);

        $project_developer = $this->developer;
        $admin_role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $admin_role_id]);
        $this->be($project_developer);

        $roles_ids = [
            Role::findByName(RoleType::ADMIN)->id,
            Role::findByName(RoleType::CLIENT)->id,
        ];
        $directory->roles()->attach($roles_ids);

        $this->get(
            'project/' . $this->project->id .
            '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id
        );
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function show_it_shows_page_with_roles_permissions_in_directory_tih_error()
    {
        $directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $page = factory(KnowledgePage::class)->create([
            'project_id' => $this->project->id,
            'name' => 'Test',
            'content' => 'Lorem ipsum',
            'creator_id' => $this->user->id,
            'knowledge_directory_id' => $directory->id,
        ]);

        $project_developer = $this->developer;
        $admin_role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $admin_role_id]);
        $this->be($project_developer);

        $roles_ids = [
            Role::findByName(RoleType::ADMIN)->id,
            Role::findByName(RoleType::CLIENT)->id,
        ];
        $page->roles()->attach($roles_ids);

        $this->get(
            'project/' . $this->project->id .
            '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id
        );
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /**
     * @feature Knowledge Page
     * @scenario Show Page
     * @case Involved list contains two position
     * @expectation Return valid involved list
     * @test
     */
    public function show_involvedListContainsTwoPosition()
    {
        //GIVEN
        $this->setProjectRole();

        $page = $this->createKnowledgePage([
            'project_id' => $this->project->id,
        ]);

        $user_1 = $this->createNewUser([
            'first_name' => 'test first name 1',
            'last_name' => 'test last name 1',
            'avatar' => 'avatar 1',
        ]);

        $user_2 = $this->createNewUser([
            'first_name' => 'test first name 2',
            'last_name' => 'test last name 2',
            'avatar' => 'avatar 2',
        ]);

        $involved_1 = $this->createInvolved([
            'user_id' => $user_1->id,
            'company_id' => $this->company->id,
            'project_id' => $this->project->id,
        ]);

        $involved_2 = $this->createInvolved([
            'user_id' => $user_2->id,
            'company_id' => $this->company->id,
            'project_id' => $this->project->id,
        ]);

        $page->involved()->save($involved_1);
        $page->involved()->save($involved_2);

        //WHEN
        $this->get(
            'project/' . $this->project->id .
            '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id
        );

        //THEN
        $this->seeStatusCode(200);
        $involved = $this->decodeResponseJson()['data']['involved']['data'];

        $this->assertEquals($user_1->id, $involved[0]['user_id']);
        $this->assertSame('test first name 1', $involved[0]['first_name']);
        $this->assertSame('test last name 1', $involved[0]['last_name']);
        $this->assertSame('avatar 1', $involved[0]['avatar']);

        $this->assertEquals($user_2->id, $involved[1]['user_id']);
        $this->assertSame('test first name 2', $involved[1]['first_name']);
        $this->assertSame('test last name 2', $involved[1]['last_name']);
        $this->assertSame('avatar 2', $involved[1]['avatar']);
    }

    /**
     * @feature Knowledge Page
     * @scenario Show Page
     * @case Two Comments for page exist
     *
     * @test
     * @Expectation Show comments
     */
    public function show_commentForPageExist()
    {
        //GIVEN
        $page = factory(KnowledgePage::class)->create([
            'project_id' => $this->project->id,
            'creator_id' => $this->user->id,
        ]);

        $project_developer = $this->developer;
        $admin_role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $admin_role_id]);
        $this->be($project_developer);

        $comment_1 = factory(KnowledgePageComment::class)->create([
            'knowledge_page_id' => $page->id,
            'user_id' => $this->user->id,
            'type' => KnowledgePageCommentType::GLOBAL,
            'ref' => null,
            'text' => 'comment_1 text',
        ]);
        $comment_2 = factory(KnowledgePageComment::class)->create([
            'knowledge_page_id' => $page->id,
            'user_id' => $this->user->id,
            'type' => KnowledgePageCommentType::INTERNAL,
            'ref' => '#ref',
            'text' => 'comment_2 text',
        ]);

        //WHEN
        $response = $this->get(
            'project/' . $this->project->id . '/pages/' . $page->id . '?selected_company_id=' . $this->company->id
        );

        //THEN
        $this->assertResponseStatus(200);
        $this->seeJsonStructure($this->getExpectedJsonStructure(), $this->response->getContent());

        $response = $this->response->getData();
        $this->assertSame(2, count($response->data->comments->data));

        $comments_1_data = $response->data->comments->data[0];
        $this->assertSame($comment_1->id, $comments_1_data->id);
        $this->assertSame(KnowledgePageCommentType::GLOBAL, $comments_1_data->type);
        $this->assertNull($comments_1_data->ref);
        $this->assertSame('comment_1 text', $comments_1_data->text);
        $this->assertSame($comment_1->user_id, $comments_1_data->user_id);

        $comments_2_data = $response->data->comments->data[1];
        $this->assertSame($comment_2->id, $comments_2_data->id);
        $this->assertSame(KnowledgePageCommentType::INTERNAL, $comments_2_data->type);
        $this->assertSame('#ref', $comments_2_data->ref);
        $this->assertSame('comment_2 text', $comments_2_data->text);
        $this->assertSame($comment_1->user_id, $comments_1_data->user_id);
    }
}
