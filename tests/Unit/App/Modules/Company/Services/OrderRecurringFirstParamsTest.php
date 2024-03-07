<?php

namespace Tests\Unit\App\Modules\Company\Services;

use App\Modules\Company\Services\PayU\OrderByCardFirstParams;
use App\Models\Db\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrderRecurringFirstParamsTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function get_error()
    {
        $params = new OrderByCardFirstParams();
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

        $params = new OrderByCardFirstParams();
        $params->setTotalAmount(10, $params::CURRENCY_PLN);
        $params->setOrderId(20);
        $params->setBuyer($user);
        $params->setCard('123', '12', '2019', '311');
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
            'payMethods' => [
                'payMethod' => [
                    'card' => [
                        'number' => '123',
                        'expirationMonth' => '12',
                        'expirationYear' => '2019',
                        'cvv' => '311',
                    ],
                ],
            ],
            'recurring' => 'FIRST',
            'merchantPosId' => '',
        ], $params->get());
    }
}
