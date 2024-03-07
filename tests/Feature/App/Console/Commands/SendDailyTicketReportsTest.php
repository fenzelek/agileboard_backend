<?php

namespace Tests\Feature\App\Console\Commands;

use App\Console\Commands\SendDailyTicketReports;
use App\Models\Db\Company;
use App\Models\Db\User;
use App\Models\Other\UserCompanyStatus;
use App\Modules\Agile\Services\Report;
use App\Notifications\DailyTicketReport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\Helpers\ExtendModule;
use Tests\TestCase;

class SendDailyTicketReportsTest extends TestCase
{
    use DatabaseTransactions, ExtendModule;

    /**
     * @var Report|\Mockery\MockInterface
     */
    private $report_query;
    /**
     * @var Report|\Mockery\MockInterface
     */
    private $report_service;

    protected function setUp():void
    {
        parent::setUp();

        $this->report_query = \Mockery::mock(Builder::class);
        $this->report_service = \Mockery::mock(Report::class);

        $command = $this->app->make(SendDailyTicketReports::class, [
            'report_service' => $this->report_service,
        ]);
        $this->instance(SendDailyTicketReports::class, $command);
    }

    /** @test */
    public function handle_success()
    {
        Notification::fake();

        $this->report_service->shouldReceive('getDaily')->twice()->andReturn($this->report_query);
        $this->report_service->shouldReceive('getProjectStatuses')->once()->andReturn(Collection::make());
        $this->report_service->shouldReceive('cleanUp')->twice();

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->report_query->shouldReceive('get')->twice()->andReturn(
            Collection::make($this->getFakeReportData()),
            Collection::make([])
        );

        $users[] = $this->createUserWithoutCompany('1_@example.com');
        $users[] = $this->createUserBelongToCompany('2_@example.com');
        $users[] = $this->createUserBelongToCompany('3_@example.com');
        $users[] = $this->createUserDeletedFromCompany('4_@example.com');

        Artisan::call('daily-ticket-report:send');

        Notification::assertNotSentTo($users[0], DailyTicketReport::class);
        Notification::assertSentTo($users[1], DailyTicketReport::class);
        Notification::assertNotSentTo($users[2], DailyTicketReport::class);
        Notification::assertNotSentTo($users[3], DailyTicketReport::class);
    }

    /** @test */
    public function with_company_option()
    {
        Notification::fake();

        $this->report_service->shouldReceive('getDaily')->once()->andReturn($this->report_query);
        $this->report_service->shouldReceive('getProjectStatuses')->once()->andReturn(Collection::make());
        $this->report_service->shouldReceive('cleanUp')->once();

        $company_name = 'company';
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->report_query->shouldReceive('get')->once()->andReturn(
            Collection::make($this->getFakeReportData())
        );

        $users[] = $this->createUserBelongToCompany('1_@example.com');
        $users[] = $this->createUserBelongToCompany('2_@example.com');

        $company = factory(Company::class)->create([
            'name' => $company_name,
        ]);

        $users[1]->companies()->attach($company->id);

        Artisan::call('daily-ticket-report:send', ['--company' => $company_name]);

        Notification::assertNotSentTo($users[0], DailyTicketReport::class);
        Notification::assertSentTo($users[1], DailyTicketReport::class);
    }

    private function createUserBelongToCompany($email)
    {
        $company = factory(Company::class)->create();
        $user = factory(User::class)->create([
            'email' => $email,
        ]);
        $user->companies()->attach($company->id);

        return $user;
    }

    private function createUserWithoutCompany($email)
    {
        return factory(User::class)->create([
            'email' => $email,
        ]);
    }

    private function getFakeReportData()
    {
        return [
            [
                'id' => 44,
                'user_id' => 35,
                'resource_id' => 15,
                'object_id' => 15,
                'field_id' => 1,
                'value_before' => 'Est voluptatem quia qui neque deserunt dolorum sunt. In et ut consequatur ea vel error expedita. Doloremque et ut enim reiciendis sint omnis praesentium.',
                'label_before' => 'Recusandae rerum impedit blanditiis nihil. Illo autem quasi esse in doloribus. Velit nesciunt odio non aut.',
                'value_after' => 'Nihil illum harum recusandae deleniti quo non. Quae quibusdam similique cum. Explicabo est doloribus quia consequatur voluptatem aut quo.',
                'label_after' => 'Perspiciatis eligendi sunt dignissimos velit doloremque nostrum ut. Nam ullam et officia. Incidunt velit quia odit et molestias. Voluptates et qui ducimus quasi. Et iste corrupti qui officiis.',
                'created_at' => '2016-02-03 08:09:10',
                'user' => [

                        'id' => 35,
                        'email' => 'useremail@example.com',
                        'first_name' => 'Anne',
                        'last_name' => 'Crooks',
                        'avatar' => '',
                        'activated' => true,
                        'deleted' => false,

                ],
                'field' => [

                        'id' => 1,
                        'object_type' => 'ticket',
                        'field_name' => 'sprint_id',

                ],
                'ticket' => [

                        'id' => 15,
                        'project_id' => 23,
                        'sprint_id' => 2030604927,
                        'status_id' => 728019450,
                        'ticket_id' => 0,
                        'name' => 'Melody Gleason',
                        'title' => 'Sienna Hirthe',
                        'type_id' => 0,
                        'assigned_id' => 0,
                        'reporter_id' => 0,
                        'description' => "Dr. Saige D'Amore",
                        'estimate_time' => 1354929970,
                        'scheduled_time_start' => '1978-03-08 19:33:25',
                        'scheduled_time_end' => '2008-05-10 06:16:17',
                        'priority' => 100,
                        'hidden' => 0,
                        'created_at' => '2016-02-03 08:09:10',
                        'updated_at' => '2016-02-03 08:09:10',
                        'deleted_at' => null,

                ],
            ],
        ];
    }

    private function createUserDeletedFromCompany(string $email)
    {
        $user = $this->createUserBelongToCompany($email);
        $user->userCompanies()
            ->update(['status' => UserCompanyStatus::DELETED]);

        return $user;
    }
}
