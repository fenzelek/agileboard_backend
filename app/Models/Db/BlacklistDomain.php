<?php

namespace App\Models\Db;

class BlacklistDomain extends Model
{
    public $timestamps = false;

    protected $fillable = ['domain'];
}
