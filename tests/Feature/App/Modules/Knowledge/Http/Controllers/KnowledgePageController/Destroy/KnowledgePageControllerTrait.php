<?php
declare(strict_types=1);

namespace Tests\Feature\App\Modules\Knowledge\Http\Controllers\KnowledgePageController\Destroy;

use App\Models\Db\Interaction;
use App\Models\Db\InteractionPing;
use App\Models\Db\Involved;
use App\Models\Db\Knowledge\KnowledgePage;

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

    private function createInteractionPing(int $interaction_id): InteractionPing
    {
        return factory(InteractionPing::class)->create([
            'interaction_id' => $interaction_id
        ]);
    }

    private function createInteraction(): Interaction
    {
        return factory(Interaction::class)->create();
    }
}