<?php

namespace Tests\Unit\App\Modules\Company\Services\Response;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery as m;
use Tests\TestCase;
use App\Modules\Company\Services\PayU\Response\ResponseOrderSimply;

class ResponseOrderSimplyTest extends TestCase
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

        $responseObject = new ResponseOrderSimply($this->result);
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
            'redirectUri' => 'https://example.pl',
        ];

        $this->result->shouldReceive('getStatus')->andReturn('SUCCESS');
        $this->result->shouldReceive('getResponse')->andReturn($return);

        $responseObject = new ResponseOrderSimply($this->result);
        $this->assertTrue($responseObject->isSuccess());
        $this->assertFalse($responseObject->getError());
        $this->assertSame($return, $responseObject->getData());
        $this->assertSame('10', $responseObject->getOrderId());
        $this->assertSame(null, $responseObject->getToken());
        $this->assertSame('https://example.pl', $responseObject->getRedirectUrl());
    }
}
