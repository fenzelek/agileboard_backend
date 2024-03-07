<?php

namespace Tests\Feature\App\Modules\Knowledge\Http\Controllers\KnowledgePageController\Destroy;

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
use App\Models\Other\Interaction\NotifiableType;
use App\Models\Other\Interaction\SourceType;
use App\Models\Other\RoleType;
use App\Modules\Notification\Models\DatabaseNotification;
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
    public function destroy_it_deletes_page_with_success()
    {
        $this->setProjectRole();
        $page = factory(KnowledgePage::class)->create([
            'project_id' => $this->project->id,
        ]);
        $this->assertCount(1, KnowledgePage::all());

        $this->delete(
            'project/' . $this->project->id
            . '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id
        )->seeStatusCode(204)->isJson();

        $this->assertCount(0, KnowledgePage::all());
        $this->assertCount(1, KnowledgePage::withTrashed()->get());
    }

    /** @test */
    public function destroy_it_deletes_page_for_admin_in_project_with_success()
    {
        $page = factory(KnowledgePage::class)->create([
            'project_id' => $this->project->id,
        ]);
        $project_admin = $this->developer;
        $admin_role_id = Role::findByName(RoleType::ADMIN)->id;
        $project_admin->projects()->attach($this->project, ['role_id' => $admin_role_id]);

        $this->assertCount(1, KnowledgePage::all());
        $this->be($project_admin);
        $this->delete(
            'project/' . $this->project->id
            . '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id
        )->seeStatusCode(204)->isJson();

        $this->assertCount(0, KnowledgePage::all());
        $this->assertCount(1, KnowledgePage::withTrashed()->get());
    }

    /** @test */
    public function destroy_it_deletes_page_for_developer_in_project_with_success()
    {
        $page = factory(KnowledgePage::class)->create([
            'project_id' => $this->project->id,
        ]);
        $project_developer = $this->developer;
        $role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $role_id]);

        $this->assertCount(1, KnowledgePage::all());
        $this->be($project_developer);
        $this->delete(
            'project/' . $this->project->id
            . '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id
        )->seeStatusCode(204)->isJson();

        $this->assertCount(0, KnowledgePage::all());
        $this->assertCount(1, KnowledgePage::withTrashed()->get());
    }

    /** @test */
    public function destroy_it_deletes_page_for_developer_not_in_project_with_error()
    {
        $page = factory(KnowledgePage::class)->create();
        $project_developer = $this->developer;

        $this->assertCount(1, KnowledgePage::all());
        $this->be($project_developer);
        $this->delete(
            'project/' . $this->project->id
            . '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id
        );
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);

        $this->assertCount(1, KnowledgePage::all());
        $this->assertCount(0, KnowledgePage::onlyTrashed()->get());
    }

    /** @test */
    public function destroy_it_deletes_page_in_directory_for_admin_in_company_with_success()
    {
        $this->setProjectRole();
        $directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $page = factory(KnowledgePage::class)->create([
            'knowledge_directory_id' => $directory->id,
            'project_id' => $this->project->id,
        ]);
        $directory->roles()->attach(Role::findByName(RoleType::DEVELOPER)->id);

        $this->assertCount(1, KnowledgePage::all());
        $this->assertNotNull($page->directory);

        $this->delete(
            'project/' . $this->project->id
            . '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id
        )->seeStatusCode(204)->isJson();

        $this->assertCount(0, KnowledgePage::all());
        $this->assertCount(1, KnowledgePage::withTrashed()->get());
    }

    /** @test */
    public function destroy_it_deletes_page_in_directory_with_role_permission_with_success()
    {
        $directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $page = factory(KnowledgePage::class)->create([
            'project_id' => $this->project->id,
            'knowledge_directory_id' => $directory->id,
        ]);
        $directory->roles()->attach(Role::findByName(RoleType::DEVELOPER)->id);

        $project_developer = $this->developer;
        $role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $role_id]);
        $this->be($project_developer);

        $this->assertCount(1, KnowledgePage::all());
        $this->assertNotNull($page->directory);

        $this->delete(
            'project/' . $this->project->id
            . '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id
        )->seeStatusCode(204)->isJson();

        $this->assertCount(0, KnowledgePage::all());
        $this->assertCount(1, KnowledgePage::withTrashed()->get());
    }

    /** @test */
    public function destroy_it_deletes_page_in_directory_with_role_permission_with_error()
    {
        $directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $page = factory(KnowledgePage::class)->create([
            'project_id' => $this->project->id,
            'knowledge_directory_id' => $directory->id,
        ]);
        $directory->roles()->attach(Role::findByName(RoleType::DEVELOPER)->id);

        $project_client = $this->developer;
        $client_role_id = Role::findByName(RoleType::CLIENT)->id;
        $project_client->projects()->attach($this->project, ['role_id' => $client_role_id]);

        $this->be($project_client);

        $this->assertCount(1, KnowledgePage::all());
        $this->assertNotNull($page->directory);

        $this->delete(
            'project/' . $this->project->id
            . '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id
        );
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);

        $this->assertCount(1, KnowledgePage::all());
        $this->assertCount(0, KnowledgePage::onlyTrashed()->get());
    }

    /** @test */
    public function destroy_it_deletes_page_in_directory_with_user_permission_with_success()
    {
        $directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $page = factory(KnowledgePage::class)->create([
            'project_id' => $this->project->id,
            'knowledge_directory_id' => $directory->id,
        ]);

        $project_developer = $this->developer;
        $role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $role_id]);
        $directory->users()->attach($project_developer);
        $this->be($project_developer);

        $this->assertCount(1, KnowledgePage::all());
        $this->assertNotNull($page->directory);

        $this->delete(
            'project/' . $this->project->id
            . '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id
        )->seeStatusCode(204)->isJson();

        $this->assertCount(0, KnowledgePage::all());
        $this->assertCount(1, KnowledgePage::withTrashed()->get());
    }

    /** @test */
    public function destroy_it_deletes_page_in_directory_with_user_permission_with_error()
    {
        $directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $page = factory(KnowledgePage::class)->create([
            'project_id' => $this->project->id,
            'knowledge_directory_id' => $directory->id,
        ]);
        $project_client = $this->developer;
        $client_role_id = Role::findByName(RoleType::CLIENT)->id;
        $project_client->projects()->attach($this->project, ['role_id' => $client_role_id]);
        $directory->users()->attach($this->user);

        $this->be($project_client);

        $this->assertCount(1, KnowledgePage::all());
        $this->assertNotNull($page->directory);

        $this->delete(
            'project/' . $this->project->id
            . '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id
        );
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);

        $this->assertCount(1, KnowledgePage::all());
        $this->assertCount(0, KnowledgePage::onlyTrashed()->get());
    }

    /** @test */
    public function destroy_it_deletes_page_in_directory_with_no_permissions_rules_with_success()
    {
        $directory = factory(KnowledgeDirectory::class)->create([
            'project_id' => $this->project->id,
        ]);
        $page = factory(KnowledgePage::class)->create([
            'project_id' => $this->project->id,
            'knowledge_directory_id' => $directory->id,
        ]);

        $project_developer = $this->developer;
        $role_id = Role::findByName(RoleType::DEVELOPER)->id;
        $project_developer->projects()->attach($this->project, ['role_id' => $role_id]);
        $this->be($project_developer);

        $this->assertCount(1, KnowledgePage::all());
        $this->assertNotNull($page->directory);

        $this->delete(
            'project/' . $this->project->id
            . '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id
        )->seeStatusCode(204)->isJson();

        $this->assertCount(0, KnowledgePage::all());
        $this->assertCount(1, KnowledgePage::withTrashed()->get());
    }

     /**
     * @feature Involved
     * @scenario Delete page with involved list
     * @case Involved list contains two position
     * @expectation Empty involved list, Interaction contains new positions, Notification contains new positions
     * @test
     */
    public function destroy_involvedListContainsTwoPosition()
    {
        $this->setProjectRole();

        $page = $this->createKnowledgePage([
            'project_id' => $this->project->id,
        ]);

        $involved_1 = $this->createInvolved([
            'company_id' => $this->company->id,
            'project_id' => $this->project->id,
        ]);

        $involved_2 = $this->createInvolved([
            'company_id' => $this->company->id,
            'project_id' => $this->project->id,
        ]);

        $page->involved()->save($involved_1);
        $page->involved()->save($involved_2);

        //WHEN
        $this->delete(
            'project/' . $this->project->id
            . '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id
        );

        //THEN
        $this->seeStatusCode(204);
        $this->assertCount(0, Involved::all());

        $this->assertCount(1, Interaction::all());

        /** @var Interaction $interaction */
        $interaction = Interaction::first();
        $this->assertEquals($this->user->id , $interaction->user_id);
        $this->assertEquals($this->project->id, $interaction->project_id);
        $this->assertEquals(InteractionEventType::KNOWLEDGE_PAGE_INVOLVED_DELETED, $interaction->event_type);
        $this->assertEquals(ActionType::INVOLVED, $interaction->action_type);
        $this->assertEquals($page->id, $interaction->source_id);
        $this->assertEquals(SourceType::KNOWLEDGE_PAGE, $interaction->source_type);

        $this->assertCount(2, InteractionPing::all());
        /** @var InteractionPing $interaction_ping */
        $interaction_ping = InteractionPing::first();
        $this->assertEquals($involved_1->user_id, $interaction_ping->recipient_id);
        $this->assertNull($interaction_ping->ref);
        $this->assertEquals(NotifiableType::USER, $interaction_ping->notifiable);
        $this->assertNull($interaction_ping->message);
        $this->assertEquals($interaction->id, $interaction_ping->interaction_id);

        /** @var InteractionPing $interaction_ping */
        $interaction_ping = InteractionPing::all()->last();
        $this->assertEquals($involved_2->user_id, $interaction_ping->recipient_id);
        $this->assertNull($interaction_ping->ref);
        $this->assertEquals(NotifiableType::USER, $interaction_ping->notifiable);
        $this->assertNull($interaction_ping->message);
        $this->assertEquals($interaction->id, $interaction_ping->interaction_id);

        $this->assertCount(2, DatabaseNotification::all());

        /** @var DatabaseNotification $notification */
        $notification = $involved_2->user->notifications()->first();
        $this->assertInstanceOf(DatabaseNotification::class, $notification);
        $this->assertSame([
            'project_id' => $this->project->id,
            'author_id' => $this->user->id,
            'action_type' => ActionType::INVOLVED,
            'event_type' => InteractionEventType::KNOWLEDGE_PAGE_INVOLVED_DELETED,
            'source_type' => SourceType::KNOWLEDGE_PAGE,
            'source_id' => $page->id,
            'ref' => null,
            'message' => null,
        ], $notification->data);
        $this->assertSame($this->company->getCompanyId(), $notification->company_id);

        /** @var DatabaseNotification $notification */
        $notification = $involved_1->user->notifications()->first();
        $this->assertInstanceOf(DatabaseNotification::class, $notification);
        $this->assertSame([
            'project_id' => $this->project->id,
            'author_id' => $this->user->id,
            'action_type' => ActionType::INVOLVED,
            'event_type' => InteractionEventType::KNOWLEDGE_PAGE_INVOLVED_DELETED,
            'source_type' => SourceType::KNOWLEDGE_PAGE,
            'source_id' => $page->id,
            'ref' => null,
            'message' => null,
        ], $notification->data);
        $this->assertSame($this->company->getCompanyId(), $notification->company_id);
    }

    /**
     * @feature Knowledge page
     * @scenario Delete page
     * @case Page with interaction and two ping
     *
     * @Expectation Interaction success deleted from db
     *
     * @test
     */
    public function delete_pageWithInteractionAndTwoPing()
    {
        //GIVEN
        $this->setProjectRole();
        $page = factory(KnowledgePage::class)->create([
            'project_id' => $this->project->id,
        ]);

        $interaction = $this->createInteraction();

        $this->createInteractionPing($interaction->id);
        $this->createInteractionPing($interaction->id);

        $page->interactions()->save($interaction);

        //WHEN
        $this->delete(
            'project/' . $this->project->id
            . '/pages/' . $page->id
            . '?selected_company_id=' . $this->company->id);

        //THEN
        $this->assertResponseStatus(204);
        $this->assertCount(0, Interaction::all());
        $this->assertCount(0, InteractionPing::all());
    }

    /**
     * @feature Knowledge page
     * @scenario Delete page
     * @case Page with interaction with ping and another interaction with ping not attached to page
     *
     * @Expectation Interaction with ping exist in db
     *
     * @test
     */
    public function delete_pageWithInteractionWithPingAndAnotherInteractionWithPingNotAttachedToPing()
    {
        //GIVEN
        $this->setProjectRole();

        $page_1 = factory(KnowledgePage::class)->create(['project_id' => $this->project->id,
        ]);
        $interaction_1 = $this->createInteraction();
        $interaction_ping_1 = $this->createInteractionPing($interaction_1->id);
        $page_1->interactions()->save($interaction_1);

        $page_2 = factory(KnowledgePage::class)->create([
            'project_id' => $this->project->id,
        ]);
        $interaction_2 = $this->createInteraction();
        $interaction_ping_2 = $this->createInteractionPing($interaction_2->id);
        $page_2->interactions()->save($interaction_2);

        //WHEN
        $this->delete(
            'project/' . $this->project->id
            . '/pages/' . $page_1->id
            . '?selected_company_id=' . $this->company->id);

        //THEN
        $this->assertResponseStatus(204);
        $this->assertCount(1, Interaction::all());
        $this->assertEquals($interaction_2->id, Interaction::first()->id);

        $this->assertCount(1, InteractionPing::all());
        $this->assertEquals($interaction_ping_2->id, InteractionPing::first()->id);
    }
}
