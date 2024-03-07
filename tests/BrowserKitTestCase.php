<?php

namespace Tests;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\Concerns\InteractsWithExceptionHandling;
use Tests\Helpers\CreateUser;
use Tests\Helpers\VerifyResponse;
use Tests\Helpers\Transformer;
use Tests\Helpers\CompanyResource;
use Tests\Helpers\ResponseHelper;
use Laravel\BrowserKitTesting\TestCase as BaseTestCase;
use DB;
use Illuminate\Foundation\Testing\DatabaseTransactions;

abstract class BrowserKitTestCase extends BaseTestCase
{
    use CreateUser, VerifyResponse, Transformer, CompanyResource, CreatesApplication, InteractsWithExceptionHandling, ResponseHelper;
    use DatabaseTransactions;
    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    protected function tearDown():void
    {
        $this->beforeApplicationDestroyed(function () {
            DB::disconnect();
        });

        parent::tearDown();
        // make sure test mode is disabled now
        Carbon::setTestNow();
    }
}
