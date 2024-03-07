<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\StoryController\Destroy;

use App\Models\Db\Company;
use App\Models\Db\Story;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use App\Models\Db\User;
use Tests\Helpers\ProjectHelper;
use Tests\BrowserKitTestCase;

class LogicDestroyTest extends BrowserKitTestCase
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
    public function deletes_story_with_success()
    {
        /* **************** setup environments  ********************/
        auth()->loginUsingId($this->user->id);
        $company = $this->setCompanyWithRole(RoleType::OWNER);
        $project = $this->getProject($company, $this->user->id);

        /* **************** prepare data  ********************/
        $story = factory(Story::class)->create([
            'project_id' => $project->id,
        ]);

        /* **************** send request  ********************/
        $this->delete('/projects/' . $project->id . '/stories/' . $story->id .
            '?selected_company_id=' . $company->id)->seeStatusCode(204);

        /* **************** assertions  ********************/
        $this->assertEquals(0, Story::count());
        $this->assertSame(1, Story::onlyTrashed()->count());
    }
}
