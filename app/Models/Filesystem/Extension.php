<?php

namespace App\Models\Filesystem;

class Extension
{
    /**
     * @return array
     */
    public static function denied()
    {
        return [
            'exe',
            'msi',
            'msp',
            'gadget',
            'scr',
            'cpl',
            'jar',
            'zip',
            'rar',
            'pdf',
            'bat',
            'js',
            'jse',
            'ws',
            'wsf',
            'wsc',
            'wsh',
            'scf',
            'lnk',
            'inf',
            'reg',
            'php',
            'html',
        ];
    }
}
