<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\StoryController\Store;

use App\Models\Db\Company;
use App\Models\Db\File;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\Story;
use App\Models\Db\Ticket;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use App\Models\Db\User;
use Tests\Helpers\ProjectHelper;
use Carbon\Carbon;
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

    public function setUp():void
    {
        parent::setUp();

        /* **************** setup environments ********************/
        $this->user = $this->createUser()->user;
    }

    /** @test */
    public function valid_structure_json_with_values()
    {
        /* **************** setup environments ********************/
        $now = Carbon::parse('2017-04-26 12:00:00');
        Carbon::setTestNow($now);

        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        /* **************** send request ********************/
        $fake_file = factory(File::class)->create([
            'project_id' => $project->id,
            'id' => 4123,
        ]);
        $fake_ticket = factory(Ticket::class)->create([
            'project_id' => $project->id,
            'id' => 5123,
        ]);
        $fake_page = factory(KnowledgePage::class)->create([
            'project_id' => $project->id,
            'id' => 6123,
        ]);
        $data = [
            'name' => 'Example story - phpunit',
            'color' => '#eeeeee',
            'files' => [$fake_file->id],
            'tickets' => [$fake_ticket->id],
            'knowledge_pages' => [$fake_page->id],
        ];

        $this->post('/projects/' . $project->id . '/stories?selected_company_id=' .
            $company->id, $data)->seeStatusCode(201)->isJson();

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];
        $story = Story::find($json['id'])->load('files', 'tickets');

        // database - stories
        $this->assertEquals($project->id, $story->project_id);
        $this->assertEquals($data['name'], $story->name);
        $this->assertEquals($data['color'], $story->color);
        $this->assertEquals(1, $story->priority);
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
        $this->assertEquals(1, $story->pages()->count());
        $storyables_page = $story->pages()->first();
        $this->assertEquals($fake_page->id, $storyables_page->id);

        // response
        $this->assertEquals([
            'id' => $story->id,
            'project_id' => $project->id,
            'name' => $data['name'],
            'color' => $data['color'],
            'priority' => 1,
            'created_at' => $now->toDateTimeString(),
            'updated_at' => $now->toDateTimeString(),
        ], $json);
    }

    /** @test */
    public function valid_setting_the_next_priority_number()
    {
        /* **************** setup environments  ********************/
        $now = Carbon::parse('2017-04-26 12:00:00');
        Carbon::setTestNow($now);

        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        /* **************** prepare data  ********************/
        factory(Story::class)->create([
            'project_id' => $project->id,
            'priority' => 2,
        ]);

        /* **************** send request  ********************/
        $data = [
            'name' => 'Example story - phpunit',
            'color' => '#eeeeee',
            'files' => [],
            'tickets' => [],
            'knowledge_pages' => [],
        ];

        $this->post('/projects/' . $project->id . '/stories?selected_company_id=' .
            $company->id, $data)->seeStatusCode(201)->isJson();

        /* **************** assertions  ********************/
        $json = $this->decodeResponseJson()['data'];
        $story = Story::find($json['id']);

        // database
        $this->assertEquals($json['id'], $story->id);
        $this->assertEquals($project->id, $story->project_id);
        $this->assertEquals($data['name'], $story->name);
        $this->assertEquals($data['color'], $story->color);
        $this->assertEquals(3, $story->priority);
        $this->assertEquals($now->toDateTimeString(), $story->created_at);
        $this->assertEquals($now->toDateTimeString(), $story->updated_at);

        $this->assertEquals(0, $story->files()->count());
        $this->assertEquals(0, $story->tickets()->count());
        $this->assertEquals(0, $story->pages()->count());

        // response
        $this->assertEquals([
            'id' => $story->id,
            'project_id' => $project->id,
            'name' => $data['name'],
            'color' => $data['color'],
            'priority' => 3,
            'created_at' => $now->toDateTimeString(),
            'updated_at' => $now->toDateTimeString(),
        ], $json);
    }
}
