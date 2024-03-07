<?php

namespace App\Models\Db;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserShortToken extends Model
{
    use SoftDeletes;

    /**
     * @inheritdoc
     */
    protected $guarded = [];

    /**
     * @inheritdoc
     */
    protected $dates = ['expires_at'];

    /**
     * Converts token to API token format.
     *
     * @return string
     */
    public function toQuickToken()
    {
        return $this->id . '.' . $this->token;
    }

    /**
     * Find short token by given API token.
     *
     * @param string $api_token
     *
     * @return CompanyToken|null
     */
    public static function fromQuickToken($api_token)
    {
        if (mb_strpos($api_token, '.') === false) {
            return;
        }
        list($id, $token) = explode('.', $api_token, 2);

        return self::where('token', $token)->find((int) $id);
    }

    /**
     * Verify whether token is expired.
     *
     * @return bool
     */
    public function isExpired()
    {
        return Carbon::now()->gt($this->expires_at);
    }

    /**
     * Token belongs to single user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
