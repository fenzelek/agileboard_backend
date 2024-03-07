<?php

namespace Tests\Feature\App\Modules\Company\Jobs;

use App\Models\Db\Module;
use App\Models\Db\Package;
use App\Models\Db\Transaction;
use App\Models\Other\RoleType;
use App\Modules\Company\Jobs\RenewSubscription;
use App\Modules\Company\Notifications\SubscriptionCanceled;
use App\Modules\Company\Services\CompanyModuleUpdater;
use Notification;
use App\Models\Db\CompanyModuleHistory;
use App\Models\Db\Payment;
use App\Models\Other\PaymentStatus;
use App\Modules\Company\Services\PaymentNotificationsService;
use App\Modules\Company\Services\PayU\PayU;
use Mockery as m;
use App\Models\Db\CompanyModule;
use App\Models\Db\Subscription;
use App\Modules\Company\Services\PaymentService;
use App\Modules\Company\Services\PayU\ParamsFactory;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\ExtendModule;
use Tests\TestCase;

class RenewSubscriptionTest extends TestCase
{
    use DatabaseTransactions, ExtendModule;

    private $payU;
    private $job;

    protected function setUp():void
    {
        parent::setUp();

        $this->createTestExtendModule();

        $this->payU = m::mock(PayU::class);
        $this->payU->shouldReceive('setParams');
        $this->payU->shouldReceive('setUser');
        $this->payU->shouldReceive('createOrder')->andReturn(false);

        $this->app->instance(PayU::class, $this->payU);

        $this->job = new RenewSubscription(new PaymentService(), new PaymentNotificationsService(), new ParamsFactory(), new CompanyModuleUpdater());
    }

    /** @test */
    public function handle_lastRepeat_cancelAllSubscriptions()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        Notification::fake();

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM, Carbon::now()->subDay());

        $subscription_package = factory(Subscription::class)->create([
            'repeats' => 14,
            'user_id' => $this->user->id,
            'days' => 30,
        ]);

        $payment = factory(Payment::class)->create([
            'subscription_id' => $subscription_package->id,
            'days' => $subscription_package->days,
            'status' => PaymentStatus::STATUS_COMPLETED,
        ]);

        CompanyModule::where('company_id', $company->id)->whereNotNull('package_id')->update([
            'subscription_id' => $subscription_package->id,
        ]);

        CompanyModuleHistory::where('company_id', $company->id)->whereNotNull('package_id')->update([
            'transaction_id' => $payment->transaction->id,
        ]);

        $module_extend = Module::findBySlug('test.extend.module');
        $mod = $module_extend->mods()->where('value', '')->first();

        $subscription_extend = factory(Subscription::class)->create(['repeats' => 14]);
        $companyModule_extend = CompanyModule::where('company_id', $company->id)->where('module_id', $module_extend->id)->first();
        $companyModule_extend->update([
            'expiration_date' => Carbon::now()->subDay(),
            'subscription_id' => $subscription_extend->id,
            'value' => 'test1',
        ]);

        $count_payments = Payment::count();
        $count_transactions = Transaction::count();
        $count_companyModules = CompanyModule::count();
        $count_companyModulesHistory = CompanyModuleHistory::count();
        $count_package_modules = CompanyModule::where('company_id', $company->id)->whereNotNull('package_id')->count();

        $this->job->handle();

        $subscription_package = $subscription_package->fresh();
        $subscription_extend = $subscription_extend->fresh();

        $this->assertSame(0, $subscription_package->active);
        $this->assertSame(0, $subscription_extend->active);

        Notification::assertSentTo($this->user, SubscriptionCanceled::class);

        $this->assertSame($count_payments, Payment::count());
        $this->assertSame($count_transactions, Transaction::count() - 2);
        $this->assertSame($count_companyModules, CompanyModule::count());
        $this->assertSame($count_companyModulesHistory, CompanyModuleHistory::count() - $count_package_modules - 1);

        $package_free = Package::findDefault();

        foreach (CompanyModule::where('company_id', $company->id)->whereNotNull('package_id')->get() as $company_module) {
            $module = $package_free->modules()->where('id', $company_module->module_id)->with(['mods' => function ($q) use ($package_free) {
                $q->whereHas('modPrices', function ($q) use ($package_free) {
                    $q->default('PLN');
                    $q->where('package_id', $package_free->id);
                });
                $q->with(['modPrices' => function ($q) use ($package_free) {
                    $q->default('PLN');
                    $q->where('package_id', $package_free->id);
                }]);
            }])->first();

            $this->assertTrue((bool) $module);
            $this->assertSame($company->id, $company_module->company_id);
            $this->assertSame($module->mods[0]->value, $company_module->value);
            $this->assertSame($module->package_id, $company_module->laravel_through_key);
            $this->assertSame(null, $company_module->expiration_date);
            $this->assertSame(null, $company_module->subscription_id);
        }

        $transaction_id = CompanyModuleHistory::whereNotNull('package_id')->where('company_id', $company->id)
            ->orderByDesc('id')->first()->transaction_id;

        foreach (CompanyModuleHistory::where('transaction_id', $transaction_id)->get() as $history) {
            $module = $package_free->modules()->where('id', $history->module_id)->with(['mods' => function ($q) use ($package_free) {
                $q->whereHas('modPrices', function ($q) use ($package_free) {
                    $q->default('PLN');
                    $q->where('package_id', $package_free->id);
                });
                $q->with(['modPrices' => function ($q) use ($package_free) {
                    $q->default('PLN');
                    $q->where('package_id', $package_free->id);
                }]);
            }])->first();

            $this->assertSame($company->id, $history->company_id);
            $this->assertSame($module->id, $history->module_id);
            $this->assertSame($module->mods[0]->id, $history->module_mod_id);
            $this->assertSame($module->mods[0]->value, $history->new_value);
            $this->assertSame(Carbon::now()->toDateTimeString(), $history->start_date->toDateTimeString());
            $this->assertSame(null, $history->expiration_date);
            $this->assertSame(CompanyModuleHistory::STATUS_USED, $history->status);
            $this->assertSame($package_free->id, $history->package_id);
            $this->assertSame($module->mods[0]->modPrices[0]->price, $history->price);
            $this->assertSame($module->mods[0]->modPrices[0]->currency, $history->currency);
        }

        $company_module = CompanyModule::whereNull('package_id')->orderByDesc('id')->first();
        $this->assertSame($company->id, $company_module->company_id);
        $this->assertSame($module_extend->id, $company_module->module_id);
        $this->assertSame('', $company_module->value);
        $this->assertSame(null, $company_module->package_id);
        $this->assertSame(null, $company_module->expiration_date);
        $this->assertSame(null, $company_module->subscription_id);

        $transaction_id = CompanyModuleHistory::whereNull('package_id')->where('company_id', $company->id)
            ->orderByDesc('id')->first()->transaction_id;

        $history = CompanyModuleHistory::where('transaction_id', $transaction_id)->first();
        $this->assertSame($company->id, $history->company_id);
        $this->assertSame($module_extend->id, $history->module_id);
        $this->assertSame($mod->id, $history->module_mod_id);
        $this->assertSame('test1', $history->old_value);
        $this->assertSame('', $history->new_value);
        $this->assertSame(Carbon::now()->toDateTimeString(), $history->start_date->toDateTimeString());
        $this->assertSame(null, $history->expiration_date);
        $this->assertSame(CompanyModuleHistory::STATUS_USED, $history->status);
        $this->assertSame(null, $history->package_id);
        $this->assertSame(0, $history->price);
        $this->assertSame('PLN', $history->currency);
    }

    /** @test */
    public function handle_firstRenew_testGetOnlyExpirationSubscriptionModule()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        Notification::fake();

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM, Carbon::now()->addDays(365));

        $subscription_1 = factory(Subscription::class)->create([
            'repeats' => 0,
            'user_id' => $this->user->id,
            'days' => 30,
        ]);
        $module = Module::findBySlug('test.extend.module');
        $mod = $module->mods()->where('value', 'test1')->where('test', 0)->first();
        $mod_price = $mod->modPrices()->where('days', 30)->first();

        CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->update([
            'value' => 'test1',
            'expiration_date' => Carbon::now()->subHour(),
            'subscription_id' => $subscription_1->id,
        ]);
        $payment = factory(Payment::class)->create([
            'subscription_id' => $subscription_1->id,
            'days' => $subscription_1->days,
            'status' => PaymentStatus::STATUS_COMPLETED,
        ]);

        factory(CompanyModuleHistory::class)->create([
            'company_id' => $company->id,
            'module_id' => $mod->module_id,
            'module_mod_id' => $mod->id,
            'new_value' => 'test1',
            'expiration_date' => Carbon::now()->subHour(),
            'status' => CompanyModuleHistory::STATUS_USED,
            'transaction_id' => $payment->transaction->id,
            'price' => $mod_price->price,
            'currency' => $mod_price->currency,
        ]);

        $subscription_2 = factory(Subscription::class)->create(['repeats' => 0]);
        $companyModule_2 = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::now()->addDay(),
            'subscription_id' => $subscription_2->id,
        ]);

        $payments_count = Payment::count();
        $company_modules_count = CompanyModule::count();
        $history_count = CompanyModuleHistory::count();

        $this->job->handle();

        $subscription_2 = $subscription_2->fresh();
        $this->assertSame(0, $subscription_2->repeats);

        $subscription_1 = $subscription_1->fresh();
        $this->assertSame(1, $subscription_1->repeats);

        $this->assertSame($payments_count + 1, Payment::count());
        $this->assertSame($company_modules_count, CompanyModule::count());
        $this->assertSame($history_count + 1, CompanyModuleHistory::count());

        $payment_new = Payment::orderByDesc('id')->first();
        $this->assertSame($mod_price->price, $payment_new->price_total);
        $this->assertSame((int) ceil($mod_price->price * 23 / 123), $payment_new->vat);
        $this->assertSame($payment->currency, $payment_new->currency);
        $this->assertSame($payment->subscription_id, $payment_new->subscription_id);
        $this->assertSame($payment->expiration_date, $payment_new->expiration_date);
        $this->assertSame($payment->days, $payment_new->days);

        Notification::assertNotSentTo($this->user, SubscriptionCanceled::class);

        $company_module = CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->first();
        $this->assertSame('test1', $company_module->value);
        $this->assertSame(Carbon::now()->subHour()->toDateTimeString(), $company_module->expiration_date->toDateTimeString());

        $history = CompanyModuleHistory::where('company_id', $company->id)->where('module_id', $module->id)->orderByDesc('id')->first();
        $this->assertSame($mod->id, $history->module_mod_id);
        $this->assertSame('test1', $history->old_value);
        $this->assertSame('test1', $history->new_value);
        $this->assertSame(null, $history->start_date);
        $this->assertSame(null, $history->expiration_date);
        $this->assertSame(CompanyModuleHistory::STATUS_NOT_USED, $history->status);
        $this->assertSame(null, $history->package_id);
        $this->assertSame($mod_price->price, $history->price);
        $this->assertSame($mod_price->currency, $history->currency);
        $this->assertSame($mod_price->vat, $history->vat);
        $this->assertSame($payment_new->transaction_id, $history->transaction_id);
    }

    /** @test */
    public function handle_firstRenew_testGetOnlyExpirationSubscriptionPackage()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        Notification::fake();

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM, Carbon::now()->subHour());

        $subscription_1 = factory(Subscription::class)->create([
            'repeats' => 0,
            'user_id' => $this->user->id,
            'days' => 30,
        ]);

        $payment = factory(Payment::class)->create([
            'subscription_id' => $subscription_1->id,
            'days' => $subscription_1->days,
            'status' => PaymentStatus::STATUS_COMPLETED,
        ]);

        CompanyModule::where('company_id', $company->id)->whereNotNull('package_id')->update([
            'subscription_id' => $subscription_1->id,
        ]);

        CompanyModuleHistory::where('company_id', $company->id)->whereNotNull('package_id')->update([
            'transaction_id' => $payment->transaction->id,
        ]);

        $subscription_2 = factory(Subscription::class)->create(['repeats' => 0]);
        $companyModule_2 = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::now()->addDay(),
            'subscription_id' => $subscription_2->id,
            'package_id' => 1,
        ]);

        $payments_count = Payment::count();
        $company_modules_count = CompanyModule::count();
        $history_count = CompanyModuleHistory::count();

        $package_modules = CompanyModule::where('company_id', $company->id)->whereNotNull('package_id')->get();
        $package_modules_history = CompanyModuleHistory::where('company_id', $company->id)->whereNotNull('package_id')->get();

        $this->job->handle();

        $subscription_2 = $subscription_2->fresh();
        $this->assertSame(0, $subscription_2->repeats);

        $subscription_1 = $subscription_1->fresh();
        $this->assertSame(1, $subscription_1->repeats);

        $this->assertSame($payments_count + 1, Payment::count());
        $this->assertSame($company_modules_count, CompanyModule::count());
        $this->assertSame($history_count + count($package_modules), CompanyModuleHistory::count());

        $payment_new = Payment::orderByDesc('id')->first();
        $this->assertSame($payment->price_total, $payment_new->price_total);
        $this->assertSame($payment->vat, $payment_new->vat);
        $this->assertSame($payment->currency, $payment_new->currency);
        $this->assertSame($payment->subscription_id, $payment_new->subscription_id);
        $this->assertSame($payment->expiration_date, $payment_new->expiration_date);
        $this->assertSame($payment->days, $payment_new->days);

        foreach ($package_modules as $module) {
            $company_module = CompanyModule::where('company_id', $company->id)->where('module_id', $module->module_id)->first();
            $this->assertSame($module->value, $company_module->value);
            $this->assertSame($module->expiration_date->toDateTimeString(), $company_module->expiration_date->toDateTimeString());
            $this->assertSame($module->package_id, $company_module->package_id);
            $this->assertSame($module->subscription_id, $company_module->subscription_id);
        }

        foreach ($package_modules_history as $item) {
            $history = CompanyModuleHistory::where('company_id', $company->id)->where('module_id', $item->module_id)->orderByDesc('id')->first();
            $this->assertSame($item->module_mod_id, $history->module_mod_id);
            $this->assertSame($item->new_value, $history->old_value);
            $this->assertSame($item->new_value, $history->new_value);
            $this->assertSame(null, $history->start_date);
            $this->assertSame(null, $history->expiration_date);
            $this->assertSame(CompanyModuleHistory::STATUS_NOT_USED, $history->status);
            $this->assertSame($item->package_id, $history->package_id);
            $this->assertSame($item->price, $history->price);
            $this->assertSame($item->currency, $history->currency);
            $this->assertSame($item->vat, $history->vat);
            $this->assertSame($payment_new->transaction_id, $history->transaction_id);
        }

        Notification::assertNotSentTo($this->user, SubscriptionCanceled::class);
    }

    /** @test */
    public function handle_nextRenewLastStatusPending()
    {
        Notification::fake();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $subscription_1 = factory(Subscription::class)->create([
            'repeats' => 1,
            'user_id' => $this->user->id,
        ]);
        $companyModule_1 = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::now()->subDay(),
            'company_id' => $company->id,
            'subscription_id' => $subscription_1->id,
        ]);
        $payment = factory(Payment::class)->create([
            'subscription_id' => $subscription_1->id,
            'status' => PaymentStatus::STATUS_PENDING,
        ]);

        $payments_count = Payment::count();

        $this->job->handle();

        $subscription_1 = $subscription_1->fresh();
        $this->assertSame(2, $subscription_1->repeats);

        $this->assertSame($payments_count, Payment::count());

        Notification::assertNotSentTo($this->user, SubscriptionCanceled::class);
    }

    /** @test */
    public function handle_nextRenewLastStatusCanceled()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        Notification::fake();

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM, Carbon::now()->addDays(365));

        $subscription_1 = factory(Subscription::class)->create([
            'repeats' => 1,
            'user_id' => $this->user->id,
            'days' => 30,
        ]);
        $module = Module::findBySlug('test.extend.module');
        $mod = $module->mods()->where('value', 'test1')->where('test', 0)->first();
        $mod_price = $mod->modPrices()->where('days', 30)->first();

        CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->update([
            'value' => 'test1',
            'expiration_date' => Carbon::now()->subHour(),
            'subscription_id' => $subscription_1->id,
        ]);

        $payment = factory(Payment::class)->create([
            'subscription_id' => $subscription_1->id,
            'days' => $subscription_1->days,
            'status' => PaymentStatus::STATUS_CANCELED,
        ]);

        factory(CompanyModuleHistory::class)->create([
            'company_id' => $company->id,
            'module_id' => $mod->module_id,
            'module_mod_id' => $mod->id,
            'new_value' => 'test1',
            'expiration_date' => Carbon::now()->subHour(),
            'status' => CompanyModuleHistory::STATUS_USED,
            'transaction_id' => $payment->transaction->id,
            'price' => $mod_price->price,
            'currency' => $mod_price->currency,
        ]);

        $payments_count = Payment::count();
        $company_modules_count = CompanyModule::count();
        $history_count = CompanyModuleHistory::count();

        $this->job->handle();

        $subscription_1 = $subscription_1->fresh();
        $this->assertSame(2, $subscription_1->repeats);

        $this->assertSame($payments_count + 1, Payment::count());
        $this->assertSame($company_modules_count, CompanyModule::count());
        $this->assertSame($history_count + 1, CompanyModuleHistory::count());

        $payment_new = Payment::orderByDesc('id')->first();
        $this->assertSame($mod_price->price, $payment_new->price_total);
        $this->assertSame((int) ceil($mod_price->price * 23 / 123), $payment_new->vat);
        $this->assertSame($payment->currency, $payment_new->currency);
        $this->assertSame($payment->subscription_id, $payment_new->subscription_id);
        $this->assertSame($payment->expiration_date, $payment_new->expiration_date);
        $this->assertSame($payment->days, $payment_new->days);

        Notification::assertNotSentTo($this->user, SubscriptionCanceled::class);

        $company_module = CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->first();
        $this->assertSame('test1', $company_module->value);
        $this->assertSame(Carbon::now()->subHour()->toDateTimeString(), $company_module->expiration_date->toDateTimeString());

        $history = CompanyModuleHistory::where('company_id', $company->id)->where('module_id', $module->id)->orderByDesc('id')->first();
        $this->assertSame($mod->id, $history->module_mod_id);
        $this->assertSame('test1', $history->old_value);
        $this->assertSame('test1', $history->new_value);
        $this->assertSame(null, $history->start_date);
        $this->assertSame(null, $history->expiration_date);
        $this->assertSame(CompanyModuleHistory::STATUS_NOT_USED, $history->status);
        $this->assertSame(null, $history->package_id);
        $this->assertSame($mod_price->price, $history->price);
        $this->assertSame($mod_price->currency, $history->currency);
        $this->assertSame($mod_price->vat, $history->vat);
        $this->assertSame($payment_new->transaction_id, $history->transaction_id);
    }

    /** @test */
    public function handle_renewExtendModuleButPackageIsExpired()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        Notification::fake();

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM, Carbon::now()->subDays(23));

        $subscription_1 = factory(Subscription::class)->create([
            'repeats' => 1,
            'user_id' => $this->user->id,
            'days' => 30,
        ]);
        $module = Module::findBySlug('test.extend.module');
        $mod = $module->mods()->where('value', 'test1')->where('test', 0)->first();
        $mod_price = $mod->modPrices()->where('days', 30)->first();

        CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->update([
            'value' => 'test1',
            'expiration_date' => Carbon::now()->subHour(),
            'subscription_id' => $subscription_1->id,
        ]);

        $payment = factory(Payment::class)->create([
            'subscription_id' => $subscription_1->id,
            'days' => $subscription_1->days,
            'status' => PaymentStatus::STATUS_COMPLETED,
        ]);

        factory(CompanyModuleHistory::class)->create([
            'company_id' => $company->id,
            'module_id' => $mod->module_id,
            'module_mod_id' => $mod->id,
            'new_value' => 'test1',
            'expiration_date' => Carbon::now()->subHour(),
            'status' => CompanyModuleHistory::STATUS_USED,
            'transaction_id' => $payment->transaction->id,
            'price' => $mod_price->price,
            'currency' => $mod_price->currency,
        ]);

        $payments_count = Payment::count();
        $company_modules_count = CompanyModule::count();
        $history_count = CompanyModuleHistory::count();

        $this->job->handle();

        $subscription_1 = $subscription_1->fresh();
        $this->assertSame(2, $subscription_1->repeats);

        $this->assertSame($payments_count, Payment::count());
        $this->assertSame($company_modules_count, CompanyModule::count());
        $this->assertSame($history_count, CompanyModuleHistory::count());

        Notification::assertNotSentTo($this->user, SubscriptionCanceled::class);

        $company_module = CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->first();
        $this->assertSame('test1', $company_module->value);
        $this->assertSame(Carbon::now()->subHour()->toDateTimeString(), $company_module->expiration_date->toDateTimeString());
    }

    /** @test */
    public function handle_nextRenewModuleButPackageExpieretIn20Days()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        Notification::fake();

        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM, Carbon::now()->addDays(20));

        $subscription_1 = factory(Subscription::class)->create([
            'repeats' => 0,
            'user_id' => $this->user->id,
            'days' => 30,
        ]);
        $module = Module::findBySlug('test.extend.module');
        $mod = $module->mods()->where('value', 'test1')->where('test', 0)->first();
        $mod_price = $mod->modPrices()->where('days', 30)->first();

        CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->update([
            'value' => 'test1',
            'expiration_date' => Carbon::now()->subHour(),
            'subscription_id' => $subscription_1->id,
        ]);

        $payment = factory(Payment::class)->create([
            'subscription_id' => $subscription_1->id,
            'days' => $subscription_1->days,
            'status' => PaymentStatus::STATUS_COMPLETED,
        ]);

        factory(CompanyModuleHistory::class)->create([
            'company_id' => $company->id,
            'module_id' => $mod->module_id,
            'module_mod_id' => $mod->id,
            'new_value' => 'test1',
            'expiration_date' => Carbon::now()->subHour(),
            'status' => CompanyModuleHistory::STATUS_USED,
            'transaction_id' => $payment->transaction->id,
            'price' => $mod_price->price,
            'currency' => $mod_price->currency,
        ]);

        $payments_count = Payment::count();
        $company_modules_count = CompanyModule::count();
        $history_count = CompanyModuleHistory::count();

        $this->job->handle();

        $subscription_1 = $subscription_1->fresh();
        $this->assertSame(1, $subscription_1->repeats);

        $this->assertSame($payments_count + 1, Payment::count());
        $this->assertSame($company_modules_count, CompanyModule::count());
        $this->assertSame($history_count + 1, CompanyModuleHistory::count());

        $price = (int) ceil($mod_price->price / $mod_price->days * 20);

        $payment_new = Payment::orderByDesc('id')->first();
        $this->assertSame($price, $payment_new->price_total);
        $this->assertSame((int) ceil($price * 23 / 123), $payment_new->vat);
        $this->assertSame($payment->currency, $payment_new->currency);
        $this->assertSame($payment->subscription_id, $payment_new->subscription_id);
        $this->assertSame($payment->expiration_date, $payment_new->expiration_date);
        $this->assertSame(20, $payment_new->days);

        Notification::assertNotSentTo($this->user, SubscriptionCanceled::class);

        $company_module = CompanyModule::where('company_id', $company->id)->where('module_id', $module->id)->first();
        $this->assertSame('test1', $company_module->value);
        $this->assertSame(Carbon::now()->subHour()->toDateTimeString(), $company_module->expiration_date->toDateTimeString());

        $history = CompanyModuleHistory::where('company_id', $company->id)->where('module_id', $module->id)->orderByDesc('id')->first();
        $this->assertSame($mod->id, $history->module_mod_id);
        $this->assertSame('test1', $history->old_value);
        $this->assertSame('test1', $history->new_value);
        $this->assertSame(null, $history->start_date);
        $this->assertSame(null, $history->expiration_date);
        $this->assertSame(CompanyModuleHistory::STATUS_NOT_USED, $history->status);
        $this->assertSame(null, $history->package_id);
        $this->assertSame($mod_price->price, $history->price);
        $this->assertSame($mod_price->currency, $history->currency);
        $this->assertSame($mod_price->vat, $history->vat);
        $this->assertSame($payment_new->transaction_id, $history->transaction_id);
    }
}
