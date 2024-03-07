<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\ProjectController\CloneTest;

use App\Models\Db\Company;
use App\Models\Db\Package;
use App\Models\Db\Project;
use App\Models\Db\Role;
use App\Models\Db\Sprint;
use App\Models\Db\Status;
use App\Models\Db\Story;
use App\Models\Db\Ticket;
use App\Models\Db\TicketComment;
use App\Models\Other\RoleType;
use App\Modules\Project\Services\File;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

trait TestTrait
{
    /** @var Company */
    protected $company;
    /** @var Project */
    protected $project;
    protected $stories;
    protected $user;

    protected function createCompany(): void
    {
        $this->company = $this->createCompanyWithRoleAndPackage(
            RoleType::ADMIN,
            Package::CEP_FREE
        );
        auth()->user()->setSelectedCompany($this->company->id);
        $this->company->roles()->sync(Role::all()->pluck('id'));
    }

    protected function createBaseProject(): void
    {
        $statuses = $this->createStatuses();
        $this->project = factory(Project::class)->create([
            'company_id' => $this->company->id,
            'status_for_calendar_id' => $statuses[0]->id,
        ]);
        $this->project->users()->attach($this->user, [
            'role_id' => Role::findByName(RoleType::ADMIN)->id,
        ]);
        $this->createStories();
        $this->project->sprints()->save(factory(Sprint::class)->create());
        $this->project->sprints()->save(factory(Sprint::class)->create());
        $this->project->statuses()->save($statuses[0]);
        $this->project->statuses()->save($statuses[1]);
        $this->project->stories()->save($this->stories[0]);
        $this->project->stories()->save($this->stories[1]);
        $this->createTickets();
    }

    protected function createStories(): void
    {
        $this->stories->push(factory(Story::class)->create(['project_id' => $this->project->id]));
        $this->stories->push(factory(Story::class)->create(['project_id' => $this->project->id]));
    }

    protected function createStatuses(): array
    {
        return [
            factory(Status::class)->create(),
            factory(Status::class)->create(),
        ];
    }

    /**
     * @param string $file_name
     */
    protected function createFile(string $file_name): void
    {
        $file = UploadedFile::fake()->image($file_name);
        $request = app()->make(Request::class);
        $request->merge(['file' => $file]);

        $file_service = app()->make(File::class);
        $file_service->save($this->project, $request);
    }

    protected function createTickets(): void
    {
        $ticket_1 = factory(Ticket::class)->create([
            'title' => 'TIC-001',
            'sprint_id' => $this->project->sprints()->first()->id,
            'status_id' => $this->project->statuses()->first()->id,
        ]);
        $ticket_2 = factory(Ticket::class)->create([
            'title' => 'TIC-002',
            'sprint_id' => $this->project->sprints()->first()->id,
            'status_id' => $this->project->statuses()->first()->id,
        ]);

        $ticket_1->subTickets()->attach($ticket_2->id);

        $ticket_1->stories()->attach($this->stories[0]);
        $ticket_2->stories()->attach($this->stories[1]);

        $this->project->tickets()->save($ticket_1);
        $this->project->tickets()->save($ticket_2);

        $this->project->created_tickets = $this->project->tickets()->count();
        $this->project->save();

        $this->createTicketComments($ticket_1);
        $this->createTicketComments($ticket_2);
    }

    /**
     * @param Ticket $ticket
     */
    protected function createTicketComments(Ticket $ticket): void
    {
        $ticket->comments()->save(factory(TicketComment::class)->create());
    }
}
