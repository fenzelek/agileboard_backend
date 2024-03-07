<?php

namespace Tests\Feature\App\Listeners;

use App\Listeners\SentNotifyOvertimeEmail;
use App\Models\Db\Package;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use App\Modules\CalendarAvailability\Events\OvertimeWasAdded;
use App\Notifications\OvertimeAdded;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SentNotifyOvertimeEmailTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @feature User Availability
     * @scenario Sent mail
     * @case email not sent user does not have admin or owner
     *
     * @test
     */
    public function it_not_sends_overtime_email_when_company_has_not_admins()
    {
        //GIVEN
        Notification::fake();

        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        $this->user->setSelectedCompany($company->id);

        //WHEN
        $listener = App::make(SentNotifyOvertimeEmail::class);
        $listener->handle(new OvertimeWasAdded($this->user));

        //THEN
        Notification::assertNotSentTo($this->user, OvertimeAdded::class);
    }

    /**
     * @feature User Availability
     * @scenario Sent mail
     * @case email sent user have admin
     *
     * @test
     */
    public function it_sends_overtime_email_when_company_has_admin()
    {
        //GIVEN
        Notification::fake();

        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        $this->user->setSelectedCompany($company->id);

        $admin = factory(User::class, 1)->create(['deleted' => 0]);
        $this->assignUsersToCompany($admin, $company, RoleType::ADMIN);

        //WHEN
        $listener = App::make(SentNotifyOvertimeEmail::class);
        $listener->handle(new OvertimeWasAdded($this->user));

        //THEN
        Notification::assertSentTo(
            $admin,
            OvertimeAdded::class,
            function ($notification, $channels) {
                return $channels == ['mail'];
            }
        );
    }

    /**
     * @feature User Availability
     * @scenario Sent mail
     * @case email sent user have admin and owner
     *
     * @test
     */
    public function it_sends_overtime_email_when_company_has_admin_and_owner()
    {
        //GIVEN
        Notification::fake();

        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        $this->user->setSelectedCompany($company->id);

        $admin = factory(User::class, 1)->create(['deleted' => 0]);
        $this->assignUsersToCompany($admin, $company, RoleType::ADMIN);
        $owner = factory(User::class, 1)->create(['deleted' => 0]);
        $this->assignUsersToCompany($owner, $company, RoleType::OWNER);

        //WHEN
        $listener = App::make(SentNotifyOvertimeEmail::class);
        $listener->handle(new OvertimeWasAdded($this->user));

        //THEN
        Notification::assertSentTo(
            $admin,
            OvertimeAdded::class,
            function ($notification, $channels) {
                return $channels == ['mail'];
            }
        );
        Notification::assertSentTo(
            $owner,
            OvertimeAdded::class,
            function ($notification, $channels) {
                return $channels == ['mail'];
            }
        );
    }
}
