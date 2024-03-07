<?php

namespace App\Models\Db;

use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyToken extends Model
{
    use SoftDeletes;

    /**
     * @inheritdoc
     */
    protected $guarded = [];

    /**
     * @inheritdoc
     */
    protected $nullable = ['ip_from', 'ip_to', 'domain'];

    /**
     * Converts token to API token format.
     *
     * @return string
     */
    public function toApiToken()
    {
        return $this->id . '.' . $this->token;
    }

    /**
     * Find company token by given API token.
     *
     * @param string $api_token
     *
     * @return CompanyToken|null
     */
    public static function fromApiToken($api_token)
    {
        if (mb_strpos($api_token, '.') === false) {
            return;
        }
        list($id, $token) = explode('.', $api_token, 2);

        return self::where('token', $token)->find((int) $id);
    }

    /**
     * Token is assigned to specific user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Token is assigned for specific API role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Verify whether token is valid for given combination host and ip.
     *
     * @param string $host
     * @param string $ip
     *
     * @return bool
     */
    public function validForServer($host, $ip)
    {
        // if domain is set and it's different than given host, it's invalid
        if ($this->domain && $this->domain != $host) {
            return false;
        }

        // if single ip is set and it's different than given ip, it's invalid
        if ($this->ip_from && ! $this->ip_to && $this->ip_from != $ip) {
            return false;
        }

        // if ip range is set and it's not in range it's invalid
        if ($this->ip_from && $this->ip_to &&
            (ip2long($ip) < ip2long($this->ip_from) || ip2long($ip) > ip2long($this->ip_to))
        ) {
            return false;
        }

        // otherwise it's valid - all conditions are fine or no domain/ip set
        return true;
    }
}
