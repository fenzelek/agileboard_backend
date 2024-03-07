<?php

namespace App\Modules\TimeTracker\Services;

use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\TimeTracker\ActivityFrameScreen;
use App\Models\Db\TimeTracker\Frame;
use App\Models\Db\TimeTracker\Screen;
use App\Models\Db\User;
use App\Modules\TimeTracker\DTO\Contracts\IAddFrame;
use App\Modules\TimeTracker\Events\TimeTrackerFrameWasAdded;
use App\Modules\TimeTracker\Http\Requests\Contracts\IAddFrames;
use App\Modules\TimeTracker\Models\Entities\DailyActivity;
use App\Modules\TimeTracker\Models\ProcessResult;
use App\Modules\TimeTracker\Services\Contracts\ITimeAdder;
use App\Modules\TimeTracker\Services\Contracts\ITimeTracker;
use Carbon\Carbon;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Collection;

class TimeTracker implements ITimeTracker
{
    protected Dispatcher $event_dispatcher;
    private User $user;
    private ITimeAdder $time_adder;

    public function __construct(Guard $guard, TimeAdder $time_adder, Dispatcher $event_dispatcher)
    {
        $this->user = $guard->user();
        $this->time_adder = $time_adder;
        $this->event_dispatcher = $event_dispatcher;
    }

    /**
     * @param IAddFrames $request
     */
    public function processFrames(IAddFrames $request): ProcessResult
    {
        $reject_time = config('time_tracker.reject_time');

        $reject_frames = Collection::make();
        foreach ($request->getFrames() as $frame) {
            if ($frame->getTo() - $frame->getFrom() > $reject_time) {
                $reject_frames->push($frame);
                continue;
            }
            if (($frame->getTo() - $frame->getFrom()) > 0) {
                $this->addFrame($frame);
            }
        }

        return new ProcessResult($reject_frames);
    }

    /**
     * @param Carbon $date
     *
     * @return DailyActivity[]|Collection
     */
    public function getTimeSummary(Carbon $date, int $time_zone_offset = 0): Collection
    {
        $start_of_day = $date->startOfDay()->addHours($time_zone_offset)->toDateTimeString();
        $end_of_day = $date->endOfDay()->addHours($time_zone_offset)->toDateTimeString();

        return Activity::query()
            ->selectRaw(
                '(DATE_ADD(utc_started_at, INTERVAL ' . $time_zone_offset . ' HOUR)) as utc_started_at,
                (DATE_ADD(utc_finished_at, INTERVAL ' . $time_zone_offset . ' HOUR)) as utc_finished_at,
                ticket_id,
                project_id,
                projects.company_id as company_id,
                tracked'
            )
            ->distinct()
            ->join('projects', 'projects.id', '=', 'time_tracking_activities.project_id')
            ->where('user_id', $this->user->id)
            ->where(function ($q) use ($end_of_day, $start_of_day, $date) {
                $q->whereBetween('utc_started_at', [$start_of_day, $end_of_day])
                    ->whereBetween('utc_finished_at', [$start_of_day, $end_of_day]);
            })
            ->orderBy('utc_started_at', 'ASC')
            ->get();
    }

    private function addFrame(IAddFrame $request): void
    {
        $frame = new Frame();
        $frame->user()->associate($this->user);
        $frame->project_id = $request->getProjectId();
        $frame->ticket_id = $request->getTaskId();
        $frame->from = $request->getFrom();
        $frame->to = $request->getTo();

        if (null !== $request->getGpsLongitude() && null !== $request->getGpsLatitude()) {
            $frame->coordinates =
                new Point($request->getGpsLatitude(), $request->getGpsLongitude());
        }

        $frame->activity = $request->getActivity();
        $frame->screens = $request->getScreenshots();

        $frame->save();

        $this->connectScreen($frame);

        // Dispatch new incoming Frame Event
        $this->event_dispatcher->dispatch(new TimeTrackerFrameWasAdded($frame));
    }

    private function connectScreen(Frame $frame)
    {
        $name_screens = $frame->screens;

        $screen_list = Screen::whereIn('name', $name_screens)->get();

        foreach ($screen_list as $screen) {
            $activity_frame_screen = new ActivityFrameScreen();
            $activity_frame_screen->screen_id = $screen->id;

            $frame->screens()->save($activity_frame_screen);
        }
    }
}
