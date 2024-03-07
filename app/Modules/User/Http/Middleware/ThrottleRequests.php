<?php

namespace App\Modules\User\Http\Middleware;

use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use Closure;
use Illuminate\Cache\RateLimiter;

/**
 * Class ThrottleRequests.
 *
 * This class is based on Illuminate\Routing\Middleware\ThrottleRequests however
 * it return custom response in case of too many attempts
 */
class ThrottleRequests
{
    /**
     * The rate limiter instance.
     *
     * @var \Illuminate\Cache\RateLimiter
     */
    protected $limiter;

    /**
     * Create a new request throttler.
     *
     * @param  \Illuminate\Cache\RateLimiter $limiter
     */
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param  int $maxAttempts
     * @param  int $decayMinutes
     *
     * @return mixed
     */
    public function handle(
        $request,
        Closure $next,
        $maxAttempts = 60,
        $decayMinutes = 1
    ) {
        $key = $this->resolveRequestSignature($request);

        if ($this->limiter->tooManyAttempts(
            $key,
            $maxAttempts,
            $decayMinutes
        )
        ) {
            return ApiResponse::responseError(
                ErrorCode::REQUESTS_RATE_EXCEEDED,
                429,
                [],
                [
                    'Retry-After' => $this->limiter->availableIn($key),
                    'X-RateLimit-Limit' => $maxAttempts,
                    'X-RateLimit-Remaining' => 0,
                ]
            );
        }

        $this->limiter->hit($key, $decayMinutes);

        $response = $next($request);

        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $maxAttempts -
                $this->limiter->attempts($key) + 1,
        ]);

        return $response;
    }

    /**
     * Resolve request signature.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return string
     */
    protected function resolveRequestSignature($request)
    {
        return $request->fingerprint();
    }
}
