<?php

namespace Tests\Unit\App\Modules\SaleReport\Services\ExternalReport;

use App\Models\Db\User;
use App\Modules\SaleReport\Services\ExternalReport;
use App\Modules\SaleReport\Services\Report;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class GetInvoicesTest extends TestCase
{
    /** @test */
    public function it_returns_content_type_returned_by_provider()
    {
        $collection = 'Sample collection';

        $app = Mockery::mock(Application::class);
        $report = Mockery::mock(Report::class);

        $external_report = new ExternalReport($app, $report);

        $user = new User();
        $request = Mockery::mock(Request::class);

        $simple_mock = Mockery::mock();
        $simple_mock->shouldReceive('orderBy->orderBy->with->get')->once()
            ->andReturn($collection);

        $report->shouldReceive('filterInvoicesRegistry')->once()->andReturn($simple_mock);

        $this->assertSame($collection, $external_report->getInvoices($request, $user));
    }
}
