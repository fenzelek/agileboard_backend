<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\FileController\Show;

use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\Role;
use App\Models\Db\Story;
use App\Models\Db\Ticket;
use App\Models\Other\RoleType;
use App\Models\Db\File as ModelFile;
use App\Models\Db\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\ProjectHelper;
use Tests\BrowserKitTestCase;

class LogicShowTest extends BrowserKitTestCase
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
    public function return_structure_is_valid()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        $page = factory(KnowledgePage::class)->create(['project_id' => $project->id]);
        $story = factory(Story::class)->create(['project_id' => $project->id]);
        $ticket = factory(Ticket::class)->create(['project_id' => $project->id]);

        $file = factory(ModelFile::class)->create([
            'project_id' => $project->id,
            'name' => 'Example file',
            'size' => 1000,
            'extension' => 'jpg',
            'description' => 'Example description',
            'user_id' => $this->user->id,
        ]);
        $file->roles()->attach(Role::findByName(RoleType::OWNER)->id);
        $file->users()->attach($this->user->id);
        $file->pages()->attach($page->id);
        $file->stories()->attach($story->id);
        $file->tickets()->attach($ticket->id);

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id . '/files/' . $file->id .
            '?selected_company_id=' . $company->id)->seeStatusCode(200)->seeJsonStructure(
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
                        'project_id' => $story->project_id,
                        'color' => $story->color,
                        'priority' => $story->priority,
                        'created_at' => $story->created_at,
                        'updated_at' => $story->updated_at,
                        'deleted_at' => $story->deleted_at,
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
            'owner' => [
                'data' => $this->getExpectedUserSimpleResponse($this->user),
            ],
        ], $json);
    }

    /** @test */
    public function return_empty_structure_is_valid()
    {
        /* **************** setup environments  ********************/
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        $page = factory(KnowledgePage::class)->create(['project_id' => $project->id]);
        $story = factory(Story::class)->create(['project_id' => $project->id]);
        $ticket = factory(Ticket::class)->create(['project_id' => $project->id]);

        $file = factory(ModelFile::class)->create([
            'project_id' => $project->id,
            'name' => 'Example file',
            'size' => 1000,
            'extension' => 'jpg',
            'description' => 'Example description',
            'user_id' => $this->user->id,
        ]);

        /* **************** send request  ********************/
        $this->get('/projects/' . $project->id . '/files/' . $file->id .
            '?selected_company_id=' . $company->id)->seeStatusCode(200)->seeJsonStructure(
                $this->structureData()
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
            'owner' => [
                'data' => $this->getExpectedUserSimpleResponse($this->user),
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
