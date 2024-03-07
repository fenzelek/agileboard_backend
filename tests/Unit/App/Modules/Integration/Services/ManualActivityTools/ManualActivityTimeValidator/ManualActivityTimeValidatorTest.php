<?php

namespace Tests\Unit\App\Modules\Integration\Services\ManualActivityTools\ManualActivityTimeValidator;

use App\Models\Db\Company;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Other\RoleType;
use App\Modules\Integration\Services\ManualActivityTools\ManualActivityTimeValidator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\App\Modules\Integration\Services\ManualActivityTools\ManualActivityToolsTrait;

class ManualActivityTimeValidatorTest extends TestCase
{
    use DatabaseTransactions;
    use ManualActivityToolsTrait;

    /**
     * @var ManualActivityTimeValidator|mixed
     */
    private ManualActivityTimeValidator $manual_activity_time_validator;

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

        $this->manual_activity_time_validator =
            $this->app->make(ManualActivityTimeValidator::class);
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity tools
     * @case success, time is valid
     *
     * @test
     */
    public function check_success_time_is_valid()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');

        //WHEN
        $response = $this->manual_activity_time_validator->check($store_activity);

        //THEN
        $this->assertTrue($response);
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity tools
     * @case failed, time is not valid, feature time given
     *
     * @test
     */
    public function check_failed_time_is_not_valid_feature_time_given()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('3021-10-01 11:00:00', '3021-10-01 11:10:00');

        //WHEN
        $response = $this->manual_activity_time_validator->check($store_activity);

        //THEN
        $this->assertFalse($response);
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity tools
     * @case failed, time is not valid, second time is feature
     *
     * @test
     */
    public function check_failed_time_is_not_valid_to_time_is_feature()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '3021-10-01 11:10:00');

        //WHEN
        $response = $this->manual_activity_time_validator->check($store_activity);

        //THEN
        $this->assertFalse($response);
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity tools
     * @case failed, time is not valid, first time is feature
     *
     * @test
     */
    public function check_failed_time_is_not_valid_from_time_is_feature()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('3021-10-01 11:00:00', '2021-10-01 11:10:00');

        //WHEN
        $response = $this->manual_activity_time_validator->check($store_activity);

        //THEN
        $this->assertFalse($response);
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity tools
     * @case failed, time is not valid, first time is bigger than second
     *
     * @test
     */
    public function check_failed_time_is_not_valid_from_time_is_bigger_than_to()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:10:00', '2021-10-01 11:00:00');

        //WHEN
        $response = $this->manual_activity_time_validator->check($store_activity);

        //THEN
        $this->assertFalse($response);
    }
}
