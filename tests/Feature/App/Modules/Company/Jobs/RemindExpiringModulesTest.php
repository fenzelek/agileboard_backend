<?php

namespace Tests\Feature\App\Modules\Company\Jobs;

use App\Models\Db\Company;
use App\Models\Db\Role;
use App\Models\Db\User;
use App\Models\Db\UserCompany;
use App\Models\Other\RoleType;
use App\Models\Other\UserCompanyStatus;
use App\Modules\Company\Jobs\RemindExpiringModules;
use App\Modules\Company\Notifications\RemindExpiring;
use App\Modules\Company\Services\PaymentNotificationsService;
use App\Models\Db\CompanyModule;
use App\Models\Db\Subscription;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\ExtendModule;
use Tests\TestCase;

class RemindExpiringModulesTest extends TestCase
{
    use DatabaseTransactions, ExtendModule;

    private $job;

    protected function setUp():void
    {
        parent::setUp();
        $this->job = new RemindExpiringModules(new PaymentNotificationsService());
        $this->createTestExtendModule();
    }

    /** @test */
    public function handle_NotSendStartTime14DaysPackageSubscriptionEnabled()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $subscription = factory(Subscription::class)->create(['user_id' => $this->user->id, 'active' => 1]);
        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-15 00:00:00'),
            'subscription_id' => $subscription->id,
            'company_id' => $company->id,
            'package_id' => 1,
        ]);

        $this->job->handle();

        \Notification::assertNotSentTo($this->user, RemindExpiring::class);
    }

    /** @test */
    public function handle_SendStartTime14DaysStartPackage()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-15 00:00:00'),
            'company_id' => $company->id,
            'package_id' => 1,
        ]);

        $this->job->handle();

        \Notification::assertSentTo(
            $this->user,
            RemindExpiring::class,
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

        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-15 23:59:59'),
            'company_id' => $company->id,
            'package_id' => 1,
        ]);

        $this->job->handle();

        \Notification::assertSentTo(
            $this->user,
            RemindExpiring::class,
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

        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-14 23:59:59'),
            'company_id' => $company->id,
            'package_id' => 1,
        ]);

        $this->job->handle();

        \Notification::assertNotSentTo($this->user, RemindExpiring::class);
    }

    /** @test */
    public function handle_NotSendStartTime15DaysPackage()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-16 00:00:00'),
            'company_id' => $company->id,
            'package_id' => 1,
        ]);

        $this->job->handle();

        \Notification::assertNotSentTo($this->user, RemindExpiring::class);
    }

    /** @test */
    public function handle_SendStartTime30DaysPackage()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-31 02:15:50'),
            'company_id' => $company->id,
            'package_id' => 1,
        ]);

        $this->job->handle();

        \Notification::assertSentTo(
            $this->user,
            RemindExpiring::class,
            function ($notification) use ($companyModule) {
                return $notification->companyModule->id == $companyModule->id &&
                    $notification->days == 30;
            }
        );
    }

    /** @test */
    public function handle_SendStartTime7DaysPackage()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-08 02:15:50'),
            'company_id' => $company->id,
            'package_id' => 1,
        ]);

        $this->job->handle();

        \Notification::assertSentTo(
            $this->user,
            RemindExpiring::class,
            function ($notification) use ($companyModule) {
                return $notification->companyModule->id == $companyModule->id &&
                    $notification->days == 7;
            }
        );
    }

    /** @test */
    public function handle_SendStartTime3DaysPackage()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-04 02:15:50'),
            'company_id' => $company->id,
            'package_id' => 1,
        ]);

        $this->job->handle();

        \Notification::assertSentTo(
            $this->user,
            RemindExpiring::class,
            function ($notification) use ($companyModule) {
                return $notification->companyModule->id == $companyModule->id &&
                    $notification->days == 3;
            }
        );
    }

    /** @test */
    public function handle_SendStartTime2DaysPackage()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-03 02:15:50'),
            'company_id' => $company->id,
            'package_id' => 1,
        ]);

        $this->job->handle();

        \Notification::assertSentTo(
            $this->user,
            RemindExpiring::class,
            function ($notification) use ($companyModule) {
                return $notification->companyModule->id == $companyModule->id &&
                    $notification->days == 2;
            }
        );
    }

    /** @test */
    public function handle_SendStartTime1DaysPackage()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-02 02:15:50'),
            'company_id' => $company->id,
            'package_id' => 1,
        ]);

        $this->job->handle();

        \Notification::assertSentTo(
            $this->user,
            RemindExpiring::class,
            function ($notification) use ($companyModule) {
                return $notification->companyModule->id == $companyModule->id &&
                    $notification->days == 1;
            }
        );
    }

    /** @test */
    public function handle_SendStartTime1DaysPackageWithSubscribtionDisabled()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $subscription = factory(Subscription::class)->create(['user_id' => $this->user->id, 'active' => 0]);
        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-02 02:15:50'),
            'subscription_id' => $subscription->id,
            'company_id' => $company->id,
            'package_id' => 1,
        ]);

        $this->job->handle();

        \Notification::assertSentTo(
            $this->user,
            RemindExpiring::class,
            function ($notification) use ($companyModule) {
                return $notification->companyModule->id == $companyModule->id &&
                    $notification->days == 1;
            }
        );
    }

    /** @test */
    public function handle_NotSendStartTime14DaysModuleSubscriptionEnabled()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $subscription = factory(Subscription::class)->create(['user_id' => $this->user->id, 'active' => 1]);
        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-15 00:00:00'),
            'subscription_id' => $subscription->id,
            'company_id' => $company->id,
            'package_id' => null,
        ]);

        $this->job->handle();

        \Notification::assertNotSentTo($this->user, RemindExpiring::class);
    }

    /** @test */
    public function handle_SendStartTime14DaysStartModule()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-15 00:00:00'),
            'company_id' => $company->id,
            'package_id' => null,
        ]);

        $this->job->handle();

        \Notification::assertSentTo(
            $this->user,
            RemindExpiring::class,
            function ($notification) use ($companyModule) {
                return $notification->companyModule->id == $companyModule->id &&
                    $notification->days == 14;
            }
        );
    }

    /** @test */
    public function handle_SendStartTime14DaysEndModule()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-15 23:59:59'),
            'company_id' => $company->id,
            'package_id' => null,
        ]);

        $this->job->handle();

        \Notification::assertSentTo(
            $this->user,
            RemindExpiring::class,
            function ($notification) use ($companyModule) {
                return $notification->companyModule->id == $companyModule->id &&
                    $notification->days == 14;
            }
        );
    }

    /** @test */
    public function handle_NotSendStartTime13DaysModule()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-14 23:59:59'),
            'company_id' => $company->id,
            'package_id' => null,
        ]);

        $this->job->handle();

        \Notification::assertNotSentTo($this->user, RemindExpiring::class);
    }

    /** @test */
    public function handle_NotSendStartTime15DaysModule()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-16 00:00:00'),
            'company_id' => $company->id,
            'package_id' => null,
        ]);

        $this->job->handle();

        \Notification::assertNotSentTo($this->user, RemindExpiring::class);
    }

    /** @test */
    public function handle_SendStartTime30DaysModule()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-31 02:15:50'),
            'company_id' => $company->id,
            'package_id' => null,
        ]);

        $this->job->handle();

        \Notification::assertSentTo(
            $this->user,
            RemindExpiring::class,
            function ($notification) use ($companyModule) {
                return $notification->companyModule->id == $companyModule->id &&
                    $notification->days == 30;
            }
        );
    }

    /** @test */
    public function handle_SendStartTime7DaysModule()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-08 02:15:50'),
            'company_id' => $company->id,
            'package_id' => null,
        ]);

        $this->job->handle();

        \Notification::assertSentTo(
            $this->user,
            RemindExpiring::class,
            function ($notification) use ($companyModule) {
                return $notification->companyModule->id == $companyModule->id &&
                    $notification->days == 7;
            }
        );
    }

    /** @test */
    public function handle_SendStartTime3DaysModule()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-04 02:15:50'),
            'company_id' => $company->id,
            'package_id' => null,
        ]);

        $this->job->handle();

        \Notification::assertSentTo(
            $this->user,
            RemindExpiring::class,
            function ($notification) use ($companyModule) {
                return $notification->companyModule->id == $companyModule->id &&
                    $notification->days == 3;
            }
        );
    }

    /** @test */
    public function handle_SendStartTime2DaysModule()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-03 02:15:50'),
            'company_id' => $company->id,
            'package_id' => null,
        ]);

        $this->job->handle();

        \Notification::assertSentTo(
            $this->user,
            RemindExpiring::class,
            function ($notification) use ($companyModule) {
                return $notification->companyModule->id == $companyModule->id &&
                    $notification->days == 2;
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

        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-02 02:15:50'),
            'company_id' => $company->id,
            'package_id' => null,
        ]);

        $this->job->handle();

        \Notification::assertSentTo(
            $this->user,
            RemindExpiring::class,
            function ($notification) use ($companyModule) {
                return $notification->companyModule->id == $companyModule->id &&
                    $notification->days == 1;
            }
        );
    }

    /** @test */
    public function handle_SendStartTime1DaysModuleWithSubscribtionDisabled()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $subscription = factory(Subscription::class)->create(['user_id' => $this->user->id, 'active' => 0]);
        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-02 02:15:50'),
            'subscription_id' => $subscription->id,
            'company_id' => $company->id,
            'package_id' => null,
        ]);

        $this->job->handle();

        \Notification::assertSentTo(
            $this->user,
            RemindExpiring::class,
            function ($notification) use ($companyModule) {
                return $notification->companyModule->id == $companyModule->id &&
                    $notification->days == 1;
            }
        );
    }

    /** @test */
    public function handle_sendOnlyOneUser()
    {
        \Notification::fake();

        $return['now'] = Carbon::parse('2017-07-01 00:00:00');
        Carbon::setTestNow($return['now']);

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $other_company = factory(Company::class)->create();
        $other_user = factory(User::class)->create();
        $other_user_company = UserCompany::create([
            'user_id' => $other_user->id,
            'company_id' => $other_company->id,
            'role_id' => Role::findByName(RoleType::OWNER)->id,
            'status' => UserCompanyStatus::APPROVED,
        ]);

        $taxoffice_user_company = UserCompany::create([
            'user_id' => $other_user->id,
            'company_id' => $company->id,
            'role_id' => Role::findByName(RoleType::TAX_OFFICE)->id,
            'status' => UserCompanyStatus::APPROVED,
        ]);

        $companyModule = factory(CompanyModule::class)->create([
            'expiration_date' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-07-15 23:59:59'),
            'company_id' => $company->id,
            'package_id' => 1,
        ]);

        $this->job->handle();

        \Notification::assertSentTo(
            $this->user,
            RemindExpiring::class,
            function ($notification) use ($companyModule) {
                return $notification->companyModule->id == $companyModule->id &&
                    $notification->days == 14;
            }
        );

        \Notification::assertNotSentTo($other_user, RemindExpiring::class);
    }
}
