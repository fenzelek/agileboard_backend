<?php

namespace App\Models\Db;

use Illuminate\Database\Eloquent\SoftDeletes;

class ErrorLog extends Model
{
    use SoftDeletes;
    use \App\Services\Mnabialek\LaravelEloquentFilter\Traits\Filterable;

    protected $guarded = [];

    /**
     * Many errors can belong to one user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
