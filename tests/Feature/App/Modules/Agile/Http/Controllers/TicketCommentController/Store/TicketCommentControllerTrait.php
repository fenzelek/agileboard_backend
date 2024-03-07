<?php
declare(strict_types=1);

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\TicketCommentController\Store;

use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Notification\Contracts\IInteractionNotificationManager;
use App\Models\Other\Interaction\NotifiableType;
use Faker\Generator as Faker;

trait TicketCommentControllerTrait
{
    private function createTicket(int $project_id): Ticket
    {
        return factory(Ticket::class)->create(['project_id' => $project_id]);
    }

    private function createProject(int $company_id): Project
    {
        return factory(Project::class)->create(['company_id' => $company_id]);
    }

    private function mockInteractionNotificationManager(): void
    {
        $interaction_notification_manager = \Mockery::mock(IInteractionNotificationManager::class);;
        $expectation = $interaction_notification_manager->allows('notify');
        $this->instance(IInteractionNotificationManager::class, $interaction_notification_manager);
    }

    public function validSingleUserInteractionData(): iterable
    {
        yield 'valid entry data with single user interaction' => [
            [
                [
                    'ref' => 'label test',
                    'notifiable' => NotifiableType::USER,
                    'message' => str_random(500),
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