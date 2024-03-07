<?php

namespace App\Models\Db;

use App\Interfaces\PermissibleRelationsInterface;
use App\Models\Db\Knowledge\KnowledgePage;

class File extends Model implements PermissibleRelationsInterface
{
    use PermissibleRelations;

    const TYPE_OTHER = 'other';
    const TYPE_NONE = 'none';

    protected $guarded = [];

    /**
     * get types.
     *
     * @return array
     */
    public static function types()
    {
        return [
            'images' => ['jpg', 'jpeg', 'gif', 'png', 'bmp'],
            'pdf' => ['pdf'],
            'documents' => ['doc', 'docx', 'odt', 'txt', 'rtf'],
            'spreadsheets' => ['xls', 'xlsx', 'ods', 'csv'],
        ];
    }

    /**
     * get list names of types.
     *
     * @return array
     */
    public static function getListTypes()
    {
        $types = [self::TYPE_OTHER, self::TYPE_NONE];
        foreach (self::types() as $type => $extensions) {
            $types [] = $type;
        }

        return $types;
    }

    /**
     * Get file extensions for named groups.
     *
     * @return array
     */
    public static function getNamedGroupsExtensions()
    {
        $extensions = [];
        foreach (self::types() as $type) {
            $extensions = array_merge($extensions, $type);
        }

        return $extensions;
    }

    /**
     * RELATIONS.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function tickets()
    {
        return $this->morphedByMany(Ticket::class, 'fileable');
    }

    public function stories()
    {
        return $this->morphedByMany(Story::class, 'fileable');
    }

    public function pages()
    {
        return $this->morphedByMany(KnowledgePage::class, 'fileable');
    }

    public function resources()
    {
        return $this->hasMany(Fileable::class);
    }

    /**
     * File was created be single user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
