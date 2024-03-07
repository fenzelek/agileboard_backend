<?php

namespace Tests\Unit\App\Modules\Company\Services;

use App\Models\Db\ModuleMod;
use App\Models\Db\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery as m;
use Tests\TestCase;
use App\Models\Db\Payment;
use App\Models\Db\ModPrice;
use App\Models\Db\Subscription;
use App\Models\Db\CompanyModuleHistory;
use App\Models\Other\PaymentStatus;
use App\Modules\Company\Services\PayU\ResponseSaver;
use App\Modules\Company\Services\PayU\Response\ResponseOrderSimply;
use App\Modules\Company\Services\PayU\Response\ResponseOrderByCardFirst;
use App\Modules\Company\Services\PayU\Response\ResponseOrderByCardNext;

class ResponseSaverPayuTest extends TestCase
{
    use DatabaseTransactions;

    private $payment;
    private $subscription_user;

    public function setUp():void
    {
        parent::setUp();
        $this->subscription_user = factory(User::class)->create();
        $this->payment = factory(Payment::class)->create([
            'status' => PaymentStatus::STATUS_BEFORE_START,
            'external_order_id' => null,
            'type' => null,
            'subscription_id' => null,
        ]);
    }

    /** @test */
    public function saveResponseFalse()
    {
        $subscription = factory(Subscription::class)->create();

        $saver = new ResponseSaver($this->payment, $subscription, $this->subscription_user, null);

        $saver->save(false);

        $payment_new = $this->payment->fresh();

        $this->assertSame($this->payment->status, $payment_new->status);
        $this->assertSame($this->payment->type, $payment_new->type);
        $this->assertSame($this->payment->external_order_id, $payment_new->external_order_id);
        $this->assertSame($this->payment->subscription_id, $payment_new->subscription_id);
    }

    /** @test */
    public function saveResponseNotSuccessWithoutToken()
    {
        $subscription = factory(Subscription::class)->create();

        $response = m::mock(ResponseOrderSimply::class);
        $response->shouldReceive('isSuccess')->andReturn(false);
        $response->shouldReceive('getToken')->andReturn(null);

        $saver = new ResponseSaver($this->payment, $subscription, $this->subscription_user, null);
        $saver->save($response);

        $payment_new = $this->payment->fresh();

        $this->assertSame($this->payment->status, $payment_new->status);
        $this->assertSame($this->payment->type, $payment_new->type);
        $this->assertSame($this->payment->external_order_id, $payment_new->external_order_id);
        $this->assertSame($this->payment->subscription_id, $payment_new->subscription_id);
    }

    /** @test */
    public function saveResponseSuccessSimply()
    {
        $subscription = factory(Subscription::class)->create();

        $response = m::mock(ResponseOrderSimply::class);
        $response->shouldReceive('isSuccess')->andReturn(true);
        $response->shouldReceive('getToken')->andReturn(null);
        $response->shouldReceive('getOrderId')->andReturn(25);

        $saver = new ResponseSaver($this->payment, $subscription, $this->subscription_user, null);
        $saver->save($response);

        $payment_new = $this->payment->fresh();

        $this->assertSame(PaymentStatus::STATUS_NEW, $payment_new->status);
        $this->assertSame($this->payment::TYPE_SIMPLE, $payment_new->type);
        $this->assertEquals(25, $payment_new->external_order_id);
        $this->assertSame($this->payment->subscription_id, $payment_new->subscription_id);
    }

    /** @test */
    public function saveResponseSuccessByCardFirstWithoutSubscription()
    {
        $subscription = factory(Subscription::class)->create();

        $response = m::mock(ResponseOrderByCardFirst::class);
        $response->shouldReceive('isSuccess')->andReturn(true);
        $response->shouldReceive('getToken')->andReturn(null);
        $response->shouldReceive('getOrderId')->andReturn(25);

        $saver = new ResponseSaver($this->payment, $subscription, $this->subscription_user, ['subscription' => false]);
        $saver->save($response);

        $payment_new = $this->payment->fresh();

        $this->assertSame(PaymentStatus::STATUS_NEW, $payment_new->status);
        $this->assertSame($this->payment::TYPE_CARD, $payment_new->type);
        $this->assertEquals(25, $payment_new->external_order_id);
        $this->assertSame($this->payment->subscription_id, $payment_new->subscription_id);
    }

    /** @test */
    public function saveResponseSuccessByCardFirstWithSubscription()
    {
        $subscription = factory(Subscription::class)->create();
        $subscription_count = Subscription::count();

        $moduleMod = factory(ModuleMod::class)->create();

        $history = factory(CompanyModuleHistory::class)->create([
            'package_id' => null,
            'transaction_id' => $this->payment->transaction->id,
            'module_id' => $moduleMod->module_id,
            'module_mod_id' => $moduleMod->id,
        ]);

        $modPrice = factory(ModPrice::class)->create([
            'package_id' => null,
            'module_mod_id' => $moduleMod->id,
        ]);

        $response = m::mock(ResponseOrderByCardFirst::class);
        $response->shouldReceive('isSuccess')->andReturn(false);
        $response->shouldReceive('getToken')->andReturn('asd');
        $response->shouldReceive('getOrderId')->andReturn(25);

        $saver = new ResponseSaver($this->payment, $subscription, $this->subscription_user, ['subscription' => true]);
        $saver->save($response);

        $payment_new = $this->payment->fresh();

        $this->assertSame(PaymentStatus::STATUS_NEW, $payment_new->status);
        $this->assertSame($this->payment::TYPE_CARD, $payment_new->type);
        $this->assertEquals(25, $payment_new->external_order_id);

        $this->assertSame(Subscription::count(), $subscription_count + 1);

        $new_subscription = Subscription::orderBy('id', 'desc')->first();
        $this->assertSame($new_subscription->id, $payment_new->subscription_id);
        $this->assertSame($modPrice->days, $new_subscription->days);
        $this->assertSame(0, $new_subscription->repeats);
        $this->assertSame('asd', decrypt($new_subscription->card_token));
        $this->assertSame($this->subscription_user->id, $new_subscription->user_id);
    }

    /** @test */
    public function saveResponseSuccessByCardNext()
    {
        $subscription = factory(Subscription::class)->create();
        $subscription_count = Subscription::count();

        $moduleMod = factory(ModuleMod::class)->create();

        $history = factory(CompanyModuleHistory::class)->create([
            'package_id' => null,
            'transaction_id' => $this->payment->transaction->id,
            'module_id' => $moduleMod->module_id,
            'module_mod_id' => $moduleMod->id,
        ]);

        $modPrice = factory(ModPrice::class)->create([
            'package_id' => null,
            'module_mod_id' => $moduleMod->id,
        ]);

        $response = m::mock(ResponseOrderByCardNext::class);
        $response->shouldReceive('isSuccess')->andReturn(true);
        $response->shouldReceive('getToken')->andReturn(null);
        $response->shouldReceive('getOrderId')->andReturn(25);

        $saver = new ResponseSaver($this->payment, $subscription, $this->subscription_user, ['subscription' => true, 'token' => 'asd']);
        $saver->save($response);

        $payment_new = $this->payment->fresh();

        $this->assertSame(PaymentStatus::STATUS_NEW, $payment_new->status);
        $this->assertSame($this->payment::TYPE_CARD, $payment_new->type);
        $this->assertEquals(25, $payment_new->external_order_id);
        $this->assertSame($this->payment->subscription_id, $payment_new->subscription_id);

        $this->assertSame(Subscription::count(), $subscription_count + 1);

        $new_subscription = Subscription::orderBy('id', 'desc')->first();
        $this->assertSame($new_subscription->id, $payment_new->subscription_id);
        $this->assertSame($modPrice->days, $new_subscription->days);
        $this->assertSame(0, $new_subscription->repeats);
        $this->assertSame('asd', decrypt($new_subscription->card_token));
        $this->assertSame($this->subscription_user->id, $new_subscription->user_id);
    }
}
