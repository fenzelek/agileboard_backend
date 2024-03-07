<?php
declare(strict_types=1);

namespace Tests\Feature\App\Modules\Knowledge\Http\Controllers\KnowledgePageController\Store;

use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Notification\Contracts\IInteractionNotificationManager;
use App\Models\Other\Interaction\NotifiableType;

trait KnowledgePageControllerTrait
{
    private function mockInteractionNotificationManager(): void
    {
        $interaction_notification_manager = \Mockery::mock(IInteractionNotificationManager::class);;
        $expectation = $interaction_notification_manager->allows('notify');
        $this->instance(IInteractionNotificationManager::class, $interaction_notification_manager);
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

    protected function verifyStorePage($data, $directory_id = 0)
    {
        $user_id = auth()->user()->id;
        $this->assertSame($this->project->id, $data->project_id);
        $this->assertSame($user_id, $data->creator_id);
        if ($directory_id) {
            $this->assertSame($directory_id, $data->knowledge_directory_id);
        } else {
            $this->assertNull($data->knowledge_directory_id);
        }
        $this->assertSame('Test', $data->name);
        $this->assertSame('Lorem ipsum', $data->content);

        $page = KnowledgePage::find($data->id);

        $this->assertSame($this->project->id, $page->project_id);
        $this->assertSame($user_id, $page->creator_id);
        if ($directory_id) {
            $this->assertSame($directory_id, $page->knowledge_directory_id);
        } else {
            $this->assertNull($page->knowledge_directory_id);
        }
        $this->assertSame('Test', $page->name);
        $this->assertSame('Lorem ipsum', $page->content);
        $this->assertEqualsCanonicalizing($this->request_data['stories'], $page->stories->pluck('id')->all(), '');
    }
}