<?php

namespace App\Models\Other;

class KnowledgePageCommentType
{
    /**
     * Delivery type.
     */
    const GLOBAL = 'global';

    const INTERNAL = 'internal';

    /**
     * Get all available addresses type.
     *
     * @return array
     */
    public static function all()
    {
        return [
            self::GLOBAL,
            self::INTERNAL,
        ];
    }
}
