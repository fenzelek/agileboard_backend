<?php

namespace App\Models\Db;

use App\Services\Mnabialek\LaravelEloquentFilter\Traits\Filterable;

class ProjectUser extends Model
{
    use Filterable;

    /**
     * @inheritdoc
     */
    protected $table = 'project_user';

    /**
     * Single record belongs to single project.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Single record belongs to single user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Single record belongs to single role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
