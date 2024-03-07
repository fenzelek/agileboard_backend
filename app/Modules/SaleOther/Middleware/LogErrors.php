<?php

namespace App\Modules\SaleOther\Middleware;

use App\Modules\SaleOther\Services\LogErrors as ErrorLogger;
use Illuminate\Http\Request;
use Closure;

class LogErrors
{
    /**
     * @var ErrorLogger
     */
    protected $error_logger;

    /**
     * LogErrors constructor.
     */
    public function __construct(ErrorLogger $error_logger)
    {
        $this->error_logger = $error_logger;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $user = auth()->user();

        if ($this->error_logger->loggable($response->status())) {
            $this->error_logger->create($request, $response, $user);
        }

        return $response;
    }
}
