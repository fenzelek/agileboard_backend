<?php

use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\TimeTracker\ActivityFrameScreen;
use App\Models\Db\TimeTracker\Frame;
use App\Models\Db\TimeTracker\Screen;
use Illuminate\Database\Seeder;

class ReconnectFramesToScreensSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $frames = Frame::all()->where('created_at', '>=', '2021-12-01 00:00:00');

        $frames->each(function ($frame) {
            $name_screens = $frame->screens;
            $screen_list = Screen::whereIn('name', $name_screens)->get();

            foreach ($screen_list as $screen) {
                $activity_frame_screen = new ActivityFrameScreen();
                $activity_frame_screen->screen_id = $screen->id;

                $frame->screens()->save($activity_frame_screen);
            }
        });

        $frames = Frame::all()->where('created_at', '>=', '2021-12-01 00:00:00');

        $frames->each(function ($frame) {
            $activity = Activity::where('external_activity_id', '=', $frame->id)->first();
            if (! $activity) {
                return;
            }
            $screens_morph = $frame->screens()->get();

            foreach ($screens_morph as $screen_morph) {
                $activity_frame_screen = new ActivityFrameScreen();
                $activity_frame_screen->screen_id = $screen_morph->screen_id;

                $activity->screens()->save($activity_frame_screen);
            }
        });
    }
}
