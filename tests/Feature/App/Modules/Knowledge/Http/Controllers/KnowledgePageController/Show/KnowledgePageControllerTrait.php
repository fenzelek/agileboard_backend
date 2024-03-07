<?php
declare(strict_types=1);

namespace Tests\Feature\App\Modules\Knowledge\Http\Controllers\KnowledgePageController\Show;

use App\Models\Db\Interaction;
use App\Models\Db\Involved;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\User;

trait KnowledgePageControllerTrait
{
    private function createInvolved($params = []): Involved
    {
        return factory(Involved::class)->create($params);
    }

    private function createKnowledgePage($params = []): KnowledgePage
    {
        return factory(KnowledgePage::class)->create($params);
    }

    public function getExpectedJsonStructure(): array
    {
        return [
            'data' => [
                'comments' => [
                    'data' => [
                        [
                            'id',
                            'knowledge_page_id',
                            'type',
                            'ref',
                            'user_id',
                            'text',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function verifyShowPage($data, KnowledgePage $page, User $user, $dir = null)
    {
        $this->assertEquals($page->id, $data->id);
        $this->assertEquals($this->project->id, $data->project_id);
        $this->assertEquals('Test', $data->name);
        $this->assertEquals($this->now->toDateTimeString(), $data->created_at);
        $this->assertEquals($this->now->toDateTimeString(), $data->updated_at);
        $this->assertEquals($user->id, $data->creator_id);
        $this->assertEquals($dir ? $dir->id : null, $data->knowledge_directory_id);
        $this->assertEquals('Lorem ipsum', $data->content);
        $this->assertNull($data->deleted_at);
    }

    protected function assertInteractionCorrect(array $response_data, Interaction $interaction)
    {
        $response_data = $response_data['interactions']['data'];
        $this->assertSame(1, count($response_data));
        $response_data = $response_data[0];

        $this->assertSame($response_data['id'], $interaction->id);
        $this->assertSame($response_data['user_id'], $interaction->user_id);
        $this->assertSame($response_data['source_type'], $interaction->source_type);
        $this->assertSame($response_data['source_id'], $interaction->source_id);
        $this->assertSame($response_data['event_type'], $interaction->event_type);
        $this->assertSame($response_data['action_type'], (string) $interaction->action_type);
        $this->assertSame($response_data['project_id'], $interaction->project_id);
        $this->assertSame($response_data['company_id'], $interaction->company_id);
        $this->assertNotEmpty($response_data['interaction_pings']['data']);
    }
}