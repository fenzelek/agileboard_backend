<?php
declare(strict_types=1);

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\TicketController\Store;

use App\Models\Db\Company;
use App\Models\Db\Project;
use App\Models\Db\Status;
use App\Models\Db\TicketType;
use App\Models\Notification\Contracts\IInteractionNotificationManager;
use App\Models\Other\Interaction\NotifiableType;

trait TicketControllerTrait
{
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

    protected function createNewStatus(int $project_id, int $priority): Status
    {
        return factory(Status::class)->create([
            'project_id' => $project_id,
            'priority' => $priority,
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

    private function prepareUrl($project_id, $company_id)
    {
        return "/projects/{$project_id}/tickets?selected_company_id={$company_id}";
    }

    private function createCompany(): Company
    {
        return factory(Company::class)->create();
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