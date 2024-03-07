<?php

namespace App\Models\Db;

use App\Models\Other\InvitationStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\Notifiable;

class Invitation extends Model
{
    use Notifiable;

    /**
     * @inheritdoc
     */
    public $incrementing = false;

    /**
     * @inheritdoc
     */
    public $timestamps = false;

    /**
     * @inheritdoc
     */
    protected $primaryKey = 'unique_hash';

    /**
     * @inheritdoc
     */
    protected $fillable = [
        'email',
        'first_name',
        'last_name',
        'role_id',
        'expiration_time',
    ];

    /**
     * @inheritdoc
     */
    protected $dates = [
        'created_at',
        'expiration_time',
    ];

    /**
     * Make sure created_at column will be filled when we set hash.
     *
     * @param $value
     */
    public function setUniqueHashAttribute($value)
    {
        $this->attributes['unique_hash'] = $value;
        $this->attributes['created_at'] = Carbon::now();
    }

    /**
     * Invitation belongs to company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Invitation is for selected role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Verify whether invitation has been expired.
     *
     * @return bool
     */
    public function isExpired()
    {
        return Carbon::parse($this->expiration_time)->isPast();
    }

    /**
     * Verify whether activation is pending.
     *
     * @return int
     */
    public function isPending()
    {
        return $this->status == InvitationStatus::PENDING;
    }

    /**
     * Choose only pending invitations.
     *
     * @param Builder $q
     */
    public function scopePending($q)
    {
        $q->where('status', InvitationStatus::PENDING);
    }
}
