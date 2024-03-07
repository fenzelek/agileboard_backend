<?php

namespace App\Models\Db\Knowledge;

use App\Interfaces\Interactions\IInteractionable;
use App\Interfaces\Involved\IHasInvolved;
use App\Interfaces\PermissibleRelationsInterface;
use App\Models\Db\File;
use App\Models\Db\Interaction;
use App\Models\Db\KnowledgePageComment;
use App\Models\Db\Involved;
use App\Models\Db\Model;
use App\Models\Db\PermissibleRelations;
use App\Models\Db\Project;
use App\Models\Db\Story;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\Other\InvolvedSourceType;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $project_id
 * @property int $creator_id
 */
class KnowledgePage extends Model implements PermissibleRelationsInterface, IInteractionable, IHasInvolved
{
    use SoftDeletes;
    use PermissibleRelations;

    protected $guarded = [];

    protected $dates = ['deleted_at'];

    /**
     * Page belongs to one project.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Page can have many files.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function files()
    {
        return $this->morphToMany(File::class, 'fileable');
    }

    /**
     * Page can be assigned to one directory.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function directory()
    {
        return $this->belongsTo(KnowledgeDirectory::class, 'knowledge_directory_id');
    }

    /**
     * Page might belong to multiple stories.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function stories()
    {
        return $this->morphToMany(Story::class, 'storyable');
    }

    public function comments()
    {
        return $this->hasMany(KnowledgePageComment::class);
    }

    public function interactions(): MorphMany
    {
        return $this->morphMany(Interaction::class, 'source');
    }

    public function involved(): MorphMany
    {
        return $this->morphMany(Involved::class, 'source');
    }

    public function getSourceType(): string
    {
        return InvolvedSourceType::KNOWLEDGE_PAGE;
    }

    public function getSourceId(): int
    {
        return $this->id;
    }
}
