<?php

namespace Tests\Unit\App\Modules\TimeTracker\Services\FrameTools;

use App\Models\Db\TimeTracker\Frame;
use App\Models\Db\User;
use App\Modules\TimeTracker\Services\FrameTools\FrameDBManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FrameDBManagerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var FrameDBManager
     */
    private $frame_manager;

    /**
     * @var User
     */
    private $other_user;

    public function setUp(): void
    {
        parent::setUp();
        $this->frame_manager = $this->app->make(FrameDBManager::class);
        $this->other_user = factory(User::class)->create();
        $this->createUser();
        auth()->loginUsingId($this->user->id);
    }

    /**
     * @feature TimeTracker
     * @scenario Search same frame
     * @case One Frame was found nothing to delete
     *
     * @test
     */
    public function search_one_frame_was_found_nothing_to_delete()
    {
        //GIVEN
        $from = Carbon::now()->setHour(8)->subMinutes(10)->getTimestamp();
        $to = Carbon::now()->setHour(8)->getTimestamp();

        $frame = $this->createDBFrameMainUser($from, $to);

        //WHEN
        $this->frame_manager->searchFrame($frame);

        //THEN
        $this->assertDatabaseCount('time_tracker_frames', 1);
    }

    /**
     * @feature TimeTracker
     * @scenario Search same frame
     * @case One Frame was deleted
     *
     * @test
     */
    public function search_one_frame_was_deleted()
    {
        //GIVEN
        $from = Carbon::now()->setHour(8)->subMinutes(10)->getTimestamp();
        $to = Carbon::now()->setHour(8)->getTimestamp();

        $this->createDBFrameMainUser($from, $to);
        $frame = $this->createDBFrameMainUser($from, $to);

        //WHEN
        $this->frame_manager->searchFrame($frame);

        //THEN
        $this->assertDatabaseCount('time_tracker_frames', 1);
    }

    /**
     * @feature TimeTracker
     * @scenario Search same frame
     * @case One Frame was deleted
     *
     * @test
     */
    public function search_one_frame_was_deleted_other_user_have_same_frames()
    {
        //GIVEN
        $from = Carbon::now()->setHour(8)->subMinutes(10)->getTimestamp();
        $to = Carbon::now()->setHour(8)->getTimestamp();

        $first_frame = $this->createDBFrameMainUser($from, $to);
        $frame = $this->createDBFrameMainUser($from, $to);
        $others_user_frame = $this->createDBFrameOtherUser($from, $to);

        //WHEN
        $this->frame_manager->searchFrame($frame);

        //THEN
        $this->assertDatabaseCount('time_tracker_frames', 2);
        $this->assertDatabaseHas('time_tracker_frames', [
            'id' => $first_frame->id,
            'user_id' => $first_frame->user_id,
            'project_id' => $first_frame->project_id,
        ]);
        $this->assertDatabaseHas('time_tracker_frames', [
            'id' => $others_user_frame->id,
            'user_id' => $others_user_frame->user_id,
            'project_id' => $others_user_frame->project_id,
        ]);
    }

    /**
     * @feature TimeTracker
     * @scenario Search same frame
     * @case One Frame was deleted
     *
     * @test
     */
    public function search_one_last_frame_was_deleted_db_has_two_same_frames()
    {
        //GIVEN
        $from = Carbon::now()->setHour(8)->subMinutes(10)->getTimestamp();
        $to = Carbon::now()->setHour(8)->getTimestamp();

        $frames = $this->createDBFrames($from, $to);

        $frame = $this->createDBFrameMainUser($from, $to);

        //WHEN
        $this->frame_manager->searchFrame($frame);

        //THEN
        $this->assertDatabaseCount('time_tracker_frames', 2);
        $this->assertDatabaseHas('time_tracker_frames', [
            'id' => $frames[0]->id,
            'user_id' => $frames[0]->user_id,
            'project_id' => $frames[0]->project_id,
        ]);
        $this->assertDatabaseHas('time_tracker_frames', [
            'id' => $frames[1]->id,
            'user_id' => $frames[1]->user_id,
            'project_id' => $frames[1]->project_id,
        ]);
    }

    private function createDBFrameMainUser($from, $to)
    {
        return factory(Frame::class)->create([
            'user_id' => $this->user->id,
            'from' => $from,
            'to' => $to,
            'activity' => 100,
        ]);
    }

    private function createDBFrames($from, $to): array
    {
        $frames = [];
        $frames [] = factory(Frame::class)->create([
            'user_id' => $this->user->id,
            'from' => $from,
            'to' => $to,
            'activity' => 100,
        ]);
        $frames [] = factory(Frame::class)->create([
            'user_id' => $this->user->id,
            'from' => $from,
            'to' => $to,
            'activity' => 100,
        ]);

        return $frames;
    }

    private function createDBFrameOtherUser($from, int $to)
    {
        return factory(Frame::class)->create([
            'user_id' => $this->other_user->id,
            'from' => $from,
            'to' => $to,
            'activity' => 100,
        ]);
    }
}
