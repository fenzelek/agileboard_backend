<?php

namespace Tests\Unit\App\Modules\Integration\Services\TimeTracking\TimeTrackerFrameIntegrator;

use App\Models\Db\Company;
use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\IntegrationProvider;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Db\TimeTracker\ActivityFrameScreen;
use App\Models\Db\TimeTracker\Frame;
use App\Models\Db\TimeTracker\Screen;
use Illuminate\Support\Collection;

trait TimeTrackerFrameIntegratorTrait
{
    protected function screenDBCreator($screen_names, Frame $frame): Collection
    {
        $screens = [];
        foreach ($screen_names as $key => $screen_name) {
            $screens [$key] = factory(Screen::class)->create([
                'user_id' => $this->user->id,
                'name' => $screen_name,
            ]);
            $activity_frame_screen = new ActivityFrameScreen();
            $activity_frame_screen->screen_id = $screens[$key]->id;
            $frame->screens()->save($activity_frame_screen);
        }

        return collect($screens);
    }

    private function createFrame(Project $project, Ticket $ticket, array $attributes): Frame
    {
        /**
         * @var Frame $frame
         */
        $frame = factory(Frame::class)->make($attributes);
        $frame->user()->associate($this->user);
        $frame->project()->associate($project);
        $frame->ticket()->associate($ticket);
        $frame->save();

        return $frame->fresh();
    }

    /**
     * @param int $tracked_time
     */
    public function createDBFrame(int $tracked_time, $project, $ticket): Frame
    {
        return factory(Frame::class)->create([
            'user_id' => $this->user->id,
            'project_id' => $project->id,
            'ticket_id' => $ticket->id,
            'from' => 1600000000,
            'to' => 1600000000 + $tracked_time,
            'activity' => 100,
        ]);
    }


    private function createTicket(): Ticket
    {
        return factory(Ticket::class)->create();
    }

    private function createProject(Company $company): Project
    {
        $project = factory(Project::class)->make();
        $project->company()->associate($company);
        $project->save();

        return $project;
    }

    private function createIntegrationFor(string $time_tracker, Company $company): Integration
    {
        $provider = IntegrationProvider::findBySlug($time_tracker);
        /**
         * @var Integration $integration
         */
        $integration = factory(Integration::class)->make();
        $integration->provider()->associate($provider);
        $integration->company()->associate($company);
        $integration->save();

        return $integration;
    }

    private function createCompany(): Company
    {
        return factory(Company::class)->create();
    }

    /**
     * @param int $tracked_time
     */
    public function createDBActivity(int $tracked_time, $project, $ticket): Activity
    {
        return factory(Activity::class)->create([
            'user_id' => $this->user->id,
            'project_id' => $project->id,
            'ticket_id' => $ticket->id,
            'utc_started_at' => 1600000000,
            'utc_finished_at' => 1600000000 + $tracked_time,
            'tracked' => $tracked_time,
            'activity' => 100,
        ]);
    }

    private function createActivityFrameScreenRelations(Activity $activity, Frame $frame, Collection $screens)
    {
        $screens->each(function ($screen) use ($activity, $frame) {
            $activity_screens = new ActivityFrameScreen();
            $activity_screens->screen_id = $screen->id;
            $frame->screens()->save($activity_screens);

            $frame_screens = new ActivityFrameScreen();
            $frame_screens->screen_id = $screen->id;
            $activity->screens()->save($frame_screens);
        });
    }
}
