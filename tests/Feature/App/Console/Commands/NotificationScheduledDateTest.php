<?php

namespace Tests\Feature\App\Console\Commands;

use App\Models\Db\Status;
use App\Modules\Agile\Events\ExpiredScheduledDateEvent;
use App\Modules\Agile\Events\TodayScheduledDateEvent;
use Event;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Other\RoleType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Tests\Helpers\ProjectHelper;
use Tests\TestCase;

class NotificationScheduledDateTest extends TestCase
{
    use DatabaseTransactions, ProjectHelper;

    /** @test */
    public function ExpiredData_success()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);

        $status_1 = factory(Status::class)->create([
            'project_id' => $project->id,
            'priority' => 1,
        ]);
        $status_2 = factory(Status::class)->create([
            'project_id' => $project->id,
            'priority' => 2,
        ]);

        $ticket_1 = factory(Ticket::class)->create([
            'project_id' => $project->id,
            'assigned_id' => $this->user->id,
            'status_id' => $status_1->id,
            'scheduled_time_end' => Carbon::parse('2016-02-02 00:00:00'),
        ]);
        $ticket_2 = factory(Ticket::class)->create([
            'project_id' => $project->id,
            'assigned_id' => $this->user->id,
            'status_id' => $status_2->id,
            'scheduled_time_end' => Carbon::parse('2016-02-03 00:00:00'),
        ]);

        Event::fake();

        Artisan::call('notification:scheduled-date', []);

        Event::assertDispatched(ExpiredScheduledDateEvent::class, function ($e) use ($ticket_1, $project) {
            return $e->ticket->id === $ticket_1->id && $e->project->id === $project->id;
        });

        Event::assertNotDispatched(TodayScheduledDateEvent::class);
    }

    /** @test */
    public function TodayData_success()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);

        $status_1 = factory(Status::class)->create([
            'project_id' => $project->id,
            'priority' => 1,
        ]);
        $status_2 = factory(Status::class)->create([
            'project_id' => $project->id,
            'priority' => 2,
        ]);

        $ticket_1 = factory(Ticket::class)->create([
            'project_id' => $project->id,
            'assigned_id' => $this->user->id,
            'status_id' => $status_2->id,
            'scheduled_time_end' => Carbon::parse('2016-02-02 00:00:00'),
        ]);
        $ticket_2 = factory(Ticket::class)->create([
            'project_id' => $project->id,
            'assigned_id' => $this->user->id,
            'status_id' => $status_1->id,
            'scheduled_time_end' => Carbon::parse('2016-02-03 00:00:00'),
        ]);

        Event::fake();

        Artisan::call('notification:scheduled-date', []);

        Event::assertNotDispatched(ExpiredScheduledDateEvent::class);

        Event::assertDispatched(TodayScheduledDateEvent::class, function ($e) use ($ticket_2, $project) {
            return $e->ticket->id === $ticket_2->id && $e->project->id === $project->id;
        });
    }
}
