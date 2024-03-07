<?php

namespace App\Models\Db;

class GusCompany extends Model
{
    /**
     * @inheritdoc
     */
    protected $guarded = [];

    /**
     * Find companies with passed vatin.
     *
     * @param int $vatin
     *
     * return mixed
     */
    public static function findByVatin($vatin)
    {
        return self::where('vatin', $vatin)->get();
    }

    /**
     * Finding company items by vatin and deleting them.
     *
     * @param $vatin
     */
    public function findAndDestroy($vatin)
    {
        $items_id = $this->findByVatin($vatin)->pluck('id')->toArray();
        $this->destroy($items_id);
    }
}
