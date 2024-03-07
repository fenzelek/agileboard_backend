<?php

namespace App\Models\Db;

class History extends Model
{
    /**
     * @inheritdoc
     */
    public $timestamps = false;
    /**
     * @inheritdoc
     */
    protected $table = 'history';

    /**
     * @inheritdoc
     */
    protected $guarded = [
    ];

    /**
     * @inheritdoc
     */
    protected $dates = ['created_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function field()
    {
        return $this->belongsTo(HistoryField::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'resource_id');
    }
}
