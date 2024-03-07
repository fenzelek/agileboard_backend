<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\StoryController\Update;

use App\Models\Db\Company;
use App\Models\Db\File;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\Story;
use App\Models\Db\Ticket;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use App\Models\Db\User;
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
    public function valid_structure_json_with_values()
    {
        /* **************** setup environments  ********************/
        $now = Carbon::parse('2017-04-26 12:00:00');
        Carbon::setTestNow($now);

        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        /* **************** prepare data  ********************/
        $fake_file = factory(File::class)->create([
            'project_id' => $project->id,
        ]);
        $fake_ticket = factory(Ticket::class)->create([
            'project_id' => $project->id,
        ]);
        $fake_page = factory(KnowledgePage::class)->create([
            'project_id' => $project->id,
        ]);
        $story = factory(Story::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'name' => 'Example story - phpunit',
            'priority' => 1,
            'created_at' => $now->toDateTimeString(),
            'updated_at' => $now->toDateTimeString(),
        ]);

        /* **************** send request  ********************/
        $data = [
            'name' => 'New example story - phpunit',
            'color' => '#eeeeee',
            'priority' => 2,
            'files' => [$fake_file->id],
            'tickets' => [$fake_ticket->id],
            'knowledge_pages' => [$fake_page->id],
        ];

        $this->put('/projects/' . $project->id . '/stories/' . $story->id .
            '?selected_company_id=' . $company->id, $data)->seeStatusCode(200);

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];
        $db_story = Story::find($json['id']);

        // database - stories
        $this->assertEquals($story->id, $db_story->id);
        $this->assertEquals($story->project_id, $db_story->project_id);
        $this->assertEquals($data['name'], $db_story->name);
        $this->assertEquals($data['color'], $db_story->color);
        $this->assertEquals($data['priority'], $db_story->priority);
        $this->assertEquals($story->created_at, $db_story->created_at);
        $this->assertEquals($story->updated_at, $db_story->updated_at);

        // database - storyables - file
        $storyables_file = $story->files()->take(1)->get();
        $this->assertEquals(1, $story->files()->count());
        $this->assertEquals($fake_file->id, $storyables_file[0]->id);

        // database - storyables - ticket
        $storyables_ticket = $story->tickets()->take(1)->get();
        $this->assertEquals(1, $story->tickets()->count());
        $this->assertEquals($fake_ticket->id, $storyables_ticket[0]->id);

        // database - storyables - page
        $this->assertEquals(1, $story->pages()->count());
        $storyables_page = $story->pages()->first();
        $this->assertEquals($fake_page->id, $storyables_page->id);

        // response
        $this->assertEquals([
            'id' => $story->id,
            'project_id' => $story->project_id,
            'name' => $data['name'],
            'color' => $data['color'],
            'priority' => $data['priority'],
            'created_at' => $now->toDateTimeString(),
            'updated_at' => $now->toDateTimeString(),
            'deleted_at' => null,
        ], $json);
    }

    /** @test */
    public function data_has_synchronize_correct_return_valid()
    {
        /* **************** setup environments  ********************/
        $now = Carbon::parse('2017-04-26 12:00:00');
        Carbon::setTestNow($now);

        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        /* **************** prepare data  ********************/
        $story = factory(Story::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'name' => 'Example story - phpunit',
            'priority' => 1,
        ]);
        $fake_file = factory(File::class)->create([
            'project_id' => $project->id,
        ]);
        $fake_ticket = factory(Ticket::class)->create([
            'project_id' => $project->id,
        ]);
        $fake_ticket_2 = factory(Ticket::class)->create([
            'project_id' => $project->id,
        ]);

        $story->files()->attach($fake_file->id);
        $story->tickets()->attach($fake_ticket->id);

        /* **************** send request  ********************/
        $data = [
            'name' => 'Example story - phpunit',
            'color' => '#eeeeee',
            'priority' => 1,
            'files' => [],
            'tickets' => [$fake_ticket_2->id],
            'knowledge_pages' => [],
        ];

        $this->put('/projects/' . $project->id . '/stories/' . $story->id .
            '?selected_company_id=' . $company->id, $data)->seeStatusCode(200);

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];
        $story = Story::find($json['id']);

        // database - stories
        $this->assertEquals($project->id, $story->project_id);
        $this->assertEquals($data['name'], $story->name);
        $this->assertEquals($data['color'], $story->color);
        $this->assertEquals($data['priority'], $story->priority);
        $this->assertEquals($now->toDateTimeString(), $story->created_at);
        $this->assertEquals($now->toDateTimeString(), $story->updated_at);

        // database - storyables - file
        $this->assertEquals(0, $story->files()->count());

        // database - storyables - ticket
        $storyables_ticket = $story->tickets()->take(1)->get();
        $this->assertEquals(1, $story->tickets()->count());
        $this->assertEquals($fake_ticket_2->id, $storyables_ticket[0]->id);

        // database - storyables - page
        $this->assertEquals(0, $story->pages()->count());

        // response
        $this->assertEquals([
            'id' => $story->id,
            'project_id' => $story->project_id,
            'name' => $story->name,
            'color' => $data['color'],
            'priority' => $story->priority,
            'created_at' => $story->created_at,
            'updated_at' => $story->updated_at,
            'deleted_at' => null,
        ], $json);
    }

    /** @test */
    public function success_update_without_update_relations()
    {
        /* **************** setup environments  ********************/
        $now = Carbon::parse('2017-04-26 12:00:00');
        Carbon::setTestNow($now);

        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        /* **************** prepare data  ********************/
        $story = factory(Story::class)->create([
            'id' => 1,
            'project_id' => $project->id,
            'name' => 'Example story - phpunit',
            'priority' => 1,
        ]);
        $fake_file = factory(File::class)->create([
            'project_id' => $project->id,
        ]);
        $fake_ticket = factory(Ticket::class)->create([
            'project_id' => $project->id,
        ]);

        $story->files()->attach($fake_file->id);
        $story->tickets()->attach($fake_ticket->id);

        /* **************** send request  ********************/
        $data = [
            'name' => 'Example story - phpunit',
            'color' => '#eeeeee',
            'priority' => 1,
            'files' => null,
        ];

        $this->put('/projects/' . $project->id . '/stories/' . $story->id .
            '?selected_company_id=' . $company->id, $data)->seeStatusCode(200);

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];
        $story = Story::find($json['id']);

        // database - stories
        $this->assertEquals($project->id, $story->project_id);
        $this->assertEquals($data['name'], $story->name);
        $this->assertEquals($data['color'], $story->color);
        $this->assertEquals($data['priority'], $story->priority);
        $this->assertEquals($now->toDateTimeString(), $story->created_at);
        $this->assertEquals($now->toDateTimeString(), $story->updated_at);

        // database - storyables - file
        $storyables_file = $story->files()->take(1)->get();
        $this->assertEquals(1, $story->files()->count());
        $this->assertEquals($fake_file->id, $storyables_file[0]->id);

        // database - storyables - ticket
        $storyables_ticket = $story->tickets()->take(1)->get();
        $this->assertEquals(1, $story->tickets()->count());
        $this->assertEquals($fake_ticket->id, $storyables_ticket[0]->id);

        // database - storyables - page
        $this->assertEquals(0, $story->pages()->count());

        // response
        $this->assertEquals([
            'id' => $story->id,
            'project_id' => $story->project_id,
            'name' => $story->name,
            'color' => $data['color'],
            'priority' => $story->priority,
            'created_at' => $story->created_at,
            'updated_at' => $story->updated_at,
            'deleted_at' => null,
        ], $json);
    }
}
