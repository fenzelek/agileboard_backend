<?php

namespace Tests\Unit\App\Modules\Company\Services;

use App\Modules\Company\Services\PayU\OrderSimplyParams;
use App\Models\Db\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrderSimplyParamsTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function get_error()
    {
        $params = new OrderSimplyParams();
        $this->expectException('\Exception');
        $this->expectExceptionMessage('One or more of required values is missing.');
        $params->get();
    }

    /** @test */
    public function getSuccess()
    {
        config()->set('payu.back_url', 'example.com');
        config()->set('payu.pln.notify_url', 'example.pl');

        $user = factory(User::class)->create();

        $params = new OrderSimplyParams();
        $params->setTotalAmount(10, $params::CURRENCY_PLN);
        $params->setOrderId(20);
        $params->setBuyer($user);
        $params->setProducts([
            (object) [
                'name' => 'test',
                'price' => 30,
            ],
        ]);

        $this->assertSame([
            'continueUrl' => config('payu.back_url'),
            'notifyUrl' => config('payu.pln.notify_url'),
            'customerIp' => request()->ip(),
            'description' => config('app.name') . ' - order 20',
            'currencyCode' => $params::CURRENCY_PLN,
            'totalAmount' => 10,
            'extOrderId' => 20,
            'settings' => ['invoiceDisabled' => true],
            'products' => [
                [
                    'name' => 'test',
                    'unitPrice' => 30,
                    'quantity' => 1,
                ],
            ],
            'buyer' => [
                'extCustomerId' => $user->id,
                'email' => $user->email,
            ],
            'merchantPosId' => '',
        ], $params->get());
    }
}
