<?php

namespace App\Models\Db;

class HistoryField extends Model
{
    /**
     * @inheritdoc
     */
    public $timestamps = false;
    /**
     * @inheritdoc
     */
    protected $guarded = [
    ];

    /**
     * Get ID.
     *
     * @param $object_type
     * @param $field_name
     *
     * @return null|int
     */
    public static function getId($object_type, $field_name)
    {
        $history = self::where('object_type', $object_type)
            ->where('field_name', $field_name)->first();
        if ($history) {
            return $history->id;
        }

        return;
    }
}
