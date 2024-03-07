<?php

namespace App\Models\Db;

use App\Models\Db\Knowledge\KnowledgePage;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\Mnabialek\LaravelEloquentFilter\Traits\Filterable;

class Story extends Model
{
    use SoftDeletes, Filterable;

    protected $guarded = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function files()
    {
        return $this->morphedByMany(File::class, 'storyable');
    }

    public function tickets()
    {
        return $this->morphedByMany(Ticket::class, 'storyable');
    }

    /**
     * Story might be assigned to multiple knowledge pages.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function pages()
    {
        return $this->morphedByMany(KnowledgePage::class, 'storyable');
    }
}
