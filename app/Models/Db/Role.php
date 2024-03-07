<?php

namespace App\Models\Db;

use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Other\RoleType;

class Role extends Model
{
    /**
     * {inheritdoc}.
     */
    public $timestamps = false;

    /**
     * {inheritdoc}.
     */
    protected $fillable = ['name', 'default'];

    /**
     * Get role by name.
     *
     * @param string $name
     * @param bool $soft
     *
     * @return mixed
     */
    public static function findByName($name, $soft = false)
    {
        $query = self::where('name', $name);

        return $soft ? $query->first() : $query->firstOrFail();
    }

    // relationships

    /**
     * Role can be assigned to multiple companies.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function companies()
    {
        return $this->belongsToMany(Company::class);
    }

    public function files()
    {
        return $this->morphedByMany(
            File::class,
            'permissionable',
            'permission_role'
        )->withTimestamps();
    }

    /**
     * Role can be assigned to many pages in Knowledge.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function knowledgePages()
    {
        return $this->morphedByMany(KnowledgePage::class, 'permissionable', 'permission_user');
    }

    // scopes
    public function scopeForGantt($query)
    {
        return $query->whereIn('name', [RoleType::DEVELOPER]);
    }

//     public function projectUser()
//    {
//        return $this->hasMany(ProjectUser::class);
//    }
}
