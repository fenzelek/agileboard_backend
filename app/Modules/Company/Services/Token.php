<?php

namespace App\Modules\Company\Services;

use App\Models\Db\CompanyToken;
use App\Modules\Company\Exceptions\ExpiredToken;
use App\Modules\Company\Exceptions\InvalidToken;
use App\Modules\Company\Exceptions\NoTokenFound;
use App\Modules\Company\Exceptions\TooShortToken;
use Exception;

class Token
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $cipher = 'AES-256-CBC';

    /**
     * @var CompanyToken
     */
    protected $company_token;

    /**
     * Token constructor.
     *
     * @param CompanyToken $company_token
     * @param $key
     *
     * @throws TooShortToken
     */
    public function __construct(CompanyToken $company_token, $key)
    {
        $this->key = $key;
        $this->company_token = $company_token;

        if (mb_strlen($this->key) < 30) {
            throw new TooShortToken();
        }
    }

    /**
     * Encode given token.
     *
     * @param string $token
     * @param int|null $timestamp
     *
     * @return string
     */
    public function encode($token, $timestamp = null)
    {
        // if no timestamp given, current timestamp will be used
        if ($timestamp === null) {
            $timestamp = time();
        }

        return $this->crypt(compact('token', 'timestamp'));
    }

    /**
     * Decode given data.
     *
     * @param string $value
     *
     * @return CompanyToken
     *
     * @throws ExpiredToken
     * @throws InvalidToken
     * @throws NoTokenFound
     */
    public function decode($value)
    {
        try {
            $value = $this->decrypt($value);
        } catch (Exception $e) {
            throw new InvalidToken();
        }

        $api_token = $this->company_token::fromApiToken($value['token']);

        if (! $api_token) {
            throw new NoTokenFound();
        }

        if ($this->isExpired($api_token, $value['timestamp'])) {
            throw new ExpiredToken();
        }

        return $api_token;
    }

    /**
     * Verify whether current token is expired.
     *
     * @param CompanyToken $token
     * @param int $token_timestamp 
     *
     * @return bool
     */
    protected function isExpired(CompanyToken $token, $token_timestamp)
    {
        if ($token->unexpired){
            return false;
        }
        return abs($token_timestamp - time()) > $token->ttl * 60;
    }

    /**
     * Encrypts given data with key and cipher.
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function crypt($value)
    {
        $iv = random_bytes(16);

        $value = openssl_encrypt(serialize($value), $this->cipher, $this->key, 0, $iv);

        $iv = base64_encode($iv);

        return base64_encode(json_encode(compact('iv', 'value')));
    }

    /**
     * Decrypt given token.
     *
     * @param string $value
     *
     * @return mixed
     * @throws InvalidToken
     */
    protected function decrypt($value)
    {
        $json = json_decode(base64_decode($value));

        $iv = base64_decode($json->iv);
        $value = openssl_decrypt($json->value, $this->cipher, $this->key, 0, $iv);

        if ($value === false) {
            throw new InvalidToken();
        }

        return unserialize($value);
    }
}
