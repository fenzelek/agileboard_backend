<?php

namespace App\Models\Db\Integration\TimeTracking;

use App\Models\Db\Integration\Integration;
use App\Models\Db\Model;
use App\Models\Db\User as ModelUser;
use App\Services\Mnabialek\LaravelEloquentFilter\Traits\Filterable;

/**
 * @property string $first_name
 * @property string $last_name
 */
class User extends Model
{
    use Filterable;

    /**
     * @inheritdoc
     */
    protected $table = 'time_tracking_users';

    /**
     * @inheritdoc
     */
    protected $guarded = [];

    /**
     * Time tracking user belongs to single integration.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function integration()
    {
        return $this->belongsTo(Integration::class);
    }

    /**
     * Time tracking user belongs to single system user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(ModelUser::class);
    }

    /**
     * Time tracking user has multiple time tracking activities.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activities()
    {
        return $this->hasMany(Activity::class, 'time_tracking_user_id');
    }

    /**
     * Verify whether time tracking user has assigned system user.
     *
     * @return bool
     */
    public function hasMatchingSystemUser()
    {
        return $this->user_id ? true : false;
    }

    /**
     * Set system user.
     *
     * @param ModelUser $user
     */
    public function setSystemUser(ModelUser $user)
    {
        $this->update(['user_id' => $user->getKey()]);
        $this->activities()->update(['user_id' => $this->user_id]);
    }
}
