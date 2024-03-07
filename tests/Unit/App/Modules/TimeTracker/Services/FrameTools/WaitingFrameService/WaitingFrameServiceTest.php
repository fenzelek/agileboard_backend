<?php

namespace Tests\Unit\App\Modules\TimeTracker\Services\FrameTools\WaitingFrameService;

use App\Models\Db\User;
use App\Modules\TimeTracker\Services\FrameTools\WaitingFrameService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use function auth;

class WaitingFrameServiceTest extends TestCase
{
    use DatabaseTransactions;
    use WaitingFrameServiceTrait;

    /**
     * @var WaitingFrameService
     */
    private $frame_service;

    /**
     * @var User
     */
    private User $other_user;

    /**
     * @var int
     */
    private int $from;

    /**
     * @var int
     */
    private int $to;

    public function setUp(): void
    {
        parent::setUp();
        $this->frame_service = $this->app->make(WaitingFrameService::class);
        $this->createUser();
        $this->other_user = factory(User::class)->create();
        auth()->loginUsingId($this->user->id);

        $this->from = Carbon::now()->setHour(8)->subMinutes(10)->getTimestamp();
        $this->to = Carbon::now()->setHour(8)->getTimestamp();
    }

    /**
     * @feature TimeTracker
     * @scenario Serve waiting frames
     * @case success one frame found and converted
     *
     * @test
     */
    public function serve_unconverted_frames_one_frame_in_DB()
    {
        //GIVEN
        $company = $this->prepareCompany();
        $project = $this->prepareProject($company);
        $this->prepareIntegration($company);

        $this->createWaitingFrame($this->from, $this->to, $this->user, $project);

        //WHEN
        $this->frame_service->serveUnconvertedFrames();

        //THEN
        $this->assertDatabaseCount('time_tracker_frames', 1);
        $this->assertDatabaseCount('time_tracking_activities', 1);
        $this->assertDatabaseHas('time_tracking_activities', [
            'project_id' => $project->id,
        ]);
    }

    /**
     * @feature TimeTracker
     * @scenario Serve waiting frames
     * @case success, two frame different company and project, found and converted
     *
     * @test
     */
    public function serve_unconverted_frames_two_frame_in_DB()
    {
        //GIVEN
        $company = $this->prepareCompany();
        $project = $this->prepareProject($company);
        $this->prepareIntegration($company);

        $company_second = $this->prepareCompany();
        $project_second = $this->prepareProject($company_second);
        $this->prepareIntegration($company_second);

        $this->createWaitingFrame($this->from, $this->to, $this->user, $project);
        $this->createWaitingFrame($this->from, $this->to, $this->other_user, $project_second);

        //WHEN
        $this->frame_service->serveUnconvertedFrames();

        //THEN
        $this->assertDatabaseCount('time_tracker_frames', 2);
        $this->assertDatabaseCount('time_tracking_activities', 2);
        $this->assertDatabaseHas('time_tracking_activities', [
            'project_id' => $project->id,
        ]);
        $this->assertDatabaseHas('time_tracking_activities', [
            'project_id' => $project_second->id,
        ]);
    }

    /**
     * @feature TimeTracker
     * @scenario Serve waiting frames
     * @case success one frame found and converted, second frame has no company
     *
     * @test
     */
    public function serve_unconverted_frames_two_frame_in_DB_one_converted_second_has_no_company()
    {
        //GIVEN
        $company = $this->prepareCompany();
        $project = $this->prepareProject($company);
        $this->prepareIntegration($company);

        $this->createWaitingFrame($this->from, $this->to, $this->user, $project);
        $this->createWaitingFrameHasNoCompany($this->from, $this->to);

        //WHEN
        $this->frame_service->serveUnconvertedFrames();

        //THEN
        $this->assertDatabaseCount('time_tracker_frames', 2);
        $this->assertDatabaseCount('time_tracking_activities', 1);
        $this->assertDatabaseHas('time_tracking_activities', [
            'project_id' => $project->id,
        ]);
    }

    /**
     * @feature TimeTracker
     * @scenario Serve waiting frames
     * @case success one frame found and converted, second frame has no integration
     *
     * @test
     */
    public function serve_unconverted_frames_two_frame_in_DB_one_converted_second_has_no_integrations()
    {
        //GIVEN
        $company = $this->prepareCompany();
        $project = $this->prepareProject($company);
        $this->prepareIntegration($company);

        $second_company = $this->prepareCompany();
        $second_project = $this->prepareProject($second_company);

        $this->createWaitingFrame($this->from, $this->to, $this->user, $project);
        $this->createWaitingFrame(
            $this->from + 1000,
            $this->to + 1000,
            $this->user,
            $second_project
        );

        //WHEN
        $this->frame_service->serveUnconvertedFrames();

        //THEN
        $this->assertDatabaseCount('time_tracker_frames', 2);
        $this->assertDatabaseCount('time_tracking_activities', 1);
    }

    /**
     * @feature TimeTracker
     * @scenario Serve waiting frames
     * @case not converted but last attempt
     *
     * @test
     */
    public function serve_unconverted_frames_one_frame_in_DB_not_converted_last_check()
    {
        //GIVEN
        $company = $this->prepareCompany();
        $project = $this->prepareProject($company);
        $this->prepareIntegration($company);

        $this->createWaitingFrame($this->from, $this->to, $this->user, $project, 3);

        //WHEN
        $this->frame_service->serveUnconvertedFrames();

        //THEN
        $this->assertDatabaseCount('time_tracker_frames', 1);
        $this->assertDatabaseCount('time_tracking_activities', 0);
    }

    /**
     * @feature TimeTracker
     * @scenario Serve waiting frames
     * @case not converted but converted
     *
     * @test
     */
    public function serve_unconverted_frames_one_frame_in_DB_not_convert_but_converted()
    {
        //GIVEN
        $company = $this->prepareCompany();
        $project = $this->prepareProject($company);
        $this->prepareIntegration($company);

        $this->createWaitingFrame($this->from, $this->to, $this->user, $project, 0, true);

        //WHEN
        $this->frame_service->serveUnconvertedFrames();

        //THEN
        $this->assertDatabaseCount('time_tracker_frames', 1);
        $this->assertDatabaseCount('time_tracking_activities', 0);
    }

    /**
     * @feature TimeTracker
     * @scenario Serve waiting frames
     * @case Frame converted but duplicate
     *
     * @test
     */
    public function serve_unconverted_frames_one_frame_in_DB_not_convert_but_duplicate()
    {
        //GIVEN
        $company = $this->prepareCompany();
        $project = $this->prepareProject($company);
        $this->prepareIntegration($company);

        $this->createWaitingFrame($this->from, $this->to, $this->user, $project);
        $this->createWaitingFrame($this->from, $this->to, $this->user, $project);

        //WHEN
        $this->frame_service->serveUnconvertedFrames();

        //THEN
        $this->assertDatabaseCount('time_tracker_frames', 1);
        $this->assertDatabaseCount('time_tracking_activities', 1);
    }

    /**
     * @feature TimeTracker
     * @scenario Serve waiting frames
     * @case Frame converted but double duplicate
     *
     * @test
     */
    public function serve_unconverted_frames_one_frame_in_DB_one_convert_but_double_duplicate()
    {
        //GIVEN
        $company = $this->prepareCompany();
        $project = $this->prepareProject($company);
        $this->prepareIntegration($company);

        $this->createWaitingFrame($this->from, $this->to, $this->user, $project);
        $this->createWaitingFrame($this->from, $this->to, $this->user, $project);
        $this->createWaitingFrame($this->from, $this->to, $this->user, $project);

        //WHEN
        $this->frame_service->serveUnconvertedFrames();

        //THEN
        $this->assertDatabaseCount('time_tracker_frames', 1);
        $this->assertDatabaseCount('time_tracking_activities', 1);
    }

    /**
     * @feature TimeTracker
     * @scenario Get unconverted frames
     * @case One unconverted frame in DB
     *
     * @test
     */
    public function get_unconverted_frames_one_frame()
    {
        //GIVEN
        $company = $this->prepareCompany();
        $project = $this->prepareProject($company);
        $this->prepareIntegration($company);

        $this->createWaitingFrame($this->from, $this->to, $this->user, $project);

        //WHEN
        $frames = $this->frame_service->getUnconvertedFrames();

        //THEN
        $this->assertCount(1, $frames);
    }

    /**
     * @feature TimeTracker
     * @scenario Get unconverted frames
     * @case Three unconverted frame in DB
     *
     * @test
     */
    public function get_unconverted_frames_three_frame()
    {
        //GIVEN
        $company = $this->prepareCompany();
        $project = $this->prepareProject($company);
        $this->prepareIntegration($company);

        $this->createWaitingFrame($this->from, $this->to, $this->user, $project);
        $this->createWaitingFrame($this->from, $this->to, $this->user, $project);
        $this->createWaitingFrame($this->from, $this->to, $this->user, $project, 3);
        $this->createWaitingFrame($this->from, $this->to, $this->user, $project, 0, true);

        $company_second = $this->prepareCompany();
        $project_second = $this->prepareProject($company_second);
        $this->prepareIntegration($company_second);

        $this->createWaitingFrame($this->from, $this->to, $this->other_user, $project_second);
        $this->createWaitingFrame(
            $this->from,
            $this->to,
            $this->other_user,
            $project_second,
            0,
            true
        );

        //WHEN
        $frames = $this->frame_service->getUnconvertedFrames();

        //THEN
        $this->assertCount(3, $frames);
    }
}
