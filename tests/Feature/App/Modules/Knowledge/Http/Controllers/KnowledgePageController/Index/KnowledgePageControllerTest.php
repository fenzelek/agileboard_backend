<?php

namespace Tests\Feature\App\Modules\Knowledge\Http\Controllers\KnowledgePageController\Index;

use App\Helpers\ErrorCode;
use App\Models\Db\Knowledge\KnowledgeDirectory;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\Project;
use App\Models\Db\Role;
use App\Models\Db\Story;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\BrowserKitTestCase;
use Tests\Helpers\ProjectHelper;

class KnowledgePageControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ProjectHelper;

    protected $company;
    protected $now;
    protected $project;
    protected $request_data;
    protected $developer;
    protected $directory;

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
    public function index_it_lists_pages_for_admin()
    {
        $pages = $this->indexSetUp();
        $this->setProjectRole();
        $this->get(
            'project/' . $this->project->id . '/pages/'
            . '?selected_company_id=' . $this->company->id
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData();

        $this->assertCount(6, $response->data);
        $this->assertEquals(
            $pages->sortBy('name')->pluck('id'),
            collect($response->data)->pluck('id')
        );

        $this->seeJsonStructure([
            'data' => [
                [
                    'id',
                    'project_id',
                    'name',
                    'created_at',
                    'updated_at',
                    'creator_id',
                    'knowledge_directory_id',
                    'pinned',
                    'deleted_at',
                ],
            ],
        ]);
    }

    /** @test */
    public function index_it_lists_pages_for_developer()
    {
        $pages = $this->indexSetUp();

        $project_developer = $this->developer;
        $role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $role_id]);
        $this->be($project_developer);

        $this->get(
            'project/' . $this->project->id . '/pages/'
            . '?selected_company_id=' . $this->company->id
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData();

        $this->assertCount(6, $response->data);
        $this->assertEquals(
            $pages->sortBy('name')->pluck('id'),
            collect($response->data)->pluck('id')
        );
    }

    /** @test */
    public function index_it_checks_role_permission_restriction()
    {
        $pages = $this->indexSetUp();

        $project_developer = $this->developer;
        $role_id = Role::findByName(RoleType::DEALER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $role_id]);
        $this->be($project_developer);

        $this->get(
            'project/' . $this->project->id . '/pages/'
            . '?selected_company_id=' . $this->company->id
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData();

        $this->assertCount(3, $response->data);
        $pages = $pages->filter(function ($val) {
            return $val->roles->isEmpty() && $val->knowledge_directory_id == null;
        });
        $this->assertEquals(
            $pages->sortBy('name')->pluck('id'),
            collect($response->data)->pluck('id')
        );
    }

    /** @test */
    public function index_it_checks_user_permission_restriction()
    {
        $pages = $this->indexSetUp();

        $user = factory(User::class)->create();
        $this->assignUsersToCompany(User::where('id', $user->id)->get(), $this->company);
        $role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $user->projects()->attach($this->project, ['role_id' => $role_id]);
        $this->be($user);

        $this->get(
            'project/' . $this->project->id . '/pages/'
            . '?selected_company_id=' . $this->company->id
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData();

        $this->assertCount(5, $response->data);
        $pages = $pages->filter(function ($val) {
            return $val->users->isEmpty();
        });
        $this->assertEquals(
            $pages->sortBy('name')->pluck('id'),
            collect($response->data)->pluck('id')
        );
    }

    /** @test */
    public function index_it_lists_pages_only_in_main_directory()
    {
        $pages = $this->indexSetUp();
        $this->setProjectRole();
        $this->get('project/' . $this->project->id . '/pages/'
            . '?selected_company_id=' . $this->company->id
            . '&knowledge_directory_id=0')->seeStatusCode(200)->isJson();
        $response = $this->response->getData();

        $this->assertCount(4, $response->data);
        $pages = $pages->filter(function ($val) {
            return $val->knowledge_directory_id == null;
        });
        $this->assertEquals(
            $pages->sortBy('name')->pluck('id'),
            collect($response->data)->pluck('id')
        );
    }

    /** @test */
    public function index_it_lists_pages_only_in_directory()
    {
        $pages = $this->indexSetUp();
        $this->setProjectRole();
        $this->get(
            'project/' . $this->project->id . '/pages/'
            . '?selected_company_id=' . $this->company->id
            . '&knowledge_directory_id=' . $this->directory->id
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData();

        $this->assertCount(2, $response->data);
        $pages = $pages->filter(function ($val) {
            return $val->knowledge_directory_id;
        });
        $this->assertEquals(
            $pages->sortBy('name')->pluck('id'),
            collect($response->data)->pluck('id')
        );
    }

    /** @test */
    public function index_it_lists_pages_only_in_directory_he_cant_access_with_error()
    {
        $pages = $this->indexSetUp();

        $project_developer = $this->developer;
        $role_id = Role::findByName(RoleType::CLIENT)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $role_id]);
        $this->be($project_developer);

        $this->get(
            'project/' . $this->project->id . '/pages/'
            . '?selected_company_id=' . $this->company->id
            . '&knowledge_directory_id=' . $this->directory->id
        )->seeStatusCode(401);
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function index_it_lists_pinned_pages_for_admin()
    {
        $pages = $this->indexSetUp();
        $this->setProjectRole();
        $this->get(
            'project/' . $this->project->id . '/pages/'
            . '?selected_company_id=' . $this->company->id
            . '&pinned=1'
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData();

        $this->assertCount(3, $response->data);
        $pages = $pages->filter(function ($val) {
            return $val->pinned;
        });
        $this->assertEquals(
            $pages->sortBy('name')->pluck('id'),
            collect($response->data)->pluck('id')
        );
        $this->assertEquals(
            $pages->sortBy('name')->pluck('name'),
            collect($response->data)->pluck('name')
        );
    }

    /** @test */
    public function index_it_checks_role_permission_restriction_for_pinned_pages()
    {
        $pages = $this->indexSetUp();

        $project_developer = $this->developer;
        $role_id = Role::findByName(RoleType::DEALER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $role_id]);
        $this->be($project_developer);

        $this->get(
            'project/' . $this->project->id . '/pages/'
            . '?selected_company_id=' . $this->company->id
            . '&pinned=1'
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData();

        $this->assertCount(1, $response->data);
        $pages = $pages->filter(function ($val) {
            return $val->roles->isEmpty() && $val->knowledge_directory_id == null && $val->pinned;
        });
        $this->assertEquals(
            $pages->sortBy('name')->pluck('id'),
            collect($response->data)->pluck('id')
        );
        $this->assertEquals(
            $pages->sortBy('name')->pluck('name'),
            collect($response->data)->pluck('name')
        );
    }

    /** @test */
    public function index_it_get_only_pages_with_given_story_id()
    {
        $pages = $this->indexSetUp();
        $this->setProjectRole();
        $stories = factory(Story::class, 2)->create();

        $pages[0]->stories()->sync([]);
        $pages[1]->stories()->sync([$stories[0]->id, $stories[1]->id]);
        $pages[2]->stories()->sync([$stories[0]->id]);
        $pages[3]->stories()->sync([$stories[1]->id]);
        $pages[4]->stories()->sync([$stories[1]->id, $stories[0]->id]);
        $pages[5]->stories()->sync([]);

        $this->get(
            'project/' . $this->project->id . '/pages/'
            . '?selected_company_id=' . $this->company->id . '&story_id=' . $stories[1]->id
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData();

        $this->assertCount(3, $response->data);
        $this->assertEquals($pages->filter(function ($value, $key) {
            return in_array($key, [1, 3, 4]);
        })->sortBy('name')->pluck('id'), collect($response->data)->pluck('id'));

        $this->seeJsonStructure([
            'data' => [
                [
                    'id',
                    'project_id',
                    'name',
                    'created_at',
                    'updated_at',
                    'creator_id',
                    'knowledge_directory_id',
                    'pinned',
                    'deleted_at',
                ],
            ],
        ]);
    }

    /** @test */
    public function index_search_pages()
    {
        $pages = [
            factory(KnowledgePage::class)->create([
                'name' => 'asd asda das',
                'content' => 'asd asda das',
                'project_id' => $this->project->id,
                'knowledge_directory_id' => null,
            ]),
            factory(KnowledgePage::class)->create([
                'name' => '1asd asda das',
                'content' => 'a test b',
                'project_id' => $this->project->id,
                'knowledge_directory_id' => null,
            ]),
            factory(KnowledgePage::class)->create([
                'name' => '2a test b',
                'content' => 'asd asda das',
                'project_id' => $this->project->id,
                'knowledge_directory_id' => null,
            ]),
        ];

        $this->setProjectRole();
        $this->get(
            'project/' . $this->project->id . '/pages/'
            . '?selected_company_id=' . $this->company->id
            . '&knowledge_directory_id=0'
            . '&search=test'
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData();

        $this->assertCount(2, $response->data);

        $this->assertSame($pages[1]->id, $response->data[0]->id);
        $this->assertSame($pages[2]->id, $response->data[1]->id);
    }

    protected function indexSetUp()
    {
        $roles_ids = [
            Role::findByName(RoleType::ADMIN)->id,
            Role::findByName(RoleType::DEVELOPER)->id,
        ];

        // Create directory with role permissions
        $this->directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $this->directory->roles()->attach($roles_ids);

        // One page in other project
        factory(KnowledgePage::class)->create();
        // Two pages in project
        $page = factory(KnowledgePage::class, 2)->create([
            'project_id' => $this->project->id,
            'knowledge_directory_id' => null,
        ]);

        // Page with role permissions
        $page->push(factory(KnowledgePage::class)->create([
            'project_id' => $this->project->id,
            'knowledge_directory_id' => null,
        ]));
        $page[2]->roles()->attach($roles_ids);

        // Page with user permissions
        $page->push(factory(KnowledgePage::class)->create([
            'project_id' => $this->project->id,
            'knowledge_directory_id' => null,
            'pinned' => true,
        ]));
        $page[3]->users()->attach($this->developer);

        // Page in project and in directory
        $page = $page->merge(factory(KnowledgePage::class, 2)->create([
            'project_id' => $this->project->id,
            'knowledge_directory_id' => $this->directory->id,
            'pinned' => true,
        ]));

        // 7 pages
        // 6 pages in project
        // 4 pages not in directory
        // 2 page in directory
        //
        // 2 pages without access restrictions
        // 1 page only for $this->developer
        // 3 pages only for Developer role (2 in directory)

        return $page;
    }
}
