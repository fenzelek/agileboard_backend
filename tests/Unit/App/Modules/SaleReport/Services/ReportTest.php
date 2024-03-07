<?php

namespace Tests\Unit\App\Modules\SaleReport\Services;

use Mockery as m;
use App\Modules\SaleReport\Services\Report;
use App\Http\Requests\Request;
use App\Models\Db\User;
use stdClass;
use PHPUnit\Framework\TestCase;

class ReportTest extends TestCase
{
    protected $report;
    protected $report_data;
    protected $request;
    protected $user;

    protected function setUp():void
    {
        parent::setUp();

        $this->report = m::mock(Report::class)->makePartial();
        $this->report_data = m::mock(stdClass::class);
        $this->request = m::mock(Request::class);
        $this->user = m::mock(User::class);
    }

    /** @test */
    public function check_if_method_invoicesRegisterReport_return_right_data()
    {
        $invoices = $this->getFakeInvoices();

        $this->expectationDeclarations($invoices);

        $summary = [
            'vat_rates' => [
                [
                    'vat_rate_id' => 1,
                    'vat_rate_name' => 'test1',
                    'price_net' => 12.65,
                    'price_gross' => 15.89,
                    'vat_sum' => 3.24,
                ],
                [
                    'vat_rate_id' => 2,
                    'vat_rate_name' => 'test2',
                    'price_net' => 3.33,
                    'price_gross' => 3.33,
                    'vat_sum' => 0.0,
                ],
                [
                    'vat_rate_id' => 3,
                    'vat_rate_name' => 'test3',
                    'price_net' => 8.0,
                    'price_gross' => 10.0,
                    'vat_sum' => 2.0,
                ],
                [
                    'vat_rate_id' => 4,
                    'vat_rate_name' => 'test4',
                    'price_net' => 2.22,
                    'price_gross' => 2.22,
                    'vat_sum' => 0.0,
                ],
                [
                    'vat_rate_id' => 5,
                    'vat_rate_name' => 'test5',
                    'price_net' => 4.0,
                    'price_gross' => 5.0,
                    'vat_sum' => 1.0,
                ],
            ],
            'price_net' => 30.2,
            'vat_sum' => 6.24,
            'price_gross' => 36.44,
        ];

        $this->assertEquals(
            $summary,
            $this->report->invoicesRegisterReport($this->request, $this->user)
        );
    }

    /** @test */
    public function check_if_method_invoicesRegisterReport_return_right_data_from_empty_invoices()
    {
        $invoices = [];

        $this->expectationDeclarations($invoices);

        $summary = [
            'vat_rates' => [],
            'price_net' => 0.0,
            'vat_sum' => 0.0,
            'price_gross' => 0.0,
        ];

        $this->assertEquals(
            $summary,
            $this->report->invoicesRegisterReport($this->request, $this->user)
        );
    }

    protected function expectationDeclarations($invoices)
    {
        $this->report->shouldReceive('filterInvoicesRegistry')->with($this->request, $this->user)
            ->once()->andReturn($this->report_data);

        $this->report_data->shouldReceive('orderBy')->with('id')->once()
            ->andReturn($this->report_data);
        $this->report_data->shouldReceive('get')->once()->andReturn($invoices);
    }

    protected function getFakeInvoices()
    {
        return [
            (object) [
                'taxes' => (object) [
                    (object) [
                        'vat_rate_id' => 1,
                        'vatRate' => (object) [
                            'name' => 'test1',
                        ],
                        'price_net' => 451,
                        'price_gross' => 611,
                    ],
                    (object) [
                        'vat_rate_id' => 2,
                        'vatRate' => (object) [
                            'name' => 'test2',
                        ],
                        'price_net' => 111,
                        'price_gross' => 111,
                    ],
                    (object) [
                        'vat_rate_id' => 3,
                        'vatRate' => (object) [
                            'name' => 'test3',
                        ],
                        'price_net' => 400,
                        'price_gross' => 500,
                    ],
                ],
            ],
            (object) [
                'taxes' => (object) [
                    (object) [
                        'vat_rate_id' => 1,
                        'vatRate' => (object) [
                            'name' => 'test1',
                        ],
                        'price_net' => 148,
                        'price_gross' => 201,
                    ],
                    (object) [
                        'vat_rate_id' => 4,
                        'vatRate' => (object) [
                            'name' => 'test4',
                        ],
                        'price_net' => 222,
                        'price_gross' => 222,
                    ],
                    (object) [
                        'vat_rate_id' => 3,
                        'vatRate' => (object) [
                            'name' => 'test3',
                        ],
                        'price_net' => 400,
                        'price_gross' => 500,
                    ],
                ],
            ],
            (object) [
                'taxes' => (object) [
                    (object) [
                        'vat_rate_id' => 1,
                        'vatRate' => (object) [
                            'name' => 'test1',
                        ],
                        'price_net' => 666,
                        'price_gross' => 777,
                    ],
                    (object) [
                        'vat_rate_id' => 2,
                        'vatRate' => (object) [
                            'name' => 'test2',
                        ],
                        'price_net' => 222,
                        'price_gross' => 222,
                    ],
                    (object) [
                        'vat_rate_id' => 5,
                        'vatRate' => (object) [
                            'name' => 'test5',
                        ],
                        'price_net' => 400,
                        'price_gross' => 500,
                    ],
                ],
            ],
        ];
    }
}
