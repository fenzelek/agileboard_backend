<?php

namespace Tests;

use DB;
use Tests\Helpers\CreateUser;
use Tests\Helpers\ResponseHelper;
use Tests\Helpers\VerifyResponse;
use Tests\Helpers\Transformer;
use Tests\Helpers\CompanyResource;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
//use Laravel\BrowserKitTesting\TestCase as BaseTestCase;
//use PHPUnit\Framework\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\Concerns\InteractsWithExceptionHandling;

abstract class TestCase extends BaseTestCase
{
    use CreateUser, VerifyResponse, Transformer, CompanyResource, CreatesApplication, ResponseHelper, InteractsWithExceptionHandling;
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
    }
}
