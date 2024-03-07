<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\FileController\Update;

use App\Models\Db\Company;
use App\Models\Db\File as ModelFile;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\Role;
use App\Models\Db\Story;
use App\Models\Db\Ticket;
use App\Models\Other\RoleType;
use App\Models\Db\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\ProjectHelper;
use Tests\BrowserKitTestCase;

class LogicUpdateTest extends BrowserKitTestCase
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

    public function setUp():void
    {
        parent::setUp();

        /* **************** setup environments  ********************/
        $this->user = $this->createUser()->user;
    }

    /** @test */
    public function fields_has_change_return_valid()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $project = $this->getProject($company, $this->user->id);

        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'name' => 'File name',
            'description' => 'File description',
        ]);

        /* **************** send request  ********************/
        $data = [
            'name' => 'New name',
            'description' => 'New description',
            'roles' => [],
            'users' => [],
            'pages' => [],
            'stories' => [],
            'tickets' => [],
        ];
        $this->put('/projects/' . $project->id . '/files/' . $file->id .
            '?selected_company_id=' . $company->id, $data)->seeStatusCode(200)
            ->seeJsonStructure($this->structureData())->isJson();

        /* **************** assertions  ********************/
        $this->assertEquals($data['name'], $file->fresh()->name);
        $this->assertEquals($data['description'], $file->fresh()->description);
    }

    /** @test */
    public function it_allows_to_not_send_relationships_arrays()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $project = $this->getProject($company, $this->user->id);

        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'name' => 'File name',
            'description' => 'File description',
        ]);

        /* **************** send request  ********************/
        $data = [
            'name' => 'New name',
            'description' => 'New description',
        ];
        $this->put('/projects/' . $project->id . '/files/' . $file->id .
            '?selected_company_id=' . $company->id, $data)->seeStatusCode(200)
            ->seeJsonStructure($this->structureData())->isJson();

        /* **************** assertions  ********************/
        $this->assertEquals($data['name'], $file->fresh()->name);
        $this->assertEquals($data['description'], $file->fresh()->description);
    }

    /** @test */
    public function data_has_change_return_valid()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);

        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $company->roles()->attach([
            Role::findByName(RoleType::OWNER)->id,
            Role::findByName(RoleType::ADMIN)->id,
        ]);
        $project = $this->getProject($company, $this->user->id);
        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'name' => 'File name',
            'description' => 'File description',
            'temp' => 1,
        ]);

        // faker data
        $user = factory(User::class)->create();
        $page = factory(KnowledgePage::class)->create(['project_id' => $project->id]);
        $story = factory(Story::class)->create(['project_id' => $project->id]);
        $ticket = factory(Ticket::class)->create(['project_id' => $project->id]);

        $file->roles()->attach([Role::findByName(RoleType::OWNER)->id]);
        $file->users()->attach([$user->id]);
        $file->pages()->attach($page->id);
        $file->stories()->attach($story->id);
        $file->tickets()->attach($ticket->id);

        // correct data
        $page = factory(KnowledgePage::class)->create(['project_id' => $project->id]);
        $story = factory(Story::class)->create(['project_id' => $project->id]);
        $ticket = factory(Ticket::class)->create(['project_id' => $project->id]);

        /* **************** send request  ********************/
        $data = [
            'name' => 'File name',
            'description' => 'File description',
            'roles' => [
                Role::findByName(RoleType::ADMIN)->id,
            ],
            'users' => [$this->user->id],
            'pages' => [$page->id],
            'stories' => [$story->id],
            'tickets' => [$ticket->id],
        ];
        $this->put('/projects/' . $project->id . '/files/' . $file->id .
            '?selected_company_id=' . $company->id, $data)->seeStatusCode(200)
            ->seeJsonStructure(
                $this->structureData(
                    $this->structureRoles(),
                    $this->structureUsers(),
                    $this->structurePages(),
                    $this->structureStories(),
                    $this->structureTickets()
                )
            )->isJson();

        $file = $file->fresh();
        /* **************** assertions  ********************/
        $this->assertEquals(false, $file->temp);
        // roles
        $this->assertEquals(1, $file->roles()->count());
        $this->assertTrue($file->roles()->pluck('role_id')
            ->contains(Role::findByName(RoleType::ADMIN)->id));
        // users
        $this->assertEquals(1, $file->users()->count());
        $this->assertTrue($file->users()->pluck('user_id')
            ->contains($this->user->id));
        // pages
        $this->assertEquals(1, $file->pages()->count());
        $this->assertTrue($file->pages()->pluck('id')
            ->contains($page->id));
        // stories
        $this->assertEquals(1, $file->stories()->count());
        $this->assertTrue($file->stories()->pluck('id')
            ->contains($story->id));
        // tickets
        $this->assertEquals(1, $file->tickets()->count());
        $this->assertTrue($file->tickets()->pluck('id')
            ->contains($ticket->id));
    }

    /** @test */
    public function data_has_synchronize_correct_return_valid()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $company->roles()->attach([
            Role::findByName(RoleType::OWNER)->id,
            Role::findByName(RoleType::ADMIN)->id,
        ]);
        auth()->loginUsingId($this->user->id);
        $project = $this->getProject($company, $this->user->id);

        // prepare data
        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'name' => 'File name',
            'description' => 'File description',
        ]);
        $user = factory(User::class)->create();
        $project->users()->attach($user->id);
        $page_1 = factory(KnowledgePage::class)->create(['project_id' => $project->id]);
        $page_2 = factory(KnowledgePage::class)->create(['project_id' => $project->id]);
        $story_1 = factory(Story::class)->create(['project_id' => $project->id]);
        $story_2 = factory(Story::class)->create(['project_id' => $project->id]);
        $ticket_1 = factory(Ticket::class)->create(['project_id' => $project->id]);
        $ticket_2 = factory(Ticket::class)->create(['project_id' => $project->id]);

        $file->roles()->attach([Role::findByName(RoleType::OWNER)->id]);
        $file->users()->attach([$this->user->id]);
        $file->pages()->attach($page_1->id);
        $file->stories()->attach($story_1->id);
        $file->tickets()->attach($ticket_1->id);

        /* **************** send request  ********************/
        $data = [
            'name' => 'File name',
            'description' => 'File description',
            'roles' => [
                Role::findByName(RoleType::OWNER)->id,
                Role::findByName(RoleType::ADMIN)->id,
            ],
            'users' => [
                $this->user->id,
                $user->id,
            ],
            'pages' => [
                $page_1->id,
                $page_2->id,
            ],
            'stories' => [
                $story_1->id,
                $story_2->id,
            ],
            'tickets' => [
                $ticket_1->id,
                $ticket_2->id,
            ],
        ];
        $this->put('/projects/' . $project->id . '/files/' . $file->id .
            '?selected_company_id=' . $company->id, $data)->seeStatusCode(200)
            ->seeJsonStructure(
                $this->structureData(
                    $this->structureRoles(),
                    $this->structureUsers(),
                    $this->structurePages(),
                    $this->structureStories(),
                    $this->structureTickets()
                )
            )->isJson();

        $file = $file->fresh();
        /* **************** assertions  ********************/
        // roles
        $this->assertEquals(2, $file->roles()->count());
        $this->assertTrue($file->roles()->pluck('role_id')
            ->contains(Role::findByName(RoleType::OWNER)->id));
        $this->assertTrue($file->roles()->pluck('role_id')
            ->contains(Role::findByName(RoleType::ADMIN)->id));

        // users
        $this->assertEquals(2, $file->users()->count());
        $this->assertTrue($file->users()->pluck('user_id')
            ->contains($this->user->id));
        $this->assertTrue($file->users()->pluck('user_id')
            ->contains($user->id));
        // pages
        $this->assertEquals(2, $file->pages()->count());
        $this->assertTrue($file->pages()->pluck('id')
            ->contains($page_1->id));
        $this->assertTrue($file->pages()->pluck('id')
            ->contains($page_2->id));
        // stories
        $this->assertEquals(2, $file->stories()->count());
        $this->assertTrue($file->stories()->pluck('id')
            ->contains($story_1->id));
        $this->assertTrue($file->stories()->pluck('id')
            ->contains($story_2->id));
        // tickets
        $this->assertEquals(2, $file->tickets()->count());
        $this->assertTrue($file->tickets()->pluck('id')
            ->contains($ticket_1->id));
        $this->assertTrue($file->tickets()->pluck('id')
            ->contains($ticket_2->id));
    }

    /** @test */
    public function data_has_remove_all_return_valid()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $company->roles()->attach([
            Role::findByName(RoleType::OWNER)->id,
            Role::findByName(RoleType::ADMIN)->id,
        ]);
        auth()->loginUsingId($this->user->id);
        $project = $this->getProject($company, $this->user->id);

        // prepare data
        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'name' => 'File name',
            'description' => 'File description',
        ]);
        $page = factory(KnowledgePage::class)->create(['project_id' => $project->id]);
        $story = factory(Story::class)->create(['project_id' => $project->id]);
        $ticket = factory(Ticket::class)->create(['project_id' => $project->id]);

        $file->roles()->attach([Role::findByName(RoleType::OWNER)->id]);
        $file->users()->attach([$this->user->id]);
        $file->pages()->attach($page->id);
        $file->stories()->attach($story->id);
        $file->tickets()->attach($ticket->id);

        /* **************** send request  ********************/
        $data = [
            'name' => 'File name',
            'description' => 'File description',
            'roles' => [],
            'users' => [],
            'pages' => [],
            'stories' => [],
            'tickets' => [],
        ];
        $this->put('/projects/' . $project->id . '/files/' . $file->id .
            '?selected_company_id=' . $company->id, $data)->seeStatusCode(200)
            ->seeJsonStructure($this->structureData())->isJson();

        $file = $file->fresh();
        /* **************** assertions  ********************/
        // roles
        $this->assertEquals(0, $file->roles()->count());
        $this->assertFalse($file->roles()->pluck('role_id')
            ->contains(Role::findByName(RoleType::OWNER)->id));
        // users
        $this->assertEquals(0, $file->users()->count());
        $this->assertFalse($file->users()->pluck('user_id')
            ->contains($this->user->id));
        // pages
        $this->assertEquals(0, $file->pages()->count());
        $this->assertFalse($file->pages()->pluck('id')
            ->contains($page->id));
        // stories
        $this->assertEquals(0, $file->stories()->count());
        $this->assertFalse($file->stories()->pluck('id')
            ->contains($story->id));
        // tickets
        $this->assertEquals(0, $file->tickets()->count());
        $this->assertFalse($file->tickets()->pluck('id')
            ->contains($ticket->id));
    }

    /** @test */
    public function return_json_structure_is_valid()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $company->roles()->attach([Role::findByName(RoleType::OWNER)->id]);
        auth()->loginUsingId($this->user->id);
        $project = $this->getProject($company, $this->user->id);

        $file = factory(ModelFile::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'name' => 'File name',
            'description' => 'File description',
        ]);

        $page = factory(KnowledgePage::class)->create(['project_id' => $project->id]);
        $story = factory(Story::class)->create(['project_id' => $project->id]);
        $ticket = factory(Ticket::class)->create(['project_id' => $project->id]);

        /* **************** send request  ********************/
        $data = [
            'name' => 'File name',
            'description' => 'File description',
            'roles' => [Role::findByName(RoleType::OWNER)->id],
            'users' => [$this->user->id],
            'pages' => [$page->id],
            'stories' => [$story->id],
            'tickets' => [$ticket->id],
        ];
        $this->put('/projects/' . $project->id . '/files/' . $file->id .
            '?selected_company_id=' . $company->id, $data)->seeStatusCode(200)
            ->seeJsonStructure(
                $this->structureData(
                    $this->structureRoles(),
                    $this->structureUsers(),
                    $this->structurePages(),
                    $this->structureStories(),
                    $this->structureTickets()
                )
            )->isJson();

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];
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
                'data' => null,
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
     * Json Structure.
     *
     * @param array $roles
     * @param array $users
     * @param array $pages
     * @param array $stories
     * @param array $tickets
     *
     * @return array
     */
    protected function structureData(
        $roles = [],
        $users = [],
        $pages = [],
        $stories = [],
        $tickets = []
    ) {
        return [
            'data' => [
                'id',
                'project_id',
                'user_id',
                'name',
                'size',
                'extension',
                'description',
                'created_at',
                'roles' => $roles,
                'users' => $users,
                'pages' => $pages,
                'stories' => $stories,
                'tickets' => $tickets,
            ],
        ];
    }

    /**
     * Json structure roles.
     *
     * @return array
     */
    protected function structureRoles()
    {
        return [
            'data' => [
                [
                    'id',
                    'name',
                ],
            ],
        ];
    }

    /**
     * Json structure roles.
     *
     * @return array
     */
    protected function structureUsers()
    {
        return [
            'data' => [
                [
                    'id',
                    'first_name',
                    'last_name',
                ],
            ],
        ];
    }

    /**
     * Json structure pages.
     *
     * @return array
     */
    protected function structurePages()
    {
        return [
            'data' => [
                [
                    'id',
                    'name',
                ],
            ],
        ];
    }

    /**
     * Json structure stories.
     *
     * @return array
     */
    protected function structureStories()
    {
        return [
            'data' => [
                [
                    'id',
                    'name',
                ],
            ],
        ];
    }

    /**
     * Json structure tickets.
     *
     * @return array
     */
    protected function structureTickets()
    {
        return [
            'data' => [
                [
                    'id',
                    'name',
                ],
            ],
        ];
    }
}
