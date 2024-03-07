<?php

namespace Tests\Feature\App\Modules\Company\Jobs;

use App\Models\Other\RoleType;
use App\Modules\Company\Jobs\RenewSubscriptionMails;
use App\Modules\Company\Notifications\RenewSubscriptionInformation;
use App\Modules\Company\Services\PaymentNotificationsService;
use App\Models\Db\CompanyModule;
use App\Models\Db\Subscription;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\ExtendModule;
use Tests\TestCase;

class RenewSubscriptionMailsTest extends TestCase
{
    use DatabaseTransactions, ExtendModule;

    private $job;

    protected function setUp():void
    {
        parent::setUp();
        $this->job = new RenewSubscriptionMails(new PaymentNotificationsService());
        $this->createTestExtendModule();
    }

    /** @test */
    public function handle_NotSendStartTime14DaysSubscriptionDisabled()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $subscription = factory(Subscription::class)->create(['user_id' => $this->user->id, 'active' => 0]);
        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-15 00:00:00'),
            'subscription_id' => $subscription->id,
            'company_id' => $company->id,
            'package_id' => 1,
        ]);

        $this->job->handle();

        \Notification::assertNotSentTo($this->user, RenewSubscriptionInformation::class);
    }

    /** @test */
    public function handle_NotSendStartTime14DaysWithoutSubscription()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-15 00:00:00'),
            'subscription_id' => null,
            'company_id' => $company->id,
            'package_id' => 1,
        ]);

        $this->job->handle();

        \Notification::assertNotSentTo($this->user, RenewSubscriptionInformation::class);
    }

    /** @test */
    public function handle_SendStartTime14DaysStartPackage()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $subscription = factory(Subscription::class)->create(['user_id' => $this->user->id]);
        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-15 00:00:00'),
            'subscription_id' => $subscription->id,
            'company_id' => $company->id,
            'package_id' => 1,
        ]);

        $this->job->handle();

        \Notification::assertSentTo(
            $this->user,
            RenewSubscriptionInformation::class,
            function ($notification) use ($companyModule) {
                return $notification->companyModule->id == $companyModule->id &&
                    $notification->days == 14;
            }
        );
    }

    /** @test */
    public function handle_SendStartTime14DaysEndPackage()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $subscription = factory(Subscription::class)->create(['user_id' => $this->user->id]);
        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-15 23:59:59'),
            'subscription_id' => $subscription->id,
            'company_id' => $company->id,
            'package_id' => 1,
        ]);

        $this->job->handle();

        \Notification::assertSentTo(
            $this->user,
            RenewSubscriptionInformation::class,
            function ($notification) use ($companyModule) {
                return $notification->companyModule->id == $companyModule->id &&
                    $notification->days == 14;
            }
        );
    }

    /** @test */
    public function handle_NotSendStartTime13DaysPackage()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $subscription = factory(Subscription::class)->create(['user_id' => $this->user->id]);
        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-14 23:59:59'),
            'subscription_id' => $subscription->id,
            'company_id' => $company->id,
            'package_id' => 1,
        ]);

        $this->job->handle();

        \Notification::assertNotSentTo($this->user, RenewSubscriptionInformation::class);
    }

    /** @test */
    public function handle_NotSendStartTime15DaysPackage()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $subscription = factory(Subscription::class)->create(['user_id' => $this->user->id]);
        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-16 00:00:00'),
            'subscription_id' => $subscription->id,
            'company_id' => $company->id,
            'package_id' => 1,
        ]);

        $this->job->handle();

        \Notification::assertNotSentTo($this->user, RenewSubscriptionInformation::class);
    }

    /** @test */
    public function handle_SendStartTime1DaysPackage()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $subscription = factory(Subscription::class)->create(['user_id' => $this->user->id]);
        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-02 02:15:50'),
            'subscription_id' => $subscription->id,
            'company_id' => $company->id,
            'package_id' => 1,
        ]);

        $this->job->handle();

        \Notification::assertSentTo(
            $this->user,
            RenewSubscriptionInformation::class,
            function ($notification) use ($companyModule) {
                return $notification->companyModule->id == $companyModule->id &&
                    $notification->days == 1;
            }
        );
    }

    /** @test */
    public function handle_SendStartTime1DaysModule()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $subscription = factory(Subscription::class)->create(['user_id' => $this->user->id]);
        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-02 02:15:50'),
            'subscription_id' => $subscription->id,
            'company_id' => $company->id,
            'package_id' => null,
        ]);

        $this->job->handle();

        \Notification::assertSentTo(
            $this->user,
            RenewSubscriptionInformation::class,
            function ($notification) use ($companyModule) {
                return $notification->companyModule->id == $companyModule->id &&
                    $notification->days == 1;
            }
        );
    }
}
