<?php

namespace Tests\Unit\App\Modules\TimeTracker\Services\TimeTracker;

use App\Models\Db\Company;
use App\Models\Db\Integration\IntegrationProvider;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Db\TimeTracker\Screen;
use App\Modules\TimeTracker\DTO\AddFrame;
use App\Modules\TimeTracker\Http\Requests\Contracts\IAddFrames;
use Carbon\Carbon;
use Illuminate\Support\Collection;

trait TimeTrackerTrait
{
    protected function prepareCompany()
    {
        $company = factory(Company::class)->create();
        $company->users()->attach($this->user->id);

        return $company;
    }

    protected function prepareProject($company)
    {
        $project = factory(Project::class)->create([
            'company_id' => $company->id,
        ]);
        $this->user->projects()->attach($project->id);

        return $project;
    }

    protected function prepareTickets($project): array
    {
        $tickets[] = $this->prepareTicket($project->id);
        $tickets[] = $this->prepareTicket($project->id);

        return $tickets;
    }

    private function prepareTicket($project_id): Ticket
    {
        return factory(Ticket::class)->create([
            'project_id' => $project_id,
        ]);
    }

    protected function prepareActivities($project, array $tickets, Carbon $date): void
    {
        $this->prepareActivity(
            $project->id,
            $tickets[0]->id,
            $date->clone()->setHour(10)->setMinute(0)->setSecond(0),
            $date->clone()->setHour(10)->setMinute(30)->setSecond(0),
        );

        $this->prepareActivity(
            $project->id,
            $tickets[1]->id,
            $date->clone()->setHour(12)->setMinute(30)->setSecond(0),
            $date->clone()->setHour(14)->setMinute(30)->setSecond(0),
        );
    }

    private function prepareActivity(int $project_id, int $ticket_id, Carbon $started_at, Carbon $finished_at): Activity
    {
        return factory(Activity::class)->create([
            'user_id' => $this->user->id,
            'project_id' => $project_id,
            'ticket_id' => $ticket_id,
            'utc_started_at' => $started_at->toDateTimeString(),
            'utc_finished_at' => $finished_at->toDateTimeString(),
        ]);
    }

    protected function screenDBCreator($screen_names): Collection
    {
        $screens = [];
        foreach ($screen_names as $screen_name) {
            $screens [] = factory(Screen::class)->create([
                'user_id' => $this->user->id,
                'name' => $screen_name,
            ]);
        }

        return collect($screens);
    }

    /**
     * @param Company $company
     */
    protected function createIntegration(Company $company)
    {
        return $company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::TIME_TRACKER)->id,
        ]);
    }

    /**
     * @param AddFrame $frame_dto
     *
     * @return IAddFrames|__anonymous@7124
     */
    private function createIncomingFrames(AddFrame $frame_dto)
    {
        $frames = new class($frame_dto) implements IAddFrames {
            protected AddFrame $add_frame;

            public function __construct(AddFrame $add_frame)
            {
                $this->add_frame = $add_frame;
            }

            public function getFrames(): iterable
            {
                return [$this->add_frame];
            }
        };

        return $frames;
    }

    private function createCompany(): Company
    {
        return factory(Company::class)->create();
    }

    public function provideFullOutsideEntryData(): iterable
    {
        return [
            [
                'started_at' => '2022-01-01 00:00:00',
                'finished_at' => '2022-01-01 01:00:00',
                'searchable_started_at' => '2022-01-01',
                '$time_zone_offset' => 1,
            ],
            [
                'started_at' => '2022-01-01 23:00:00',
                'finished_at' => '2022-01-02 00:00:00',
                'searchable_started_at' => '2022-01-01',
                '$time_zone_offset' => -1,
            ],
        ];
    }
}
