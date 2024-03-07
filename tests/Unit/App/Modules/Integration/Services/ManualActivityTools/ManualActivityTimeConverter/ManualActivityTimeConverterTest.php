<?php

namespace Tests\Unit\App\Modules\Integration\Services\ManualActivityTools\ManualActivityTimeConverter;

use App\Models\Db\Company;
use App\Models\Db\Integration\IntegrationProvider;
use App\Models\Db\Integration\TimeTracking\ManualActivityHistory;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Other\RoleType;
use App\Modules\Integration\Models\ActivityFromToDTO;
use App\Modules\Integration\Services\ManualActivityTools\ManualActivityTimeConverter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Tests\Unit\App\Modules\Integration\Services\ManualActivityTools\ManualActivityToolsTrait;

class ManualActivityTimeConverterTest extends TestCase
{
    use DatabaseTransactions;
    use ManualActivityToolsTrait;

    /**
     * @var ManualActivityTimeConverter
     */
    private ManualActivityTimeConverter $manual_converter;

    /**
     * @var Project
     */
    private Project $project;

    /**
     * @var Ticket
     */
    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $this->company = factory(Company::class)->create();
        $this->project = $this->getProject($this->company, $this->user, RoleType::DEVELOPER);
        $this->ticket = $this->getTicket($this->project);

        $this->manual_integration = $this->company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::MANUAL)->id,
        ]);

        $this->free_slots = Collection::make();
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity add
     * @case success convert DB has not activities, new activity can be saved
     *
     * @test
     */
    public function convert_add_activity_DB_free()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');
        $history = factory(ManualActivityHistory::class)->create();

        $free_slot = new ActivityFromToDTO(Carbon::make('2021-10-01 11:00:00'), Carbon::make('2021-10-01 11:10:00'));
        $this->free_slots->push($free_slot);
        $free_slots_search = $this->getFreeSlots($this->free_slots);

        $this->manual_converter = $this->app->make(ManualActivityTimeConverter::class, ['free_time_slot_search' => $free_slots_search]);

        //WHEN
        $response =
            $this->manual_converter->convert($store_activity, $this->manual_integration, $history);

        //THEN
        $this->assertCount(1, $response);
        $this->assertDatabaseCount('time_tracking_activities', 1);
        $this->assertEquals($response[0]->utc_started_at, '2021-10-01 11:00:00');
        $this->assertEquals($response[0]->utc_finished_at, '2021-10-01 11:10:00');
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity add
     * @case success convert DB activities, DB has three empty slots
     *
     * @test
     */
    public function convert_add_activity_DB_three_empty_slots()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');
        $history = factory(ManualActivityHistory::class)->create();

        $free_slot = new ActivityFromToDTO(Carbon::make('2021-10-01 11:02:00'), Carbon::make('2021-10-01 11:04:00'));
        $this->free_slots->push($free_slot);
        $free_slot = new ActivityFromToDTO(Carbon::make('2021-10-01 11:05:00'), Carbon::make('2021-10-01 11:06:00'));
        $this->free_slots->push($free_slot);
        $free_slot = new ActivityFromToDTO(Carbon::make('2021-10-01 11:07:00'), Carbon::make('2021-10-01 11:09:00'));
        $this->free_slots->push($free_slot);
        $free_slots_search = $this->getFreeSlots($this->free_slots);

        $this->manual_converter = $this->app->make(ManualActivityTimeConverter::class, ['free_time_slot_search' => $free_slots_search]);

        //WHEN
        $response = $this->manual_converter->convert($store_activity, $this->manual_integration, $history);

        //THEN
        $this->assertCount(3, $response);
        $this->assertDatabaseCount('time_tracking_activities', 3);
        $this->assertEquals($response[0]->utc_started_at, '2021-10-01 11:02:00');
        $this->assertEquals($response[0]->utc_finished_at, '2021-10-01 11:04:00');
        $this->assertEquals($response[1]->utc_started_at, '2021-10-01 11:05:00');
        $this->assertEquals($response[1]->utc_finished_at, '2021-10-01 11:06:00');
        $this->assertEquals($response[2]->utc_started_at, '2021-10-01 11:07:00');
        $this->assertEquals($response[2]->utc_finished_at, '2021-10-01 11:09:00');
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity add
     * @case failed convert DB has same activity
     *
     * @test
     */
    public function convert_add_activity_DB_is_not_empty()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');
        $history = factory(ManualActivityHistory::class)->create();

        $free_slots_search = $this->getFreeSlots($this->free_slots);

        $this->manual_converter = $this->app->make(ManualActivityTimeConverter::class, ['free_time_slot_search' => $free_slots_search]);

        //WHEN
        $response = $this->manual_converter->convert($store_activity, $this->manual_integration, $history);

        //THEN
        $this->assertCount(0, $response);
        $this->assertDatabaseCount('time_tracking_activities', 0);
    }
}
