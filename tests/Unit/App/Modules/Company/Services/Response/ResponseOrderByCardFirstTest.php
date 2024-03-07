<?php

namespace Tests\Unit\App\Modules\Company\Services\Response;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery as m;
use Tests\TestCase;
use App\Modules\Company\Services\PayU\Response\ResponseOrderByCardFirst;

class ResponseOrderByCardFirstTest extends TestCase
{
    use DatabaseTransactions;

    private $result;

    public function setUp():void
    {
        parent::setUp();
        $this->result = m::mock(\OpenPayU_Result::class);
    }

    /** @test */
    public function get_error()
    {
        $return = (object) [];

        $this->result->shouldReceive('getStatus')->andReturn('ERROR');
        $this->result->shouldReceive('getResponse')->andReturn($return);

        $responseObject = new ResponseOrderByCardFirst($this->result);
        $this->assertFalse($responseObject->isSuccess());
        $this->assertSame('ERROR', $responseObject->getError());
        $this->assertSame($return, $responseObject->getData());
        $this->assertSame(null, $responseObject->getOrderId());
        $this->assertSame(null, $responseObject->getToken());
        $this->assertSame(null, $responseObject->getRedirectUrl());
    }

    /** @test */
    public function get_success()
    {
        $return = (object) [
            'orderId' => '10',
            'payMethods' => (object) [
                'payMethod' => (object) [
                    'value' => 'asdasd',
                ],
            ],
        ];

        $this->result->shouldReceive('getStatus')->andReturn('SUCCESS');
        $this->result->shouldReceive('getResponse')->andReturn($return);

        $responseObject = new ResponseOrderByCardFirst($this->result);
        $this->assertTrue($responseObject->isSuccess());
        $this->assertFalse($responseObject->getError());
        $this->assertSame($return, $responseObject->getData());
        $this->assertSame('10', $responseObject->getOrderId());
        $this->assertSame('asdasd', $responseObject->getToken());
        $this->assertSame(null, $responseObject->getRedirectUrl());
    }

    /** @test */
    public function get_warning_3ds()
    {
        $return = (object) [
            'orderId' => '10',
            'redirectUri' => 'https://example.pl',
            'payMethods' => (object) [
                'payMethod' => (object) [
                    'value' => 'asdasd',
                ],
            ],
        ];

        $this->result->shouldReceive('getStatus')->andReturn('WARNING_CONTINUE_3DS');
        $this->result->shouldReceive('getResponse')->andReturn($return);

        $responseObject = new ResponseOrderByCardFirst($this->result);
        $this->assertFalse($responseObject->isSuccess());
        $this->assertSame('WARNING_CONTINUE_3DS', $responseObject->getError());
        $this->assertSame($return, $responseObject->getData());
        $this->assertSame('10', $responseObject->getOrderId());
        $this->assertSame('asdasd', $responseObject->getToken());
        $this->assertSame('https://example.pl', $responseObject->getRedirectUrl());
    }

    /** @test */
    public function get_warning_cvv()
    {
        $return = (object) [
            'orderId' => '10',
            'redirectUri' => 'https://example.pl',
        ];

        $this->result->shouldReceive('getStatus')->andReturn('WARNING_CONTINUE_CVV');
        $this->result->shouldReceive('getResponse')->andReturn($return);

        $responseObject = new ResponseOrderByCardFirst($this->result);
        $this->assertFalse($responseObject->isSuccess());
        $this->assertSame('WARNING_CONTINUE_CVV', $responseObject->getError());
        $this->assertSame($return, $responseObject->getData());
        $this->assertSame('10', $responseObject->getOrderId());
        $this->assertSame(null, $responseObject->getToken());
        $this->assertSame(null, $responseObject->getRedirectUrl());
    }
}
