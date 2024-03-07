<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Integration\Http\Controllers\TimeTrackingActivityController;

use App\Models\Db\Company;
use App\Models\Db\Integration\IntegrationProvider;
use App\Models\Db\Integration\TimeTracking\User as TimeTrackingUser;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use DatabaseTransactions;
    use TimeTrackerTrait;

    /**
     * @test
     */
    public function export_WhenSumTrackTimesIsFalse_ReturnActivityAsCsv()
    {
        //Given

        //When
        $query = [
            'selected_company_id' => $this->company->id,
            'sum_ticket_times' => true,
        ];
        $response = $this
            ->get(route('time-tracking-activity.export') . '?' . Arr::query($query));

        //Then
        $response->assertOk();
        $response->assertDownload();
    }

    /**
     * @test
     */
    public function export_WhenSumTrackTimesIsTrue_ReturnActivityAsCsv()
    {
        //Given

        //When
        $query = [
            'selected_company_id' => $this->company->id,
            'sum_ticket_times' => false,
        ];
        $response = $this
            ->get(route('time-tracking-activity.export') . '?' . Arr::query($query));

        //Then
        $response->assertOk();
        $response->assertDownload();
    }

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        $this->company = factory(Company::class)->create();
        $this->hubstaff_integration = $this->company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::HUBSTAFF)->id,
        ]);
        $this->upwork_integration = $this->company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::UPWORK)->id,
        ]);
        $this->time_tracker_integration = $this->company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::TIME_TRACKER)->id,
        ]);

        $this->user = factory(User::class)->create();
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::DEVELOPER);
        auth()->loginUsingId($this->user->id);

        $this->tracking_users = factory(TimeTrackingUser::class, 5)->create(['user_id' => null]);

        $this->tracking_users[2]->external_user_id = 'XYZ_EXTERNAL';
        $this->tracking_users[2]->save();

        $this->project = factory(Project::class)->create(['company_id' => $this->company->id]);
        $this->ticket = factory(Ticket::class)->create();

        $this->now = Carbon::now();
        Carbon::setTestNow($this->now);

        $this->tracking_activities = $this->createTimeTrackingActivities();
    }
}
