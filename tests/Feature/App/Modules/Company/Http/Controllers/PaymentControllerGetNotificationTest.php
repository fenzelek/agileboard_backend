<?php

namespace  Tests\Feature\App\Modules\Company\Http\Controllers;

use App\Models\Db\CompanyModule;
use App\Models\Db\Module;
use App\Models\Db\Package;
use App\Modules\Company\Notifications\PaymentStatusInfo;
use App\Modules\Company\Services\PayU\PayU;
use App\Notifications\PaymentCompleted;
use Carbon\Carbon;
use Mockery as m;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\ExtendModule;
use Tests\TestCase;
use App\Models\Db\Payment;
use App\Models\Db\Subscription;
use App\Models\Db\CompanyModuleHistory;
use App\Models\Other\PaymentStatus;
use Notification;

class PaymentControllerGetNotificationTest extends TestCase
{
    use DatabaseTransactions, ExtendModule;

    private $company;
    private $payment;

    protected function setUp():void
    {
        parent::setUp();

        $this->createTestExtendModule();
    }

    /** @test */
    public function getNotification_wrongCurrency()
    {
        $response = $this->post('companies/payments/notification/asd');
        $response->assertStatus(404);
    }

    /** @test */
    public function getNotification_errorFromPayu()
    {
        $payu = m::mock(PayU::class);
        $payu->shouldReceive('getDataFromNotification')->andReturn(false);
        $this->app->instance(PayU::class, $payu);

        $response = $this->post('companies/payments/notification/pln');
        $response->assertStatus(200);
    }

    /** @test */
    public function getNotification_paymentNotExist()
    {
        $payu = m::mock(PayU::class);
        $payu->shouldReceive('getDataFromNotification')->andReturn(
            (object) [
                'order_id' => '1',
                'status' => PaymentStatus::STATUS_COMPLETED,
                'data' => [],
        ]
        );
        $this->app->instance(PayU::class, $payu);

        $response = $this->post('companies/payments/notification/pln');
        $response->assertStatus(200);

        $this->assertFalse((bool) Payment::where('status', PaymentStatus::STATUS_COMPLETED)->first());
    }

    /** @test */
    public function getNotification_getStatusRejectedByEur()
    {
        $payment = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_CANCELED, 'external_order_id' => '1']);
        $payment2 = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_CANCELED, 'external_order_id' => '2']);

        $payu = m::mock(PayU::class);
        $payu->shouldReceive('getDataFromNotification')->andReturn(
            (object) [
                'order_id' => '1',
                'status' => PaymentStatus::STATUS_REJECTED,
                'data' => [],
            ]
        );
        $payu->shouldReceive('setOrderCompleted')->andReturn(true);
        $this->app->instance(PayU::class, $payu);

        $response = $this->post('companies/payments/notification/eur');
        $response->assertStatus(200);

        $payment = $payment->fresh();
        $this->assertSame($payment->status, PaymentStatus::STATUS_REJECTED);
        $payment2 = $payment2->fresh();
        $this->assertSame($payment2->status, PaymentStatus::STATUS_CANCELED);
    }

    /** @test */
    public function getNotification_getStatusCanceled()
    {
        Notification::fake();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $payment = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_PENDING, 'external_order_id' => '1']);
        $payment2 = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_PENDING, 'external_order_id' => '2']);

        $history = factory(CompanyModuleHistory::class)->create([
            'package_id' => null,
            'company_id' => $company->id,
            'transaction_id' => $payment->transaction->id,
        ]);

        $payu = m::mock(PayU::class);
        $payu->shouldReceive('getDataFromNotification')->andReturn(
            (object) [
                'order_id' => '1',
                'status' => PaymentStatus::STATUS_CANCELED,
                'data' => [],
            ]
        );
        $this->app->instance(PayU::class, $payu);

        $response = $this->post('companies/payments/notification/pln');
        $response->assertStatus(200);

        $payment = $payment->fresh();
        $this->assertSame($payment->status, PaymentStatus::STATUS_CANCELED);
        $payment2 = $payment2->fresh();
        $this->assertSame($payment2->status, PaymentStatus::STATUS_PENDING);

        Notification::assertSentTo(
            $this->user,
            PaymentStatusInfo::class,
            function ($notification, $channels) use ($payment) {
                return $notification->payment->id === $payment->id;
            }
        );
    }

    /** @test */
    public function getNotification_getStatusCompleted_expirationPayment()
    {
        Notification::fake();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $payment = factory(Payment::class)->create([
            'status' => PaymentStatus::STATUS_PENDING,
            'external_order_id' => '1',
            'expiration_date' => Carbon::now()->subDay(),
            'days' => 30,
        ]);
        $payment2 = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_PENDING, 'external_order_id' => '2']);

        $module = Module::findBySlug('test.extend.module');
        $mod = $module->mods()->where('value', 'test1')->where('test', 0)->first();
        $mod_price = $mod->modPrices()->where('days', 30)->first();

        $history = $this->createHistory($company, $mod, $mod_price, $payment->transaction);

        $payu = m::mock(PayU::class);
        $payu->shouldReceive('getDataFromNotification')->andReturn(
            (object) [
                'order_id' => '1',
                'status' => PaymentStatus::STATUS_COMPLETED,
                'data' => [],
            ]
        );
        $this->app->instance(PayU::class, $payu);

        $response = $this->post('companies/payments/notification/pln');
        $response->assertStatus(200);

        $payment = $payment->fresh();
        $this->assertSame($payment->status, PaymentStatus::STATUS_COMPLETED_BUT_NOT_USED);
        $payment2 = $payment2->fresh();
        $this->assertSame($payment2->status, PaymentStatus::STATUS_PENDING);

        Notification::assertSentTo(
            $this->user,
            PaymentStatusInfo::class,
            function ($notification, $channels) use ($payment) {
                return $notification->payment->id === $payment->id;
            }
        );

        Notification::assertSentTo(
            $this->user,
            PaymentCompleted::class,
            function ($notification, $channels) use ($payment) {
                return $notification->payment->id === $payment->id;
            }
        );

        $company_module = CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->first();
        $this->assertSame('', $company_module->value);
        $this->assertSame(null, $company_module->expiration_date);

        $history_new = $history->fresh();
        $this->assertSame($company->id, $history_new->company_id);
        $this->assertSame($module->id, $history_new->module_id);
        $this->assertSame($mod->id, $history_new->module_mod_id);
        $this->assertSame(null, $history_new->old_value);
        $this->assertSame($mod->value, $history_new->new_value);
        $this->assertSame(null, $history_new->start_date);
        $this->assertSame(null, $history_new->expiration_date);
        $this->assertSame(CompanyModuleHistory::STATUS_NOT_USED, $history_new->status);
        $this->assertSame(null, $history_new->package_id);
        $this->assertSame($mod_price->price, $history_new->price);
        $this->assertSame($mod_price->currency, $history_new->currency);
        $this->assertSame($mod_price->vat, $history_new->vat);
        $this->assertSame($payment->transaction->id, $history_new->transaction_id);
    }

    /** @test */
    public function getNotification_getStatusCompleted_buyfirstExtendModule()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        Notification::fake();

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM, Carbon::now()->addDays(365));

        $payment = factory(Payment::class)->create([
            'status' => PaymentStatus::STATUS_PENDING,
            'external_order_id' => '1',
            'days' => 30,
        ]);
        $payment2 = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_PENDING, 'external_order_id' => '2']);

        $module = Module::findBySlug('test.extend.module');
        $mod = $module->mods()->where('value', 'test1')->where('test', 0)->first();
        $mod_price = $mod->modPrices()->where('days', 30)->first();

        $history = $this->createHistory($company, $mod, $mod_price, $payment->transaction);

        $payu = m::mock(PayU::class);
        $payu->shouldReceive('getDataFromNotification')->andReturn(
            (object) [
                'order_id' => '1',
                'status' => PaymentStatus::STATUS_COMPLETED,
                'data' => [],
            ]
        );
        $this->app->instance(PayU::class, $payu);

        $count_companyModules = CompanyModule::count();
        $count_companyModulesHistory = CompanyModuleHistory::count();

        $response = $this->post('companies/payments/notification/pln');
        $response->assertStatus(200);

        $payment = $payment->fresh();
        $this->assertSame($payment->status, PaymentStatus::STATUS_COMPLETED);
        $payment2 = $payment2->fresh();
        $this->assertSame($payment2->status, PaymentStatus::STATUS_PENDING);

        Notification::assertSentTo(
            $this->user,
            PaymentStatusInfo::class,
            function ($notification, $channels) use ($payment) {
                return $notification->payment->id === $payment->id;
            }
        );

        Notification::assertSentTo(
            $this->user,
            PaymentCompleted::class,
            function ($notification, $channels) use ($payment) {
                return $notification->payment->id === $payment->id;
            }
        );

        $this->assertSame($count_companyModules, CompanyModule::count());
        $this->assertSame($count_companyModulesHistory, CompanyModuleHistory::count());

        $expiration_date = Carbon::now()->addDays(30)->toDateTimeString();

        $company_module = CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->first();
        $this->assertSame($mod->value, $company_module->value);
        $this->assertSame($expiration_date, $company_module->expiration_date->toDateTimeString());

        $history_new = $history->fresh();
        $this->assertSame($company->id, $history_new->company_id);
        $this->assertSame($module->id, $history_new->module_id);
        $this->assertSame($mod->id, $history_new->module_mod_id);
        $this->assertSame(null, $history_new->old_value);
        $this->assertSame($mod->value, $history_new->new_value);
        $this->assertSame($now->toDateTimeString(), $history_new->start_date->toDateTimeString());
        $this->assertSame($expiration_date, $history_new->expiration_date->toDateTimeString());
        $this->assertSame(CompanyModuleHistory::STATUS_USED, $history_new->status);
        $this->assertSame(null, $history_new->package_id);
        $this->assertSame($mod_price->price, $history_new->price);
        $this->assertSame($mod_price->currency, $history_new->currency);
        $this->assertSame($mod_price->vat, $history_new->vat);
        $this->assertSame($payment->transaction->id, $history_new->transaction_id);
    }

    /** @test */
    public function getNotification_getStatusCompletedWithSubscription()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        Notification::fake();

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM, Carbon::now()->addDays(365));

        $subscription = factory(Subscription::class)->create(['repeats' => 12]);
        $payment = factory(Payment::class)->create([
            'status' => PaymentStatus::STATUS_PENDING,
            'external_order_id' => '1',
            'subscription_id' => $subscription->id,
            'days' => 30,
        ]);
        $payment2 = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_PENDING, 'external_order_id' => '2']);

        $module = Module::findBySlug('test.extend.module');
        $mod = $module->mods()->where('value', 'test1')->where('test', 0)->first();
        $mod_price = $mod->modPrices()->where('days', 30)->first();

        $history = $this->createHistory($company, $mod, $mod_price, $payment->transaction);
        CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)
            ->update(['value' => 'test1', 'expiration_date' => Carbon::now()->subHour()]);

        $payu = m::mock(PayU::class);
        $payu->shouldReceive('getDataFromNotification')->andReturn(
            (object) [
                'order_id' => '1',
                'status' => PaymentStatus::STATUS_COMPLETED,
                'data' => [],
            ]
        );
        $this->app->instance(PayU::class, $payu);

        $count_companyModules = CompanyModule::count();
        $count_companyModulesHistory = CompanyModuleHistory::count();

        $response = $this->post('companies/payments/notification/pln');
        $response->assertStatus(200);

        $payment = $payment->fresh();
        $this->assertSame($payment->status, PaymentStatus::STATUS_COMPLETED);
        $payment2 = $payment2->fresh();
        $this->assertSame($payment2->status, PaymentStatus::STATUS_PENDING);
        $subscription = $subscription->fresh();
        $this->assertSame($subscription->repeats, 0);

        Notification::assertSentTo(
            $this->user,
            PaymentStatusInfo::class,
            function ($notification, $channels) use ($payment) {
                return $notification->payment->id === $payment->id;
            }
        );

        Notification::assertSentTo(
            $this->user,
            PaymentCompleted::class,
            function ($notification, $channels) use ($payment) {
                return $notification->payment->id === $payment->id;
            }
        );

        $this->assertSame($count_companyModules, CompanyModule::count());
        $this->assertSame($count_companyModulesHistory, CompanyModuleHistory::count());

        $expiration_date = Carbon::now()->addDays(30)->toDateTimeString();

        $company_module = CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->first();
        $this->assertSame($mod->value, $company_module->value);
        $this->assertSame($expiration_date, $company_module->expiration_date->toDateTimeString());

        $history_new = $history->fresh();
        $this->assertSame($company->id, $history_new->company_id);
        $this->assertSame($module->id, $history_new->module_id);
        $this->assertSame($mod->id, $history_new->module_mod_id);
        $this->assertSame(null, $history_new->old_value);
        $this->assertSame($mod->value, $history_new->new_value);
        $this->assertSame($now->toDateTimeString(), $history_new->start_date->toDateTimeString());
        $this->assertSame($expiration_date, $history_new->expiration_date->toDateTimeString());
        $this->assertSame(CompanyModuleHistory::STATUS_USED, $history_new->status);
        $this->assertSame(null, $history_new->package_id);
        $this->assertSame($mod_price->price, $history_new->price);
        $this->assertSame($mod_price->currency, $history_new->currency);
        $this->assertSame($mod_price->vat, $history_new->vat);
        $this->assertSame($payment->transaction->id, $history_new->transaction_id);
    }

    /** @test */
    public function getNotification_getStatusCompleted_buyExtendModuleWhenPackageExpiresEarlier()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        Notification::fake();

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM, Carbon::now()->addDays(20));

        $payment = factory(Payment::class)->create([
            'status' => PaymentStatus::STATUS_PENDING,
            'external_order_id' => '1',
            'days' => 30,
        ]);
        $payment2 = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_PENDING, 'external_order_id' => '2']);

        $module = Module::findBySlug('test.extend.module');
        $mod = $module->mods()->where('value', 'test1')->where('test', 0)->first();
        $mod_price = $mod->modPrices()->where('days', 30)->first();

        $history = $this->createHistory($company, $mod, $mod_price, $payment->transaction);

        $payu = m::mock(PayU::class);
        $payu->shouldReceive('getDataFromNotification')->andReturn(
            (object) [
                'order_id' => '1',
                'status' => PaymentStatus::STATUS_COMPLETED,
                'data' => [],
            ]
        );
        $this->app->instance(PayU::class, $payu);

        $count_companyModules = CompanyModule::count();
        $count_companyModulesHistory = CompanyModuleHistory::count();

        $response = $this->post('companies/payments/notification/pln');
        $response->assertStatus(200);

        $payment = $payment->fresh();
        $this->assertSame($payment->status, PaymentStatus::STATUS_COMPLETED);
        $payment2 = $payment2->fresh();
        $this->assertSame($payment2->status, PaymentStatus::STATUS_PENDING);

        Notification::assertSentTo(
            $this->user,
            PaymentStatusInfo::class,
            function ($notification, $channels) use ($payment) {
                return $notification->payment->id === $payment->id;
            }
        );

        Notification::assertSentTo(
            $this->user,
            PaymentCompleted::class,
            function ($notification, $channels) use ($payment) {
                return $notification->payment->id === $payment->id;
            }
        );

        $this->assertSame($count_companyModules, CompanyModule::count());
        $this->assertSame($count_companyModulesHistory, CompanyModuleHistory::count());

        $expiration_date = Carbon::now()->addDays(20)->toDateTimeString();

        $company_module = CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->first();
        $this->assertSame($mod->value, $company_module->value);
        $this->assertSame($expiration_date, $company_module->expiration_date->toDateTimeString());

        $history_new = $history->fresh();
        $this->assertSame($company->id, $history_new->company_id);
        $this->assertSame($module->id, $history_new->module_id);
        $this->assertSame($mod->id, $history_new->module_mod_id);
        $this->assertSame(null, $history_new->old_value);
        $this->assertSame($mod->value, $history_new->new_value);
        $this->assertSame($now->toDateTimeString(), $history_new->start_date->toDateTimeString());
        $this->assertSame($expiration_date, $history_new->expiration_date->toDateTimeString());
        $this->assertSame(CompanyModuleHistory::STATUS_USED, $history_new->status);
        $this->assertSame(null, $history_new->package_id);
        $this->assertSame($mod_price->price, $history_new->price);
        $this->assertSame($mod_price->currency, $history_new->currency);
        $this->assertSame($mod_price->vat, $history_new->vat);
        $this->assertSame($payment->transaction->id, $history_new->transaction_id);
    }

    /** @test */
    public function getNotification_getStatusCompleted_renewExtendModuleSame()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        Notification::fake();

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM, Carbon::now()->addDays(365));

        $payment = factory(Payment::class)->create([
            'status' => PaymentStatus::STATUS_PENDING,
            'external_order_id' => '1',
            'days' => 30,
        ]);
        $payment2 = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_PENDING, 'external_order_id' => '2']);

        $module = Module::findBySlug('test.extend.module');
        $mod = $module->mods()->where('value', 'test1')->where('test', 0)->first();
        $mod_price = $mod->modPrices()->where('days', 30)->first();

        $start_date = Carbon::now()->addDays(2);

        $history = $this->createHistory($company, $mod, $mod_price, $payment->transaction, null, $start_date);
        CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)
            ->update(['value' => 'test1', 'expiration_date' => $start_date]);

        $payu = m::mock(PayU::class);
        $payu->shouldReceive('getDataFromNotification')->andReturn(
            (object) [
                'order_id' => '1',
                'status' => PaymentStatus::STATUS_COMPLETED,
                'data' => [],
            ]
        );
        $this->app->instance(PayU::class, $payu);

        $count_companyModules = CompanyModule::count();
        $count_companyModulesHistory = CompanyModuleHistory::count();

        $response = $this->post('companies/payments/notification/pln');
        $response->assertStatus(200);

        $payment = $payment->fresh();
        $this->assertSame($payment->status, PaymentStatus::STATUS_COMPLETED);
        $payment2 = $payment2->fresh();
        $this->assertSame($payment2->status, PaymentStatus::STATUS_PENDING);

        Notification::assertSentTo(
            $this->user,
            PaymentStatusInfo::class,
            function ($notification, $channels) use ($payment) {
                return $notification->payment->id === $payment->id;
            }
        );

        Notification::assertSentTo(
            $this->user,
            PaymentCompleted::class,
            function ($notification, $channels) use ($payment) {
                return $notification->payment->id === $payment->id;
            }
        );

        $this->assertSame($count_companyModules, CompanyModule::count());
        $this->assertSame($count_companyModulesHistory, CompanyModuleHistory::count());

        $expiration_date = Carbon::now()->addDays(32)->toDateTimeString();

        $company_module = CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->first();
        $this->assertSame($mod->value, $company_module->value);
        $this->assertSame($expiration_date, $company_module->expiration_date->toDateTimeString());

        $history_new = $history->fresh();
        $this->assertSame($company->id, $history_new->company_id);
        $this->assertSame($module->id, $history_new->module_id);
        $this->assertSame($mod->id, $history_new->module_mod_id);
        $this->assertSame(null, $history_new->old_value);
        $this->assertSame($mod->value, $history_new->new_value);
        $this->assertSame($start_date->toDateTimeString(), $history_new->start_date->toDateTimeString());
        $this->assertSame($expiration_date, $history_new->expiration_date->toDateTimeString());
        $this->assertSame(CompanyModuleHistory::STATUS_USED, $history_new->status);
        $this->assertSame(null, $history_new->package_id);
        $this->assertSame($mod_price->price, $history_new->price);
        $this->assertSame($mod_price->currency, $history_new->currency);
        $this->assertSame($mod_price->vat, $history_new->vat);
        $this->assertSame($payment->transaction->id, $history_new->transaction_id);
    }

    /** @test */
    public function getNotification_getStatusCompleted_renewExtendModuleOther()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        Notification::fake();

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM, Carbon::now()->addDays(365));

        $payment = factory(Payment::class)->create([
            'status' => PaymentStatus::STATUS_PENDING,
            'external_order_id' => '1',
            'days' => 30,
        ]);
        $payment2 = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_PENDING, 'external_order_id' => '2']);

        $module = Module::findBySlug('test.extend.module');
        $mod = $module->mods()->where('value', 'test2')->where('test', 0)->first();
        $mod_price = $mod->modPrices()->where('days', 30)->first();

        $start_date = Carbon::now()->addDays(2);

        $history = $this->createHistory($company, $mod, $mod_price, $payment->transaction, null, $start_date);
        CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)
            ->update(['value' => 'test1', 'expiration_date' => $start_date]);

        $payu = m::mock(PayU::class);
        $payu->shouldReceive('getDataFromNotification')->andReturn(
            (object) [
                'order_id' => '1',
                'status' => PaymentStatus::STATUS_COMPLETED,
                'data' => [],
            ]
        );
        $this->app->instance(PayU::class, $payu);

        $count_companyModules = CompanyModule::count();
        $count_companyModulesHistory = CompanyModuleHistory::count();

        $response = $this->post('companies/payments/notification/pln');
        $response->assertStatus(200);

        $payment = $payment->fresh();
        $this->assertSame($payment->status, PaymentStatus::STATUS_COMPLETED);
        $payment2 = $payment2->fresh();
        $this->assertSame($payment2->status, PaymentStatus::STATUS_PENDING);

        Notification::assertSentTo(
            $this->user,
            PaymentStatusInfo::class,
            function ($notification, $channels) use ($payment) {
                return $notification->payment->id === $payment->id;
            }
        );

        Notification::assertSentTo(
            $this->user,
            PaymentCompleted::class,
            function ($notification, $channels) use ($payment) {
                return $notification->payment->id === $payment->id;
            }
        );

        $this->assertSame($count_companyModules, CompanyModule::count());
        $this->assertSame($count_companyModulesHistory, CompanyModuleHistory::count());

        $expiration_date = Carbon::now()->addDays(32)->toDateTimeString();

        $company_module = CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->first();
        $this->assertSame('test1', $company_module->value);
        $this->assertSame($start_date->toDateTimeString(), $company_module->expiration_date->toDateTimeString());

        $history_new = $history->fresh();
        $this->assertSame($company->id, $history_new->company_id);
        $this->assertSame($module->id, $history_new->module_id);
        $this->assertSame($mod->id, $history_new->module_mod_id);
        $this->assertSame(null, $history_new->old_value);
        $this->assertSame($mod->value, $history_new->new_value);
        $this->assertSame($start_date->toDateTimeString(), $history_new->start_date->toDateTimeString());
        $this->assertSame($expiration_date, $history_new->expiration_date->toDateTimeString());
        $this->assertSame(CompanyModuleHistory::STATUS_NOT_USED, $history_new->status);
        $this->assertSame(null, $history_new->package_id);
        $this->assertSame($mod_price->price, $history_new->price);
        $this->assertSame($mod_price->currency, $history_new->currency);
        $this->assertSame($mod_price->vat, $history_new->vat);
        $this->assertSame($payment->transaction->id, $history_new->transaction_id);
    }

    /** @test */
    public function getNotification_getStatusCompleted_changeExtendModule()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        Notification::fake();

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM, Carbon::now()->addDays(365));

        $expiration_date = Carbon::now()->addDays(15)->toDateTimeString();

        $payment = factory(Payment::class)->create([
            'status' => PaymentStatus::STATUS_PENDING,
            'external_order_id' => '1',
            'days' => 30,
            'expiration_date' => $expiration_date,
        ]);
        $payment2 = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_PENDING, 'external_order_id' => '2']);

        $module = Module::findBySlug('test.extend.module');
        $mod = $module->mods()->where('value', 'test1')->where('test', 0)->first();
        $mod_price = $mod->modPrices()->where('days', 30)->first();

        $history = $this->createHistory($company, $mod, $mod_price, $payment->transaction, null);
        CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)
            ->update(['value' => 'test2', 'expiration_date' => $expiration_date]);

        $payu = m::mock(PayU::class);
        $payu->shouldReceive('getDataFromNotification')->andReturn(
            (object) [
                'order_id' => '1',
                'status' => PaymentStatus::STATUS_COMPLETED,
                'data' => [],
            ]
        );
        $this->app->instance(PayU::class, $payu);

        $count_companyModules = CompanyModule::count();
        $count_companyModulesHistory = CompanyModuleHistory::count();

        $response = $this->post('companies/payments/notification/pln');
        $response->assertStatus(200);

        $payment = $payment->fresh();
        $this->assertSame($payment->status, PaymentStatus::STATUS_COMPLETED);
        $payment2 = $payment2->fresh();
        $this->assertSame($payment2->status, PaymentStatus::STATUS_PENDING);

        Notification::assertSentTo(
            $this->user,
            PaymentStatusInfo::class,
            function ($notification, $channels) use ($payment) {
                return $notification->payment->id === $payment->id;
            }
        );

        Notification::assertSentTo(
            $this->user,
            PaymentCompleted::class,
            function ($notification, $channels) use ($payment) {
                return $notification->payment->id === $payment->id;
            }
        );

        $this->assertSame($count_companyModules, CompanyModule::count());
        $this->assertSame($count_companyModulesHistory, CompanyModuleHistory::count());

        $company_module = CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->first();
        $this->assertSame($mod->value, $company_module->value);
        $this->assertSame($expiration_date, $company_module->expiration_date->toDateTimeString());

        $history_new = $history->fresh();
        $this->assertSame($company->id, $history_new->company_id);
        $this->assertSame($module->id, $history_new->module_id);
        $this->assertSame($mod->id, $history_new->module_mod_id);
        $this->assertSame(null, $history_new->old_value);
        $this->assertSame($mod->value, $history_new->new_value);
        $this->assertSame(Carbon::now()->toDateTimeString(), $history_new->start_date->toDateTimeString());
        $this->assertSame($expiration_date, $history_new->expiration_date->toDateTimeString());
        $this->assertSame(CompanyModuleHistory::STATUS_USED, $history_new->status);
        $this->assertSame(null, $history_new->package_id);
        $this->assertSame($mod_price->price, $history_new->price);
        $this->assertSame($mod_price->currency, $history_new->currency);
        $this->assertSame($mod_price->vat, $history_new->vat);
        $this->assertSame($payment->transaction->id, $history_new->transaction_id);
    }

    /** @test */
    public function getNotification_getStatusCompleted_changeModuleFromPackage()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        Notification::fake();

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM, Carbon::now()->addDays(365));

        $package_premium = Package::findBySlug(Package::PREMIUM);

        $expiration_date = Carbon::now()->addDays(365)->toDateTimeString();

        $payment = factory(Payment::class)->create([
            'status' => PaymentStatus::STATUS_PENDING,
            'external_order_id' => '1',
            'days' => 0,
            'expiration_date' => $expiration_date,
        ]);
        $payment2 = factory(Payment::class)->create(['status' => PaymentStatus::STATUS_PENDING, 'external_order_id' => '2']);

        $module = Module::findBySlug('invoices.registry.export.name');
        $mod = $module->mods()->where('value', 'optima')->where('test', 0)->first();
        $mod_price = $mod->modPrices()->where('days', 365)->first();

        $history = $this->createHistory($company, $mod, $mod_price, $payment->transaction, $package_premium->id);

        $payu = m::mock(PayU::class);
        $payu->shouldReceive('getDataFromNotification')->andReturn(
            (object) [
                'order_id' => '1',
                'status' => PaymentStatus::STATUS_COMPLETED,
                'data' => [],
            ]
        );
        $this->app->instance(PayU::class, $payu);

        $count_companyModules = CompanyModule::count();
        $count_companyModulesHistory = CompanyModuleHistory::count();

        $response = $this->post('companies/payments/notification/pln');
        $response->assertStatus(200);

        $payment = $payment->fresh();
        $this->assertSame($payment->status, PaymentStatus::STATUS_COMPLETED);
        $payment2 = $payment2->fresh();
        $this->assertSame($payment2->status, PaymentStatus::STATUS_PENDING);

        Notification::assertSentTo(
            $this->user,
            PaymentStatusInfo::class,
            function ($notification, $channels) use ($payment) {
                return $notification->payment->id === $payment->id;
            }
        );

        Notification::assertSentTo(
            $this->user,
            PaymentCompleted::class,
            function ($notification, $channels) use ($payment) {
                return $notification->payment->id === $payment->id;
            }
        );

        $this->assertSame($count_companyModules, CompanyModule::count());
        $this->assertSame($count_companyModulesHistory, CompanyModuleHistory::count());

        $company_module = CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->first();
        $this->assertSame($mod->value, $company_module->value);
        $this->assertSame($expiration_date, $company_module->expiration_date->toDateTimeString());

        $history_new = $history->fresh();
        $this->assertSame($company->id, $history_new->company_id);
        $this->assertSame($module->id, $history_new->module_id);
        $this->assertSame($mod->id, $history_new->module_mod_id);
        $this->assertSame(null, $history_new->old_value);
        $this->assertSame($mod->value, $history_new->new_value);
        $this->assertSame(Carbon::now()->toDateTimeString(), $history_new->start_date->toDateTimeString());
        $this->assertSame($expiration_date, $history_new->expiration_date->toDateTimeString());
        $this->assertSame(CompanyModuleHistory::STATUS_USED, $history_new->status);
        $this->assertSame($package_premium->id, $history_new->package_id);
        $this->assertSame($mod_price->price, $history_new->price);
        $this->assertSame($mod_price->currency, $history_new->currency);
        $this->assertSame($mod_price->vat, $history_new->vat);
        $this->assertSame($payment->transaction->id, $history_new->transaction_id);
    }

    /** @test */
    public function getNotification_getStatusCompleted_buyFirstPackage_with_subscription()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        Notification::fake();

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::START);

        $subscription = factory(Subscription::class)->create(['active' => 0]);

        $payment = factory(Payment::class)->create([
            'status' => PaymentStatus::STATUS_PENDING,
            'external_order_id' => '1',
            'days' => 30,
            'subscription_id' => $subscription->id,
        ]);

        $premium_package = Package::findBySlug(Package::PREMIUM);
        $premium_modules = $premium_package->modules()
            ->with(['mods' => function ($q) use ($premium_package) {
                $q->whereHas('modPrices', function ($q) use ($premium_package) {
                    $q->where('package_id', $premium_package->id);
                    $q->where('days', 30);
                });
                $q->with(['modPrices' => function ($q) use ($premium_package) {
                    $q->where('package_id', $premium_package->id);
                    $q->where('days', 30);
                }]);
            }])
            ->get();

        $history_modules = [];
        foreach ($premium_modules as $module) {
            $history_modules [] = $this->createHistory(
                $company,
                $module->mods[0],
                $module->mods[0]->modPrices[0],
                $payment->transaction,
                $premium_package->id
            );
        }

        $payu = m::mock(PayU::class);
        $payu->shouldReceive('getDataFromNotification')->andReturn(
            (object) [
                'order_id' => '1',
                'status' => PaymentStatus::STATUS_COMPLETED,
                'data' => [],
            ]
        );
        $this->app->instance(PayU::class, $payu);

        $count_companyModules = CompanyModule::count();
        $count_companyModulesHistory = CompanyModuleHistory::count();

        $response = $this->post('companies/payments/notification/pln');
        $response->assertStatus(200);

        $payment = $payment->fresh();
        $this->assertSame($payment->status, PaymentStatus::STATUS_COMPLETED);

        $subscription = $subscription->fresh();
        $this->assertSame(1, $subscription->active);

        Notification::assertSentTo(
            $this->user,
            PaymentStatusInfo::class,
            function ($notification, $channels) use ($payment) {
                return $notification->payment->id === $payment->id;
            }
        );

        Notification::assertSentTo(
            $this->user,
            PaymentCompleted::class,
            function ($notification, $channels) use ($payment) {
                return $notification->payment->id === $payment->id;
            }
        );

        $this->assertSame($count_companyModules, CompanyModule::count());
        $this->assertSame($count_companyModulesHistory, CompanyModuleHistory::count());

        $expiration_date = Carbon::now()->addDays(30)->toDateTimeString();

        foreach ($history_modules as $history_module) {
            $company_module = CompanyModule::where('company_id', $company->id)->where('module_id', $history_module->module_id)->first();

            $module = $premium_modules->where('id', $history_module->module_id)->first();

            $this->assertSame($module->mods[0]->value, $company_module->value);
            $this->assertSame($expiration_date, $company_module->expiration_date->toDateTimeString());

            $history_new = CompanyModuleHistory::findOrFail($history_module->id);
            $this->assertSame($company->id, $history_new->company_id);
            $this->assertSame($module->id, $history_new->module_id);
            $this->assertSame($module->mods[0]->id, $history_new->module_mod_id);
            $this->assertSame(null, $history_new->old_value);
            $this->assertSame($module->mods[0]->value, $history_new->new_value);
            $this->assertSame($now->toDateTimeString(), $history_new->start_date->toDateTimeString());
            $this->assertSame($expiration_date, $history_new->expiration_date->toDateTimeString());
            $this->assertSame(CompanyModuleHistory::STATUS_USED, $history_new->status);
            $this->assertSame($premium_package->id, $history_new->package_id);
            $this->assertSame($module->mods[0]->modPrices[0]->price, $history_new->price);
            $this->assertSame($module->mods[0]->modPrices[0]->currency, $history_new->currency);
            $this->assertSame($module->mods[0]->modPrices[0]->vat, $history_new->vat);
            $this->assertSame($payment->transaction->id, $history_new->transaction_id);
        }
    }

    /** @test */
    public function getNotification_getStatusCompleted_renewPackageSame()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        Notification::fake();

        $start_date = Carbon::now()->addDays(2);

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM, $start_date);

        $payment = factory(Payment::class)->create([
            'status' => PaymentStatus::STATUS_PENDING,
            'external_order_id' => '1',
            'days' => 30,
        ]);

        $premium_package = Package::findBySlug(Package::PREMIUM);
        $premium_modules = $premium_package->modules()
            ->with(['mods' => function ($q) use ($premium_package) {
                $q->whereHas('modPrices', function ($q) use ($premium_package) {
                    $q->where('package_id', $premium_package->id);
                    $q->where('days', 30);
                });
                $q->with(['modPrices' => function ($q) use ($premium_package) {
                    $q->where('package_id', $premium_package->id);
                    $q->where('days', 30);
                }]);
            }])
            ->get();

        $history_modules = [];
        foreach ($premium_modules as $module) {
            $history_modules [] = $this->createHistory(
                $company,
                $module->mods[0],
                $module->mods[0]->modPrices[0],
                $payment->transaction,
                $premium_package->id,
                $start_date->toDateTimeString()
            );
        }

        $payu = m::mock(PayU::class);
        $payu->shouldReceive('getDataFromNotification')->andReturn(
            (object) [
                'order_id' => '1',
                'status' => PaymentStatus::STATUS_COMPLETED,
                'data' => [],
            ]
        );
        $this->app->instance(PayU::class, $payu);

        $count_companyModules = CompanyModule::count();
        $count_companyModulesHistory = CompanyModuleHistory::count();

        $response = $this->post('companies/payments/notification/pln');
        $response->assertStatus(200);

        $payment = $payment->fresh();
        $this->assertSame($payment->status, PaymentStatus::STATUS_COMPLETED);

        Notification::assertSentTo(
            $this->user,
            PaymentStatusInfo::class,
            function ($notification, $channels) use ($payment) {
                return $notification->payment->id === $payment->id;
            }
        );

        Notification::assertSentTo(
            $this->user,
            PaymentCompleted::class,
            function ($notification, $channels) use ($payment) {
                return $notification->payment->id === $payment->id;
            }
        );

        $this->assertSame($count_companyModules, CompanyModule::count());
        $this->assertSame($count_companyModulesHistory, CompanyModuleHistory::count());

        $expiration_date = Carbon::now()->addDays(32)->toDateTimeString();

        foreach ($history_modules as $history_module) {
            $company_module = CompanyModule::where('company_id', $company->id)->where('module_id', $history_module->module_id)->first();

            $module = $premium_modules->where('id', $history_module->module_id)->first();

            $this->assertSame($module->mods[0]->value, $company_module->value);
            $this->assertSame($expiration_date, $company_module->expiration_date->toDateTimeString());

            $history_new = CompanyModuleHistory::findOrFail($history_module->id);
            $this->assertSame($company->id, $history_new->company_id);
            $this->assertSame($module->id, $history_new->module_id);
            $this->assertSame($module->mods[0]->id, $history_new->module_mod_id);
            $this->assertSame(null, $history_new->old_value);
            $this->assertSame($module->mods[0]->value, $history_new->new_value);
            $this->assertSame($start_date->toDateTimeString(), $history_new->start_date->toDateTimeString());
            $this->assertSame($expiration_date, $history_new->expiration_date->toDateTimeString());
            $this->assertSame(CompanyModuleHistory::STATUS_USED, $history_new->status);
            $this->assertSame($premium_package->id, $history_new->package_id);
            $this->assertSame($module->mods[0]->modPrices[0]->price, $history_new->price);
            $this->assertSame($module->mods[0]->modPrices[0]->currency, $history_new->currency);
            $this->assertSame($module->mods[0]->modPrices[0]->vat, $history_new->vat);
            $this->assertSame($payment->transaction->id, $history_new->transaction_id);
        }
    }

    //@todo add test for change package, and renew package other

    private function createHistory(
        $company,
        $module_mod,
        $mod_price,
        $transaction,
        $package_id = null,
        $start_date = null,
        $expiration_date = null,
        $status = CompanyModuleHistory::STATUS_NOT_USED
    ) {
        return factory(CompanyModuleHistory::class)->create([
            'company_id' => $company->id,
            'module_id' => $module_mod->module_id,
            'module_mod_id' => $module_mod->id,
            'new_value' => $module_mod->value,
            'package_id' => $package_id,
            'start_date' => $start_date,
            'expiration_date' => $expiration_date,
            'status' => $status,
            'transaction_id' => $transaction->id,
            'price' => $mod_price->price,
            'currency' => $mod_price->currency,
        ]);
    }
}
