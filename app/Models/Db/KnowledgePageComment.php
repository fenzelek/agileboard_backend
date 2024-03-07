<?php

namespace App\Models\Db;

use App\Interfaces\Interactions\IInteractionable;
use App\Models\Db\Knowledge\KnowledgePage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int $id
 * @property int $user_id
 * @property int $knowledge_page_id
 * @property string $type
 * @property ?string $ref
 * @property ?string $text
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read KnowledgePage|null $knowledgePage
 * @property-read User|null $user
 * @method static Builder|KnowledgePageComment newModelQuery()
 * @method static Builder|KnowledgePageComment newQuery()
 * @method static Builder|KnowledgePageComment query()
 */
class KnowledgePageComment extends Model implements IInteractionable
{
    /**
     * @inheritdoc
     */
    protected $guarded = [];

    public function knowledgePage(): BelongsTo
    {
        return $this->belongsTo(KnowledgePage::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function interactions(): MorphMany
    {
        return $this->morphMany(Interaction::class, 'source');
    }
}
