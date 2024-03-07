<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class EloquentQueryLogProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->environment('local')) {
            /** @var Request $request */
            $request = $this->app->get('request');

            $summary_time = 0;

            Log::channel('eloquent')->info(
                sprintf('%s %s:', $request->getMethod(), $request->getRequestUri())
            );

            DB::listen(function ($query) use ($request, &$summary_time) {
                $summary_time += $query->time;

                Log::channel('eloquent')->debug(
                    sprintf(
                        '%s at %s ms',
                        vsprintf(str_replace('?', '\'%s\'', $query->sql), $query->bindings),
                        $query->time
                    )
                );

                Log::channel('eloquent')->info(
                    sprintf('SUMMARY TIME %s:', $summary_time)
                );
            });
        }
    }
}
