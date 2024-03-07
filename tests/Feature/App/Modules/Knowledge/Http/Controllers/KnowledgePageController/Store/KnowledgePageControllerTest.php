<?php

namespace Tests\Feature\App\Modules\Knowledge\Http\Controllers\KnowledgePageController\Store;

use App\Helpers\ErrorCode;
use App\Models\Db\Interaction;
use App\Models\Db\InteractionPing;
use App\Models\Db\Involved;
use App\Models\Db\Knowledge\KnowledgeDirectory;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\Project;
use App\Models\Db\Role;
use App\Models\Db\Story;
use App\Models\Db\User;
use App\Models\Other\Interaction\ActionType;
use App\Models\Other\Interaction\InteractionEventType;
use App\Models\Other\Interaction\NotifiableGroupType;
use App\Models\Other\Interaction\NotifiableType;
use App\Models\Other\Interaction\SourceType;
use App\Models\Other\MorphMap;
use App\Models\Other\RoleType;
use App\Modules\Notification\Models\DatabaseNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\BrowserKitTestCase;
use Tests\Helpers\ProjectHelper;

class KnowledgePageControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ProjectHelper, KnowledgePageControllerTrait;

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
    public function store_it_creates_page_with_success()
    {
        $this->mockInteractionNotificationManager();
        $this->assertCount(0, KnowledgePage::all());
        $this->setProjectRole();
        $this->post(
            'project/' . $this->project->id .
            '/pages?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(201)->isJson();
        $response = $this->response->getData();

        $this->assertCount(1, KnowledgePage::all());
        $this->verifyStorePage($response->data);
    }

    /** @test */
    public function store_it_creates_page_for_admin_in_project_with_success()
    {
        $this->mockInteractionNotificationManager();
        $project_admin = $this->developer;
        $admin_role_id = Role::findByName(RoleType::ADMIN)->id;
        $project_admin->projects()->attach($this->project, ['role_id' => $admin_role_id]);

        // we verify if we can send also empty stories array
        $this->request_data['stories'] = [];

        $this->assertCount(0, KnowledgePage::all());
        $this->be($project_admin);
        $this->post(
            'project/' . $this->project->id .
            '/pages?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(201)->isJson();
        $response = $this->response->getData();

        $this->assertCount(1, KnowledgePage::all());
        $this->verifyStorePage($response->data);
    }

    /** @test */
    public function store_it_creates_page_for_developer_in_project_with_success()
    {
        $this->mockInteractionNotificationManager();
        $project_developer = $this->developer;
        $admin_role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $admin_role_id]);

        $this->assertCount(0, KnowledgePage::all());
        $this->be($project_developer);
        $this->post(
            'project/' . $this->project->id .
            '/pages?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(201)->isJson();
        $response = $this->response->getData();

        $this->assertCount(1, KnowledgePage::all());
        $this->verifyStorePage($response->data);
    }

    /** @test */
    public function store_it_creates_page_for_developer_in_company_with_error()
    {
        $this->assertCount(0, KnowledgePage::all());
        $this->be($this->developer);
        $this->post(
            'project/' . $this->project->id .
            '/pages?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(401);
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);

        $this->assertCount(0, KnowledgePage::all());
    }

    /** @test */
    public function store_it_gets_error_when_sending_story_from_other_project()
    {
        $project_developer = $this->developer;
        $admin_role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $admin_role_id]);

        $other_story = factory(Story::class)->create();
        $this->request_data['stories'] = [$other_story->id, $this->stories[0]->id];

        $this->assertCount(0, KnowledgePage::all());
        $this->be($project_developer);
        $this->post(
            'project/' . $this->project->id .
            '/pages?selected_company_id=' . $this->company->id,
            $this->request_data
        );

        $this->verifyValidationResponse(['stories.0'], ['stories.1']);

        $this->assertCount(0, KnowledgePage::all());
    }

    /** @test */
    public function store_sending_empty_array_will_throw_validation_error()
    {
        $this->assertCount(0, KnowledgePage::all());
        $this->setProjectRole();
        $this->post(
            'project/' . $this->project->id .
            '/pages?selected_company_id=' . $this->company->id,
            []
        )->seeStatusCode(422);
        $this->verifyValidationResponse([
            'name',
            'content',
        ]);

        $this->assertCount(0, KnowledgePage::all());
    }

    /** @test */
    public function store_it_creates_page_and_add_users_with_success()
    {
        $this->mockInteractionNotificationManager();
        $this->assertCount(0, KnowledgePage::all());

        // create users and attach them to project
        $some_users = factory(User::class, 3)->create();
        $this->assignUsersToCompany($some_users, $this->company);
        $this->project->users()->attach($some_users, [
            'role_id' => Role::findByName(RoleType::DEVELOPER)->id,
        ]);
        $this->setProjectRole();

        // Add users id to request
        $this->request_data['users'] = $some_users->pluck('id')->toArray();

        $this->post(
            'project/' . $this->project->id .
            '/pages?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(201)->isJson();
        $response_data = $this->response->getData()->data;

        // Verify added page
        $this->assertCount(1, KnowledgePage::all());
        $this->verifyStorePage($response_data);

        // Verify users added to page
        $response_users = collect($response_data->users->data)->sortBy('id');
        $this->assertCount(3, $response_users);
        $this->assertEquals($some_users->sortBy('id')->pluck('id'), $response_users->pluck('id'));
        $page = KnowledgePage::find($response_data->id);
        $this->assertEquals($some_users->sortBy('id')->pluck('id'), $page->users->pluck('id'));
    }

    /** @test */
    public function store_it_creates_page_for_developer_outside_of_project_with_error()
    {
        $this->assertCount(0, KnowledgePage::all());
        $this->setProjectRole();
        // create user and attach it to company
        $user_in_company = factory(User::class)->create();
        $this->assignUsersToCompany(User::where('id', $user_in_company->id)->get(), $this->company);

        // Add user id to request
        $this->request_data['users'] = [$user_in_company->id];

        $this->post(
            'project/' . $this->project->id .
            '/pages?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(422);
        $this->verifyValidationResponse([
            'users.0',
        ]);
    }

    /** @test */
    public function store_it_creates_page_and_add_roles_with_success()
    {
        $this->mockInteractionNotificationManager();
        $this->assertCount(0, KnowledgePage::all());
        $this->setProjectRole();
        // add company roles
        $roles_id = [
            Role::findByName(RoleType::ADMIN)->id,
            Role::findByName(RoleType::DEVELOPER)->id,
        ];
        $this->company->roles()->attach($roles_id);

        // Add roles id to request
        $this->request_data['roles'] = $roles_id;

        $this->post(
            'project/' . $this->project->id .
            '/pages?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(201)->isJson();
        $response_data = $this->response->getData()->data;

        // Verify added page
        $this->assertCount(1, KnowledgePage::all());
        $this->verifyStorePage($response_data);

        // Verify roles added to page
        $response_roles = collect($response_data->roles->data)->sortBy('id');
        $this->assertCount(2, $response_roles);
        $this->assertEquals($roles_id, $response_roles->pluck('id')->toArray());
        $page = KnowledgePage::find($response_data->id);
        $this->assertEquals($roles_id, $page->roles->pluck('id')->toArray());
    }

    /** @test */
    public function store_it_creates_page_and_add_wrong_role_with_error()
    {
        $this->assertCount(0, KnowledgePage::all());
        $this->setProjectRole();
        // add company roles
        $roles_id = [
            Role::findByName(RoleType::ADMIN)->id,
            Role::findByName(RoleType::DEVELOPER)->id,
        ];
        $this->company->roles()->attach($roles_id);
        // Role not in company roles
        $wrong_role = Role::findByName(RoleType::CLIENT)->id;

        // Add roles id to request
        $this->request_data['roles'] = [$wrong_role];

        $this->post(
            'project/' . $this->project->id .
            '/pages?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(422);
        $this->verifyValidationResponse([
            'roles.0',
        ]);
    }

    /** @test */
    public function store_it_creates_page_in_directory_for_admin_in_company_with_success()
    {
        $this->mockInteractionNotificationManager();
        $this->setProjectRole();
        $directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $directory->roles()->attach(Role::findByName(RoleType::DEVELOPER)->id);

        $this->request_data['knowledge_directory_id'] = $directory->id;
        $this->assertCount(0, KnowledgePage::all());

        $this->post(
            'project/' . $this->project->id .
            '/pages?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(201)->isJson();
        $response = $this->response->getData();

        $this->assertCount(1, KnowledgePage::all());
        $this->verifyStorePage($response->data, $directory->id);
    }

    /** @test */
    public function store_it_creates_page_in_directory_with_role_permission_with_success()
    {
        $this->mockInteractionNotificationManager();
        $directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $directory->roles()->attach(Role::findByName(RoleType::DEVELOPER)->id);

        $project_developer = $this->developer;
        $admin_role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $admin_role_id]);

        $this->be($project_developer);

        $this->request_data['knowledge_directory_id'] = $directory->id;
        $this->assertCount(0, KnowledgePage::all());

        $this->post(
            'project/' . $this->project->id .
            '/pages?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(201)->isJson();
        $response = $this->response->getData();

        $this->assertCount(1, KnowledgePage::all());
        $this->verifyStorePage($response->data, $directory->id);
    }

    /** @test */
    public function store_it_creates_page_in_directory_with_role_permission_with_error()
    {
        $directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $directory->roles()->attach(Role::findByName(RoleType::DEVELOPER)->id);

        $project_developer = $this->developer;
        $admin_role_id = Role::findByName(RoleType::CLIENT)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $admin_role_id]);

        $this->be($project_developer);

        $this->request_data['knowledge_directory_id'] = $directory->id;
        $this->assertCount(0, KnowledgePage::all());

        $this->post(
            'project/' . $this->project->id .
            '/pages?selected_company_id=' . $this->company->id,
            $this->request_data
        );
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
        $this->assertCount(0, KnowledgePage::all());
    }

    /** @test */
    public function store_it_creates_page_in_directory_with_user_permission_with_success()
    {
        $this->mockInteractionNotificationManager();
        $directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $project_developer = $this->developer;
        $admin_role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $admin_role_id]);
        $directory->users()->attach($project_developer);

        $this->be($project_developer);

        $this->request_data['knowledge_directory_id'] = $directory->id;
        $this->assertCount(0, KnowledgePage::all());

        $this->post(
            'project/' . $this->project->id .
            '/pages?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(201)->isJson();
        $response = $this->response->getData();

        $this->assertCount(1, KnowledgePage::all());
        $this->verifyStorePage($response->data, $directory->id);
    }

    /** @test */
    public function store_it_creates_page_in_directory_with_user_permission_with_error()
    {
        $directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $project_developer = $this->developer;
        $role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $role_id]);
        $directory->users()->attach($this->user);

        $this->be($project_developer);

        $this->request_data['knowledge_directory_id'] = $directory->id;
        $this->assertCount(0, KnowledgePage::all());

        $this->post(
            'project/' . $this->project->id .
            '/pages?selected_company_id=' . $this->company->id,
            $this->request_data
        );
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
        $this->assertCount(0, KnowledgePage::all());
    }

    /** @test */
    public function store_it_creates_page_in_directory_with_no_permissions_rules_with_success()
    {
        $this->mockInteractionNotificationManager();
        $directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $project_developer = $this->developer;
        $role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $role_id]);

        $this->be($project_developer);

        $this->request_data['knowledge_directory_id'] = $directory->id;
        $this->assertCount(0, KnowledgePage::all());

        $this->post(
            'project/' . $this->project->id .
            '/pages?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(201)->isJson();
        $response = $this->response->getData();

        $this->assertCount(1, KnowledgePage::all());
        $this->verifyStorePage($response->data, $directory->id);
    }

    /** @test */
    public function store_it_creates_pinned_page_with_success()
    {
        $this->mockInteractionNotificationManager();
        $this->assertCount(0, KnowledgePage::all());
        $this->setProjectRole();
        $this->request_data['pinned'] = true;

        $this->post(
            'project/' . $this->project->id .
            '/pages?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(201)->isJson();
        $response = $this->response->getData();

        $this->assertCount(1, KnowledgePage::all());
        $this->verifyStorePage($response->data);
        $this->assertTrue($response->data->pinned);
    }

    /** @test */
    public function store_it_creates_pinned_page_with_error()
    {
        $this->assertCount(0, KnowledgePage::all());
        $this->setProjectRole();
        $this->request_data['pinned'] = 'Testing';

        $this->post(
            'project/' . $this->project->id .
            '/pages?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(422);
        $this->verifyValidationResponse([
            'pinned',
        ]);
    }


    /**
     * @feature Involved
     * @scenario Store page with involved list
     * @case Involved list contains two position
     * @expectation Involved list contains new positions, Interaction contains new positions, Notification contains new positions
     * @test
     */
    public function store_involvedListContainsTwoPosition()
    {
        //GIVEN
        $this->assertCount(0, KnowledgePage::all());
        $this->setProjectRole();

        $user_1 = $this->createNewUser();
        $user_2 = $this->createNewUser();

        $user_1->projects()->save($this->project);
        $user_2->projects()->save($this->project);

        $data = [
            'knowledge_directory_id' => null,
            'name' => 'Test',
            'content' => 'Lorem ipsum',
            'pinned' => false,
            'involved_ids' => [
                $user_1->id,
                $user_2->id,
            ]
        ];

        //WHEN
        $this->post(
            'project/' . $this->project->id . '/pages?selected_company_id=' . $this->company->id, $data
        );

        //THEN
        $this->seeStatusCode(201);
        $this->assertCount(1, KnowledgePage::all());

        $knowledge_page = $this->decodeResponseJson()['data'];

        /** @var Involved $involved_first */
        $involved_first = Involved::all()->first()->toArray();
        $this->assertSame($user_1->id, $involved_first['user_id']);
        $this->assertSame(MorphMap::KNOWLEDGE_PAGES, $involved_first['source_type']);
        $this->assertSame($knowledge_page['id'], $involved_first['source_id']);
        $this->assertSame($this->project->id, $involved_first['project_id']);
        $this->assertSame($this->company->id, $involved_first['company_id']);

        /** @var Involved $involved_last */
        $involved_last = Involved::all()->last()->toArray();
        $this->assertSame($user_2->id, $involved_last['user_id']);
        $this->assertSame(MorphMap::KNOWLEDGE_PAGES, $involved_last['source_type']);
        $this->assertSame($knowledge_page['id'], $involved_last['source_id']);
        $this->assertSame($this->project->id, $involved_last['project_id']);
        $this->assertSame($this->company->id, $involved_last['company_id']);

        /** @var KnowledgePage $knowledge_page */
        $knowledgePage = KnowledgePage::first();

        $this->assertCount(1, Interaction::all());
        /** @var Interaction $interaction */
        $interaction = Interaction::first();
        $this->assertEquals($this->user->id , $interaction->user_id);
        $this->assertEquals($this->project->id, $interaction->project_id);
        $this->assertEquals(InteractionEventType::KNOWLEDGE_PAGE_INVOLVED_ASSIGNED, $interaction->event_type);
        $this->assertEquals(ActionType::INVOLVED, $interaction->action_type);
        $this->assertEquals($knowledgePage->id, $interaction->source_id);
        $this->assertEquals(SourceType::KNOWLEDGE_PAGE, $interaction->source_type);

        $this->assertCount(2, InteractionPing::all());
        /** @var InteractionPing $interaction_ping */
        $interaction_ping = InteractionPing::first();
        $this->assertEquals($user_1->id, $interaction_ping->recipient_id);
        $this->assertNull($interaction_ping->ref);
        $this->assertEquals(NotifiableType::USER, $interaction_ping->notifiable);
        $this->assertNull($interaction_ping->message);
        $this->assertEquals($interaction->id, $interaction_ping->interaction_id);

        /** @var InteractionPing $interaction_ping */
        $interaction_ping = InteractionPing::all()->last();
        $this->assertEquals($user_2->id, $interaction_ping->recipient_id);
        $this->assertNull($interaction_ping->ref);
        $this->assertEquals(NotifiableType::USER, $interaction_ping->notifiable);
        $this->assertNull($interaction_ping->message);
        $this->assertEquals($interaction->id, $interaction_ping->interaction_id);

        $this->assertCount(2, DatabaseNotification::all());

        /** @var DatabaseNotification $notification */
        $notification = $user_2->notifications()->first();
        $this->assertInstanceOf(DatabaseNotification::class, $notification);
        $this->assertSame([
            'project_id' => $this->project->id,
            'author_id' => $this->user->id,
            'action_type' => ActionType::INVOLVED,
            'event_type' => InteractionEventType::KNOWLEDGE_PAGE_INVOLVED_ASSIGNED,
            'source_type' => SourceType::KNOWLEDGE_PAGE,
            'source_id' => $knowledgePage->id,
            'ref' => null,
            'message' => null,
        ], $notification->data);
        $this->assertSame($this->company->getCompanyId(), $notification->company_id);

        /** @var DatabaseNotification $notification */
        $notification = $user_1->notifications()->first();
        $this->assertInstanceOf(DatabaseNotification::class, $notification);
        $this->assertSame([
            'project_id' => $this->project->id,
            'author_id' => $this->user->id,
            'action_type' => ActionType::INVOLVED,
            'event_type' => InteractionEventType::KNOWLEDGE_PAGE_INVOLVED_ASSIGNED,
            'source_type' => SourceType::KNOWLEDGE_PAGE,
            'source_id' => $knowledgePage->id,
            'ref' => null,
            'message' => null,
        ], $notification->data);
        $this->assertSame($this->company->getCompanyId(), $notification->company_id);
    }

 /**
     * @feature Involved
     * @scenario Store page with involved list
     * @case Involved list contains user which is not in project
     * @expectation Return validation error
     * @test
     */
    public function store_involvedListContainsUserWhichIsNotInProject()
    {
        //GIVEN
        $this->assertCount(0, KnowledgePage::all());
        $this->setProjectRole();

        $user = $this->createNewUser();

        $data = [
            'knowledge_directory_id' => null,
            'name' => 'Test',
            'content' => 'Lorem ipsum',
            'pinned' => false,
            'involved_ids' => [
                $user->id,
            ]
        ];

        //WHEN
        $this->post(
            'project/' . $this->project->id . '/pages?selected_company_id=' . $this->company->id, $data
        );

        //THEN
        $this->seeStatusCode(422);
        $this->assertStringContainsString('This users are not in project', $this->response->getContent());
    }

    /**
     * @feature Knowledge page
     * @scenario Add page
     * @case Page with single interaction
     *
     * @Expectation Success save to db
     *
     * @dataProvider validSingleUserInteractionData
     *
     * @test
     */
    public function store_pageWithSingleInteraction($entry_interaction_data)
    {
        //GIVEN
        $this->mockInteractionNotificationManager();
        $recipient = $this->createNewUser();
        $this->setProjectRole();

        $entry_interaction_data[0] = array_merge($entry_interaction_data[0], ['recipient_id' => $recipient->id]);

        $this->request_data['interactions'] = [
            'data' => $entry_interaction_data
        ];

        //WHEN
        $this->post(
            'project/' . $this->project->id .
            '/pages?selected_company_id=' . $this->company->id,
            $this->request_data);

        //THEN
        $this->assertResponseStatus(201);

        /** @var KnowledgePage $knowledge_page */
        $knowledge_page = KnowledgePage::first();

        $this->assertCount(1, Interaction::all());
        /** @var Interaction $interaction */
        $interaction = Interaction::first();
        $this->assertEquals($this->user->id , $interaction->user_id);
        $this->assertEquals($this->project->id, $interaction->project_id);
        $this->assertEquals(InteractionEventType::KNOWLEDGE_PAGE_NEW, $interaction->event_type);
        $this->assertEquals(ActionType::PING, $interaction->action_type);
        $this->assertEquals($knowledge_page->id, $interaction->source_id);
        $this->assertEquals('knowledge_pages', $interaction->source_type);

        $this->assertCount(1, InteractionPing::all());
        /** @var InteractionPing $interaction_ping */
        $interaction_ping = InteractionPing::first();
        $this->assertEquals($recipient->id , $interaction_ping->recipient_id);
        $this->assertEquals('label test', $interaction_ping->ref);
        $this->assertEquals(NotifiableType::USER, $interaction_ping->notifiable);
        $this->assertEquals('message test', $interaction_ping->message);
        $this->assertEquals($interaction->id, $interaction_ping->interaction_id);
    }

    /**
     * @feature Knowledge page
     * @scenario Add page
     * @case Page with two interaction
     *
     * @Expectation Success save to db
     *
     * @dataProvider validTwoUserInteractionData
     *
     * @test
     */
    public function store_pageWithTwoInteraction(array $entry_interaction_data)
    {
        //GIVEN
        $this->mockInteractionNotificationManager();
        $this->setProjectRole();

        $recipient_1 = $this->createNewUser();
        $recipient_2 = $this->createNewUser();

        $entry_interaction_data[0] = array_merge($entry_interaction_data[0], ['recipient_id' => $recipient_1->id]);
        $entry_interaction_data[1] = array_merge($entry_interaction_data[1], ['recipient_id' => $recipient_2->id]);

        $this->request_data['interactions'] = [
            'data' => $entry_interaction_data
        ];

        //WHEN
        $this->post(
            'project/' . $this->project->id .
            '/pages?selected_company_id=' . $this->company->id,
            $this->request_data);

        //THEN
        $this->assertResponseStatus(201);

        $this->assertCount(1, Interaction::all());
        /** @var Interaction $interaction */
        $interaction = Interaction::first();

        $this->assertCount(2, InteractionPing::all());
        /** @var InteractionPing $interaction_ping_1 */
        $interaction_ping_1 = InteractionPing::first();
        $this->assertEquals($recipient_1->id , $interaction_ping_1->recipient_id);
        $this->assertEquals('label test 1', $interaction_ping_1->ref);
        $this->assertEquals(NotifiableType::USER, $interaction_ping_1->notifiable);
        $this->assertEquals('message test 1', $interaction_ping_1->message);
        $this->assertEquals($interaction->id, $interaction_ping_1->interaction_id);

        /** @var InteractionPing $interaction_ping_2 */
        $interaction_ping_2 = InteractionPing::all()->last();
        $this->assertEquals($recipient_2->id , $interaction_ping_2->recipient_id);
        $this->assertEquals('label test 2', $interaction_ping_2->ref);
        $this->assertEquals(NotifiableType::USER, $interaction_ping_2->notifiable);
        $this->assertEquals('message test 2', $interaction_ping_2->message);
        $this->assertEquals($interaction->id, $interaction_ping_2->interaction_id);
    }

    /**
     * @feature Knowledge page
     * @scenario Add page
     * @case Page with group interaction
     *
     * @Expectation Success save to db
     *
     * @dataProvider validGroupInteractionData
     *
     * @test
     */
    public function store_pageWithGroupInteraction(array $entry_interaction_data)
    {
        //GIVEN
        $this->mockInteractionNotificationManager();
        $this->setProjectRole();

        $entry_interaction_data[0] = array_merge($entry_interaction_data[0], ['recipient_id' => NotifiableGroupType::ALL]);

        $this->request_data['interactions'] = [
            'data' => $entry_interaction_data
        ];

        //WHEN
        $this->post(
            'project/' . $this->project->id .
            '/pages?selected_company_id=' . $this->company->id,
            $this->request_data);

        //THEN
        $this->assertResponseStatus(201);

        /** @var KnowledgePage $knowledge_page */
        $knowledge_page = KnowledgePage::first();

        $this->assertCount(1, Interaction::all());
        /** @var Interaction $interaction */
        $interaction = Interaction::first();
        $this->assertEquals($this->user->id , $interaction->user_id);
        $this->assertEquals($this->project->id, $interaction->project_id);
        $this->assertEquals($this->company->id, $interaction->company_id);
        $this->assertEquals(InteractionEventType::KNOWLEDGE_PAGE_NEW, $interaction->event_type);
        $this->assertEquals(ActionType::PING, $interaction->action_type);
        $this->assertEquals($knowledge_page->id, $interaction->source_id);
        $this->assertEquals('knowledge_pages', $interaction->source_type);

        $this->assertCount(1, InteractionPing::all());
        /** @var InteractionPing $interaction_ping */
        $interaction_ping = InteractionPing::first();
        $this->assertEquals(NotifiableGroupType::ALL , $interaction_ping->recipient_id);
        $this->assertEquals('label test', $interaction_ping->ref);
        $this->assertEquals(NotifiableType::GROUP, $interaction_ping->notifiable);
        $this->assertEquals('message test', $interaction_ping->message);
        $this->assertEquals($interaction->id, $interaction_ping->interaction_id);
    }

    /**
     * @feature Knowledge page
     * @scenario Add page
     * @case Page with mixed type interaction
     *
     * @Expectation Success save to db
     *
     * @dataProvider validMixedInteractionData
     *
     * @test
     */
    public function store_pageWithMixedTypeInteraction(array $entry_interaction_data)
    {
        //GIVEN
        $this->mockInteractionNotificationManager();
        $this->setProjectRole();
        $recipient_1 = $this->createNewUser();

        $entry_interaction_data[0] = array_merge($entry_interaction_data[0], ['recipient_id' => $recipient_1->id]);
        $entry_interaction_data[1] = array_merge($entry_interaction_data[1], ['recipient_id' => NotifiableGroupType::ALL]);

        $this->request_data['interactions'] = [
            'data' => $entry_interaction_data
        ];

        //WHEN
        $this->post(
            'project/' . $this->project->id .
            '/pages?selected_company_id=' . $this->company->id,
            $this->request_data);

        //THEN
        $this->assertResponseStatus(201);

        $this->assertCount(1, Interaction::all());
        /** @var Interaction $interaction */
        $interaction = Interaction::first();

        $this->assertCount(2, InteractionPing::all());
        /** @var InteractionPing $interaction_ping_1 */
        $interaction_ping_1 = InteractionPing::first();
        $this->assertEquals($recipient_1->id , $interaction_ping_1->recipient_id);
        $this->assertEquals('label test 1', $interaction_ping_1->ref);
        $this->assertEquals(NotifiableType::USER, $interaction_ping_1->notifiable);
        $this->assertEquals('message test 1', $interaction_ping_1->message);
        $this->assertEquals($interaction->id, $interaction_ping_1->interaction_id);

        /** @var InteractionPing $interaction_ping_2 */
        $interaction_ping_2 = InteractionPing::all()->last();
        $this->assertEquals(NotifiableGroupType::ALL , $interaction_ping_2->recipient_id);
        $this->assertEquals('label test 2', $interaction_ping_2->ref);
        $this->assertEquals(NotifiableType::GROUP, $interaction_ping_2->notifiable);
        $this->assertEquals('message test 2', $interaction_ping_2->message);
        $this->assertEquals($interaction->id, $interaction_ping_2->interaction_id);
    }
}
