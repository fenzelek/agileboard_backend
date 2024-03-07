<?php
declare(strict_types=1);

namespace Tests\Feature\App\Modules\Knowledge\Http\Controllers\KnowledgePageController\Update;

use App\Models\Db\Knowledge\KnowledgeDirectory;
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

    protected function updateSetUp($make_dir = false): KnowledgePage
    {
        if ($make_dir) {
            $directory = factory(KnowledgeDirectory::class)->create([
                'project_id' => $this->project->id,
            ]);
        }

        $this->request_data = [
            'name' => 'New test',
            'content' => 'dolor sit amet',
            'pinned' => false,
            'stories' => [$this->stories[0]->id, $this->stories[2]->id, $this->stories[4]->id],
        ];

        $knowledge_page = factory(KnowledgePage::class)->create([
            'project_id' => $this->project->id,
            'name' => 'test',
            'creator_id' => $this->user->id,
            'knowledge_directory_id' => $make_dir ? $directory->id : null,
            'content' => 'Lorem ipsum',
        ]);

        $knowledge_page->stories()->sync([
            $this->stories[1]->id,
            $this->stories[5]->id,
            $this->stories[4]->id,
        ]);

        return $knowledge_page;
    }

    protected function verifyUpdatePage($data, $page)
    {
        $this->assertCount(1, KnowledgePage::all());
        $this->assertEquals($page->id, $data->id);
        $this->assertEquals($this->project->id, $data->project_id);
        $this->assertEquals('New test', $data->name);
        $this->assertEquals('dolor sit amet', $data->content);
        $this->assertEquals(null, $data->knowledge_directory_id);

        if (array_key_exists('stories', $this->request_data)) {
            $this->assertEqualsCanonicalizing(
                $this->request_data['stories'],
                $page->fresh()->stories->pluck('id')->all(),
                ''
            );
        } else {
            $this->assertSame(0, $page->fresh()->stories()->count());
        }
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
}