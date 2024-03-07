<?php

namespace App\Models\Db\Knowledge;

use App\Interfaces\PermissibleRelationsInterface;
use App\Models\Db\Model;
use App\Models\Db\PermissibleRelations;
use App\Models\Db\Project;

class KnowledgeDirectory extends Model implements PermissibleRelationsInterface
{
    use PermissibleRelations;

    protected $guarded = [];

    /**
     * Directory belongs to one project.
     *
     * @return mixed
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Directory can have many pages.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pages()
    {
        return $this->hasMany(KnowledgePage::class);
    }
}
