<?php

namespace Tests\Unit\App\Modules\SaleReport\Services\ExternalReport;

use App\Modules\SaleReport\Services\ExternalReport;
use App\Modules\SaleReport\Services\Firmen;
use App\Modules\SaleReport\Services\Optima;
use Tests\TestCase;

class GetProviderTest extends TestCase
{
    /** @test */
    public function it_throws_exception_when_invalid_export_name_was_given()
    {
        $external_report = app()->make(ExternalReport::class);

        $this->expectExceptionMessage('Wrong application setting for export provider.');

        $external_report->getProvider('invalid');
    }

    /** @test */
    public function it_returns_optima_provider_when_optima_export_name_was_given()
    {
        $this->withoutExceptionHandling();
        $external_report = app()->make(ExternalReport::class);

        $provider = $external_report->getProvider('optima');
        $this->assertTrue($provider instanceof Optima);
    }

    /** @test */
    public function it_returns_firmen_provider_when_firmen_export_name_was_given()
    {
        $this->withoutExceptionHandling();
        $external_report = app()->make(ExternalReport::class);

        $provider = $external_report->getProvider('firmen');
        $this->assertTrue($provider instanceof Firmen);
    }
}
