<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\StoryController;

use App\Helpers\ErrorCode;
use App\Models\Db\Company;
use App\Models\Db\Project;
use App\Models\Db\Story;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use App\Models\Db\User;
use Illuminate\Support\Collection;
use Tests\BrowserKitTestCase;
use Tests\Helpers\ProjectHelper;

class IndexTest extends BrowserKitTestCase
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

        $this->user = $this->createUser()->user;
    }

    /** @test */
    public function it_returns_valid_json_structure()
    {
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);

        $project = $this->getProject($company, $this->user->id);

        $project->stories()->save(factory(Story::class)->make());

        $this->get('/projects/' . $project->id . '/stories?selected_company_id=' .
            $company->id)->seeStatusCode(200)->isJson();

        $this->seeJsonStructure([
            'data' => [
                [
                    'id',
                    'project_id',
                    'name',
                    'priority',
                    'created_at',
                    'updated_at',
                    'deleted_at',
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
        ]);
    }

    /** @test */
    public function it_returns_valid_data()
    {
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);

        $project = $this->getProject($company, $this->user->id);

        $stories = $project->stories()->saveMany(factory(Story::class, 3)->make());

        $this->get('/projects/' . $project->id . '/stories?selected_company_id=' .
            $company->id)->seeStatusCode(200)->isJson();

        $json = collect($this->decodeResponseJson()['data']);

        $this->assertCount(3, $json);

        // make sure all elements are returned
        $stories->each(function ($story) use ($json) {
            $json_element = $json->first(function ($element) use ($story) {
                return $element['id'] == $story->id;
            });
            $this->assertNotNull($json_element);
            $this->assertEquals($story->fresh()->toArray(), $json_element);
        });
    }

    /** @test */
    public function it_allows_to_filter_stories_by_name()
    {
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);

        $project = $this->getProject($company, $this->user->id);

        /** @var Collection $stories */
        $stories = collect($project->stories()->saveMany([
            factory(Story::class)->make(['name' => 'Abctestghi']),
            factory(Story::class)->make(['name' => 'Ghijkl']),
            factory(Story::class)->make(['name' => 'Testghi']),
        ]));

        $this->get('/projects/' . $project->id . '/stories?selected_company_id=' .
            $company->id . '&name=test')->seeStatusCode(200)->isJson();

        $json = collect($this->decodeResponseJson()['data']);

        $this->assertCount(2, $json);

        $stories = $stories->forget(1);

        // make sure all elements are returned
        $stories->each(function ($story) use ($json) {
            $json_element = $json->first(function ($element) use ($story) {
                return $element['id'] == $story->id;
            });
            $this->assertNotNull($json_element);
            $this->assertEquals($story->fresh()->toArray(), $json_element);
        });
    }

    /** @test */
    public function it_returns_none_result_when_no_matching_records_found()
    {
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);

        $project = $this->getProject($company, $this->user->id);

        collect($project->stories()->saveMany([
            factory(Story::class)->make(['name' => 'Abctestghi']),
            factory(Story::class)->make(['name' => 'Ghijkl']),
            factory(Story::class)->make(['name' => 'Testghi']),
        ]));

        $this->get('/projects/' . $project->id . '/stories?selected_company_id=' .
            $company->id . '&name=wwww')->seeStatusCode(200)->isJson();

        $json = collect($this->decodeResponseJson()['data']);

        $this->assertCount(0, $json);
    }

    /** @test */
    public function it_gets_error_when_trying_to_access_other_project()
    {
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $other_company = factory(Company::class)->create();

        $project = $this->getProject($company, $this->user->id);

        $project->stories()->save(factory(Story::class)->make());

        $this->get('/projects/' . $project->id . '/stories?selected_company_id=' .
            $other_company->id);

        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function it_gets_error_when_trying_to_access_project_that_is_not_assigned_to_user()
    {
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::DEVELOPER);
        $project = factory(Project::class)->create([
            'id' => 9999,
            'company_id' => $company->id,
            'name' => 'Test remove project',
            'short_name' => 'trp',
        ]);

        $project->stories()->save(factory(Story::class)->make());

        $this->get('/projects/' . $project->id . '/stories?selected_company_id=' .
            $company->id);

        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }
}
