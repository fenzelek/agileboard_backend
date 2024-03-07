<?php
declare(strict_types=1);

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\TicketController\Update;

use App\Models\Db\Company;
use App\Models\Db\History;
use App\Models\Db\HistoryField;
use App\Models\Db\Project;
use App\Models\Db\Status;
use App\Models\Db\Ticket;
use App\Models\Db\TicketType;
use App\Models\Notification\Contracts\IInteractionNotificationManager;
use App\Models\Other\Interaction\NotifiableType;
use App\Modules\Agile\Services\HistoryService;

trait TicketControllerTrait
{
    private function createCompany(): Company
    {
        return factory(Company::class)->create();
    }

    /**
     * @param int $project_id
     * @param int $priority
     *
     * @return Ticket
     */
    protected function createTicket(int $project_id, int $priority): Ticket
    {
        return factory(Ticket::class)->create([
            'project_id' => $project_id,
            'priority' => $priority,
        ]);
    }

    private function mockInteractionNotificationManager(): void
    {
        $interaction_notification_manager = \Mockery::mock(IInteractionNotificationManager::class);;
        $expectation = $interaction_notification_manager->allows('notify');
        $this->instance(IInteractionNotificationManager::class, $interaction_notification_manager);
    }

    private function createNewProject(int $company_id): Project
    {
        $project = factory(Project::class)->create(['company_id' => $company_id]);
        $this->setProjectRole($project);
        $status = $this->createNewStatus($project->id, 1);
        $project->update(['status_for_calendar_id' => $status->id]);
        return $project;
    }

    private function createNewStatus(int $project_id, int $priority): Status
    {
        return factory(Status::class)->create([
            'project_id' => $project_id,
            'priority' => $priority,
        ]);
    }

    private function createNewTicket(int $project_id): Ticket {
        return factory(Ticket::class)->create([
            'project_id' => $project_id,
            'type_id' => TicketType::orderBy('id', 'desc')->first()->id,
            'priority' => 2,
            'hidden' => true,
            'scheduled_time_start' => '2018-11-11 11:11:11',
            'scheduled_time_end' => '2018-11-12 11:11:11',
        ]);
    }

    private function getDataSendSimple(): array
    {
        return [
            'name' => ' test ',
            'sprint_id' => 0,
            'type_id' => TicketType::first()->id,
            'assigned_id' => null,
            'reporter_id' => null,
            'description' => '',
            'estimate_time' => 0,
            'scheduled_time_start' => null,
            'scheduled_time_end' => null,
            'story_id' => null,
        ];
    }

    private function prepareUrl(int $project_id, int $ticket_id, int $company_id): string
    {
        return "/projects/{$project_id}/tickets/{$ticket_id}?selected_company_id={$company_id}";
    }

    /**
     * @param $field_name
     * @param $value_before
     * @param $label_before
     * @param $value_after
     * @param $label_after
     */
    private function same_history(
        $field_name,
        $value_before,
        $label_before,
        $value_after,
        $label_after
    ) {
        $field_id = HistoryField::getId(HistoryService::TICKET, $field_name);

        $this->assertSame(1, History::where([
            'field_id' => $field_id,
            'value_before' => $value_before,
            'label_before' => $label_before,
            'value_after' => $value_after,
            'label_after' => $label_after,
        ])->count());
    }

    private function createPermissions()
    {
        $this->project->permission()->create([
            'ticket_update' => [
                'roles' => [
                    ['name' => 'admin', 'value' => true],
                ],
            ],
        ]);
    }

    public function validSingleUserInteractionData(): iterable
    {
        yield 'valid entry data with single user interaction' => [
            [
                [
                    'ref' => 'label test',
                    'notifiable' => NotifiableType::USER,
                    'message' => 'message test',
                ]
            ]
        ];
    }

    public function validGroupInteractionData(): iterable
    {
        yield 'valid entry data with group interaction' => [
            [
                [
                    'ref' => 'label test',
                    'notifiable' => NotifiableType::GROUP,
                    'message' => 'message test',
                ]
            ]
        ];
    }

    public function validTwoUserInteractionData(): iterable
    {
        yield 'valid entry data with two user interaction' => [
            [
                [
                    'ref' => 'label test 1',
                    'notifiable' => NotifiableType::USER,
                    'message' => 'message test 1',
                ],
                [
                    'ref' => 'label test 2',
                    'notifiable' => NotifiableType::USER,
                    'message' => 'message test 2',
                ]
            ]
        ];
    }

    public function validMixedInteractionData(): iterable
    {
        yield 'valid entry data with single user interaction' => [
            [
                [
                    'ref' => 'label test 1',
                    'notifiable' => NotifiableType::USER,
                    'message' => 'message test 1',
                ],
                [
                    'ref' => 'label test 2',
                    'notifiable' => NotifiableType::GROUP,
                    'message' => 'message test 2',
                ]
            ]
        ];
    }
}