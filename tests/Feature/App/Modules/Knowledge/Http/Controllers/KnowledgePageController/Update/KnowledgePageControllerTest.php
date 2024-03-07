<?php

namespace Tests\Feature\App\Modules\Knowledge\Http\Controllers\KnowledgePageController\Update;

use App\Models\Db\Interaction;
use App\Models\Db\InteractionPing;
use App\Models\Db\Involved;
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
    public function update_it_updates_page_with_success_as_admin()
    {
        $this->mockInteractionNotificationManager();
        $page = $this->updateSetUp();
        $this->setProjectRole();
        $this->assertCount(1, KnowledgePage::all());
        $this->put(
            'project/' . $this->project->id .
            '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData();

        $this->verifyUpdatePage($response->data, $page);
    }

    /** @test */
    public function update_it_updates_page_when_stories_not_sent()
    {
        $this->mockInteractionNotificationManager();
        $page = $this->updateSetUp();
        $this->setProjectRole();
        unset($this->request_data['stories']);

        $this->assertCount(1, KnowledgePage::all());
        $this->put(
            'project/' . $this->project->id .
            '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData();

        $this->verifyUpdatePage($response->data, $page);
    }

    /** @test */
    public function update_it_changes_page_for_pinned_one_with_success_as_admin()
    {
        $this->mockInteractionNotificationManager();
        $page = $this->updateSetUp();
        $this->setProjectRole();
        $this->assertCount(1, KnowledgePage::all());
        $this->request_data['pinned'] = true;
        $this->put(
            'project/' . $this->project->id .
            '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData();

        $this->verifyUpdatePage($response->data, $page);
        $this->assertTrue($response->data->pinned);
    }

    /** @test */
    public function update_it_updates_page_with_success_as_admin_in_project()
    {
        $this->mockInteractionNotificationManager();
        $page = $this->updateSetUp();

        $project_admin = $this->developer;
        $role_id = Role::findByName(RoleType::ADMIN)->id;
        $project_admin->projects()->attach($this->project, ['role_id' => $role_id]);
        $this->be($project_admin);

        $this->assertCount(1, KnowledgePage::all());
        $this->put(
            'project/' . $this->project->id .
            '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData();

        $this->verifyUpdatePage($response->data, $page);
    }

    /** @test */
    public function update_it_updates_page_with_success_as_developer_in_project()
    {
        $this->mockInteractionNotificationManager();
        $page = $this->updateSetUp();

        $project_developer = $this->developer;
        $role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $role_id]);
        $this->be($project_developer);

        $this->assertCount(1, KnowledgePage::all());
        $this->put(
            'project/' . $this->project->id .
            '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData();

        $this->verifyUpdatePage($response->data, $page);
    }

    /** @test */
    public function update_it_add_user_permissions_to_page_with_success_as_developer_in_project()
    {
        $this->mockInteractionNotificationManager();
        $page = $this->updateSetUp();

        $project_developer = $this->developer;
        $role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $role_id]);
        $this->be($project_developer);

        // Add users id to request
        $some_users = factory(User::class, 3)->create();
        $this->project->users()->attach($some_users, [
            'role_id' => Role::findByName(RoleType::DEVELOPER)->id,
        ]);
        $this->request_data['users'] = $some_users->pluck('id')->toArray();

        $this->assertCount(1, KnowledgePage::all());
        $this->put(
            'project/' . $this->project->id .
            '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData();

        $this->verifyUpdatePage($response->data, $page);
        $this->assertEquals(
            $some_users->pluck('id')->sort()->toArray(),
            $page->fresh()->users->pluck('id')->sort()->toArray()
        );
        $this->assertEquals(
            $some_users->pluck('id')->sort()->toArray(),
            collect($response->data->users->data)->pluck('id')->sort()->toArray()
        );
    }

    /** @test */
    public function update_it_change_user_permissions_to_page_with_success_as_developer_in_project()
    {
        $this->mockInteractionNotificationManager();
        $page = $this->updateSetUp();
        // Attach developer to page
        $page->users()->attach($this->developer);
        $this->assertCount(1, $page->users);

        $project_developer = $this->developer;
        $role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $role_id]);
        $this->be($project_developer);

        // Add users id to request
        $some_users = factory(User::class, 3)->create();
        $this->project->users()->attach($some_users, [
            'role_id' => Role::findByName(RoleType::DEVELOPER)->id,
        ]);
        $this->request_data['users'] = $some_users->pluck('id')->toArray();

        $this->assertCount(1, KnowledgePage::all());
        $this->put(
            'project/' . $this->project->id .
            '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData();

        $this->verifyUpdatePage($response->data, $page);
        $this->assertCount(3, $page->fresh()->users);
        $this->assertNotContains(
            $this->developer->id,
            $page->fresh()->users->pluck('id')->toArray()
        );
        $this->assertEquals(
            $some_users->pluck('id')->sort()->toArray(),
            $page->fresh()->users->pluck('id')->sort()->toArray()
        );
    }

    /** @test */
    public function update_it_add_roles_permissions_to_page_with_success_as_developer_in_project()
    {
        $this->mockInteractionNotificationManager();
        $page = $this->updateSetUp();

        $project_developer = $this->developer;
        $role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $role_id]);
        $this->be($project_developer);

        // add roles to company and to request
        $roles_id = [
            Role::findByName(RoleType::ADMIN)->id,
            Role::findByName(RoleType::DEVELOPER)->id,
        ];
        $this->company->roles()->attach($roles_id);
        $this->request_data['roles'] = $roles_id;

        $this->assertCount(1, KnowledgePage::all());
        $this->put(
            'project/' . $this->project->id .
            '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData();

        $this->verifyUpdatePage($response->data, $page);
        $this->assertEquals($roles_id, $page->fresh()->roles->pluck('id')->sort()->toArray());
    }

    /** @test */
    public function update_it_change_roles_permissions_to_page_with_success_as_developer_in_project()
    {
        $this->mockInteractionNotificationManager();
        $page = $this->updateSetUp();
        // Attach role to page
        $page->roles()->attach(Role::findByName(RoleType::DEVELOPER));
        $this->assertCount(1, $page->roles);

        $project_developer = $this->developer;
        $role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $role_id]);
        $this->be($project_developer);

        // add roles to company and to request
        $roles_id = [
            Role::findByName(RoleType::ADMIN)->id,
            Role::findByName(RoleType::CLIENT)->id,
        ];
        $this->company->roles()->attach($roles_id);
        $this->request_data['roles'] = $roles_id;

        $this->assertCount(1, KnowledgePage::all());
        $this->put(
            'project/' . $this->project->id .
            '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id,
            $this->request_data
        )->seeStatusCode(200)->isJson();
        $response = $this->response->getData();

        $this->verifyUpdatePage($response->data, $page);
        $this->assertCount(2, $page->fresh()->roles);
        $this->assertNotContains(
            Role::findByName(RoleType::DEVELOPER)->id,
            $page->fresh()->roles->pluck('id')->toArray()
        );
        $this->assertEquals($roles_id, $page->fresh()->roles->pluck('id')->sort()->toArray());
    }

    /** @test */
    public function update_it_updates_page_with_empty_data_throw_validation_error()
    {
        $page = $this->updateSetUp();
        $this->setProjectRole();
        $this->assertCount(1, KnowledgePage::all());
        $this->put(
            'project/' . $this->project->id .
            '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id,
            []
        )->seeStatusCode(422);
        $this->verifyValidationResponse([
            'name',
            'content',
        ]);
    }

    /**
     * @feature Involved
     * @scenario Update page with involved list
     * @case Involved list contains two position
     * @expectation Involved list contains only new positions, Interaction contains new positions, Notification contains new positions
     * @test
     */
    public function update_involvedListContainsTwoPosition()
    {
        //GIVEN
        $page = $this->updateSetUp();
        $this->setProjectRole();

        $user_1 = $this->createNewUser();
        $user_2 = $this->createNewUser();

        $user_1->projects()->save($this->project);
        $user_2->projects()->save($this->project);

        $this->request_data = [
            'name' => 'Test',
            'content' => 'Lorem ipsum',
            'pinned' => false,
            'involved_ids' => [
                $user_1->id,
                $user_2->id,
            ],
        ];

        //WHEN
        $this->put(
            'project/' . $this->project->id .
            '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id,
            $this->request_data
        );

        //THEN
        $this->seeStatusCode(200);
        $this->assertCount(2, Involved::all());

        /** @var Involved $involved_first */
        $involved_first = Involved::all()->first()->toArray();
        $this->assertSame($user_1->id, $involved_first['user_id']);
        $this->assertSame(MorphMap::KNOWLEDGE_PAGES, $involved_first['source_type']);
        $this->assertSame($page['id'], $involved_first['source_id']);
        $this->assertSame($this->project->id, $involved_first['project_id']);
        $this->assertSame($this->company->id, $involved_first['company_id']);

        /** @var Involved $involved_last */
        $involved_last = Involved::all()->last()->toArray();
        $this->assertSame($user_2->id, $involved_last['user_id']);
        $this->assertSame(MorphMap::KNOWLEDGE_PAGES, $involved_last['source_type']);
        $this->assertSame($page['id'], $involved_last['source_id']);
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
     * @feature Knowledge page
     * @scenario Update page
     * @case Page with single interaction
     *
     * @Expectation Success save to db
     *
     * @dataProvider validSingleUserInteractionData
     *
     * @test
     */
    public function update_pageWithSingleInteraction($entry_interaction_data)
    {
        //GIVEN
        $this->mockInteractionNotificationManager();
        $page = $this->updateSetUp();
        $this->setProjectRole();

        $recipient = $this->createNewUser();

        $entry_interaction_data[0] = array_merge($entry_interaction_data[0], ['recipient_id' => $recipient->id]);

        $this->request_data['interactions'] = [
            'data' => $entry_interaction_data
        ];

        //WHEN
        $this->put(
            'project/' . $this->project->id .
            '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id,
            $this->request_data);

        //THEN
        $this->assertResponseStatus(200);

        /** @var KnowledgePage $knowledge_page */
        $knowledge_page = KnowledgePage::first();

        $this->assertCount(1, Interaction::all());
        /** @var Interaction $interaction */
        $interaction = Interaction::first();
        $this->assertEquals($this->user->id , $interaction->user_id);
        $this->assertEquals($this->project->id, $interaction->project_id);
        $this->assertEquals($this->company->id, $interaction->company_id);
        $this->assertEquals(InteractionEventType::KNOWLEDGE_PAGE_EDIT, $interaction->event_type);
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
     * @scenario Update page
     * @case Page with two interaction
     *
     * @Expectation Success save to db
     *
     * @dataProvider validTwoUserInteractionData
     *
     * @test
     */
    public function update_pageWithTwoInteraction(array $entry_interaction_data)
    {
        //GIVEN
        $this->mockInteractionNotificationManager();
        $page = $this->updateSetUp();
        $this->setProjectRole();

        $recipient_1 = $this->createNewUser();
        $recipient_2 = $this->createNewUser();

        $entry_interaction_data[0] = array_merge($entry_interaction_data[0], ['recipient_id' => $recipient_1->id]);
        $entry_interaction_data[1] = array_merge($entry_interaction_data[1], ['recipient_id' => $recipient_2->id]);

        $this->request_data['interactions'] = [
            'data' => $entry_interaction_data
        ];

        //WHEN
        $this->put(
            'project/' . $this->project->id .
            '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id,
            $this->request_data);

        //THEN
        $this->assertResponseStatus(200);

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
     * @scenario Update page
     * @case Page with group interaction
     *
     * @Expectation Success save to db
     *
     * @dataProvider validGroupInteractionData
     *
     * @test
     */
    public function update_pageWithGroupInteraction(array $entry_interaction_data)
    {
        //GIVEN
        $this->mockInteractionNotificationManager();
        $page = $this->updateSetUp();
        $this->setProjectRole();

        $entry_interaction_data[0] = array_merge($entry_interaction_data[0], ['recipient_id' => NotifiableGroupType::ALL]);

        $this->request_data['interactions'] = [
            'data' => $entry_interaction_data
        ];

        //WHEN
        $this->put(
            'project/' . $this->project->id .
            '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id,
            $this->request_data);

        //THEN
        $this->assertResponseStatus(200);

        /** @var KnowledgePage $knowledge_page */
        $knowledge_page = KnowledgePage::first();

        $this->assertCount(1, Interaction::all());
        /** @var Interaction $interaction */
        $interaction = Interaction::first();
        $this->assertEquals($this->user->id , $interaction->user_id);
        $this->assertEquals($this->project->id, $interaction->project_id);
        $this->assertEquals(InteractionEventType::KNOWLEDGE_PAGE_EDIT, $interaction->event_type);
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
     * @scenario Update page
     * @case Page with mixed type interaction
     *
     * @Expectation Success save to db
     *
     * @dataProvider validMixedInteractionData
     *
     * @test
     */
    public function update_pageWithMixedTypeInteraction(array $entry_interaction_data)
    {
        //GIVEN
        $this->mockInteractionNotificationManager();
        $page = $this->updateSetUp();
        $this->setProjectRole();
        $recipient_1 = $this->createNewUser();

        $entry_interaction_data[0] = array_merge($entry_interaction_data[0], ['recipient_id' => $recipient_1->id]);
        $entry_interaction_data[1] = array_merge($entry_interaction_data[1], ['recipient_id' => NotifiableGroupType::ALL]);

        $this->request_data['interactions'] = [
            'data' => $entry_interaction_data
        ];

        //WHEN
        $this->put(
            'project/' . $this->project->id .
            '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id,
            $this->request_data);

        //THEN
        $this->assertResponseStatus(200);

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
