<?php

namespace Tests\Unit\App\Modules\Company\Services;

use App\Models\Db\User;
use App\Models\Db\Payment;
use App\Models\Db\CompanyModuleHistory;
use App\Modules\Company\Services\PayU\OrderSimplyParams;
use App\Modules\Company\Services\PayU\OrderByCardNextParams;
use App\Modules\Company\Services\PayU\OrderByCardFirstParams;
use App\Modules\Company\Services\PayU\ParamsFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ParamsFactoryTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function simplyOrder()
    {
        config()->set('payu.back_url', 'example.com');
        config()->set('payu.pln.notify_url', 'example.pl');

        $user = factory(User::class)->create();
        $payment = factory(Payment::class)->create();
        $history = factory(CompanyModuleHistory::class)->create([
            'package_id' => null,
            'transaction_id' => $payment->transaction->id,
        ]);

        $params = [
            'subscription' => false,
            'payment_id' => $payment->id,
            'type' => Payment::TYPE_SIMPLE,
            'token' => '',
            'card_exp_month' => '05',
            'card_exp_year' => '2020',
            'card_cvv' => '111',
            'card_number' => '3213213213213212121',
        ];

        $factory = new ParamsFactory();

        $paramsObject = $factory->createOrderParams($params, $user, $payment);

        $this->assertTrue($paramsObject instanceof OrderSimplyParams);

        $this->assertSame([
            'continueUrl' => config('payu.back_url'),
            'notifyUrl' => config('payu.pln.notify_url'),
            'customerIp' => request()->ip(),
            'description' => config('app.name') . ' - order ' . $payment->id,
            'currencyCode' => $paramsObject::CURRENCY_PLN,
            'totalAmount' => $payment->price_total,
            'extOrderId' => $payment->id,
            'settings' => ['invoiceDisabled' => true],
            'products' => [
                [
                    'name' => 'Module',
                    'unitPrice' => $payment->price_total,
                    'quantity' => 1,
                ],
            ],
            'buyer' => [
                'extCustomerId' => $user->id,
                'email' => $user->email,
            ],
            'merchantPosId' => '',
        ], $paramsObject->get());
    }

    /** @test */
    public function orderByCardNext()
    {
        config()->set('payu.back_url', 'example.com');
        config()->set('payu.pln.notify_url', 'example.pl');

        $user = factory(User::class)->create();
        $payment = factory(Payment::class)->create();
        $history = factory(CompanyModuleHistory::class)->create([
            'package_id' => null,
            'transaction_id' => $payment->transaction->id,
        ]);

        $params = [
            'subscription' => false,
            'payment_id' => $payment->id,
            'type' => Payment::TYPE_CARD,
            'token' => 'dssddsdsdfsds',
            'card_exp_month' => '05',
            'card_exp_year' => '2020',
            'card_cvv' => '111',
            'card_number' => '3213213213213212121',
        ];

        $factory = new ParamsFactory();

        $paramsObject = $factory->createOrderParams($params, $user, $payment);

        $this->assertTrue($paramsObject instanceof OrderByCardNextParams);

        $this->assertSame([
            'continueUrl' => config('payu.back_url'),
            'notifyUrl' => config('payu.pln.notify_url'),
            'customerIp' => request()->ip(),
            'description' => config('app.name') . ' - order ' . $payment->id,
            'currencyCode' => $paramsObject::CURRENCY_PLN,
            'totalAmount' => $payment->price_total,
            'extOrderId' => $payment->id,
            'settings' => ['invoiceDisabled' => true],
            'products' => [
                [
                    'name' => 'Module',
                    'unitPrice' => $payment->price_total,
                    'quantity' => 1,
                ],
            ],
            'buyer' => [
                'extCustomerId' => $user->id,
                'email' => $user->email,
            ],
            'payMethods' => [
                'payMethod' => [
                    'value' => $params['token'],
                    'type' => 'CARD_TOKEN',
                ],
            ],
            'recurring' => 'STANDARD',
            'merchantPosId' => '',
        ], $paramsObject->get());
    }

    /** @test */
    public function orderByCardFirst()
    {
        config()->set('payu.back_url', 'example.com');
        config()->set('payu.pln.notify_url', 'example.pl');

        $user = factory(User::class)->create();
        $payment = factory(Payment::class)->create();
        $history = factory(CompanyModuleHistory::class)->create([
            'package_id' => 1,
            'transaction_id' => $payment->transaction->id,
        ]);

        $params = [
            'subscription' => false,
            'payment_id' => $payment->id,
            'type' => Payment::TYPE_CARD,
            'token' => '',
            'card_exp_month' => '05',
            'card_exp_year' => '2020',
            'card_cvv' => '111',
            'card_number' => '3213213213213212121',
        ];

        $factory = new ParamsFactory();

        $paramsObject = $factory->createOrderParams($params, $user, $payment);

        $this->assertTrue($paramsObject instanceof OrderByCardFirstParams);

        $this->assertSame([
            'continueUrl' => config('payu.back_url'),
            'notifyUrl' => config('payu.pln.notify_url'),
            'customerIp' => request()->ip(),
            'description' => config('app.name') . ' - order ' . $payment->id,
            'currencyCode' => $paramsObject::CURRENCY_PLN,
            'totalAmount' => $payment->price_total,
            'extOrderId' => $payment->id,
            'settings' => ['invoiceDisabled' => true],
            'products' => [
                [
                    'name' => 'Package',
                    'unitPrice' => $payment->price_total,
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
                        'number' => $params['card_number'],
                        'expirationMonth' => $params['card_exp_month'],
                        'expirationYear' => $params['card_exp_year'],
                        'cvv' => $params['card_cvv'],
                    ],
                ],
            ],
            'recurring' => 'FIRST',
            'merchantPosId' => '',
        ], $paramsObject->get());
    }
}
