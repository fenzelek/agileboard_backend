<?php

namespace Tests\Unit\App\Modules\TimeTracker\Services\FrameTools;

use App\Models\Db\TimeTracker\Frame;
use App\Models\Db\User;
use App\Modules\TimeTracker\Services\FrameTools\FrameSearcher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FrameSearcherTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var FrameSearcher
     */
    private $frame_search;

    /**
     * @var User
     */
    private $other_user;

    public function setUp(): void
    {
        parent::setUp();
        $this->farme_search = $this->app->make(FrameSearcher::class);
        $this->other_user = factory(User::class)->create();
        $this->createUser();
        auth()->loginUsingId($this->user->id);
    }

    /**
     * @feature TimeTracker
     * @scenario Search same frame
     * @case Zero frames was found
     *
     * @test
     */
    public function search_zero_frame_found()
    {
        //GIVEN
        $from = Carbon::now()->setHour(8)->subMinutes(10)->getTimestamp();
        $to = Carbon::now()->setHour(8)->getTimestamp();

        $this->createNorSameFrame($from, $to);
        $frame_dto = new Frame([
            'user_id' => $this->user->id,
            'from' => $from,
            'to' => $to,
            'activity' => 87,
        ]);

        //WHEN
        $frames = $this->farme_search->searchDuplicatesOf($frame_dto);

        //THEN
        $this->assertCount(0, $frames);
    }

    /**
     * @feature TimeTracker
     * @scenario Search same frame
     * @case Zero frames was found
     *
     * @test
     */
    public function search_zero_frame_found_another_user_has_same_frame()
    {
        //GIVEN
        $from = Carbon::now()->setHour(8)->subMinutes(10)->getTimestamp();
        $to = Carbon::now()->setHour(8)->getTimestamp();

        $frame_dto = new Frame([
            'user_id' => $this->user->id,
            'from' => $from,
            'to' => $to,
            'activity' => 87,
        ]);
        $this->createDBFrameOtherUser($from, $to);

        //WHEN
        $frames = $this->farme_search->searchDuplicatesOf($frame_dto);

        //THEN
        $this->assertCount(0, $frames);
    }

    /**
     * @feature TimeTracker
     * @scenario Search same frame
     * @case One frame was found
     *
     * @test
     */
    public function search_many_frame_found()
    {
        //GIVEN
        $from = Carbon::now()->setHour(8)->subMinutes(10)->getTimestamp();
        $to = Carbon::now()->setHour(8)->getTimestamp();

        $frame = $this->createDBFrameMainUser($from, $to);
        $this->createFrames($from, $to);

        //WHEN
        $frames = $this->farme_search->searchDuplicatesOf($frame);

        //THEN
        $this->assertCount(2, $frames);
    }

    private function createFrames($from, $to)
    {
        factory(Frame::class)->create([
            'user_id' => $this->user->id,
            'from' => $from,
            'to' => $to,
            'activity' => 100,
        ]);

        factory(Frame::class)->create([
            'user_id' => $this->user->id,
            'from' => $from,
            'to' => $to,
            'activity' => 100,
        ]);
    }

    private function createNorSameFrame($from, $to)
    {
        factory(Frame::class)->create([
            'user_id' => $this->user->id,
            'from' => $from + 100,
            'to' => $to + 100,
            'activity' => 100,
        ]);

        factory(Frame::class)->create([
            'user_id' => $this->other_user->id,
            'from' => $from,
            'to' => $to,
            'activity' => 100,
        ]);
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

    private function createDBFrameMainUser($from, int $to)
    {
        return factory(Frame::class)->create([
            'user_id' => $this->user->id,
            'from' => $from,
            'to' => $to,
            'activity' => 100,
        ]);
    }
}
