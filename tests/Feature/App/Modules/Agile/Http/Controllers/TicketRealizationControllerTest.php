<?php

namespace Tests\Feature\App\Modules\Agile\Http\Controllers;

use App\Models\Db\Company;
use App\Models\Db\Project;
use App\Models\Db\Role;
use App\Models\Db\Ticket;
use App\Models\Db\TicketRealization;
use App\Models\Db\User;
use App\Models\Db\UserCompany;
use App\Models\Other\RoleType;
use App\Models\Other\UserCompanyStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;
use Tests\Helpers\ProjectHelper;

class TicketRealizationControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ProjectHelper;

    /** @test */
    public function validation_error()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project =
            factory(Project::class)->create([
                'company_id' => $company->id,
                'short_name' => 'PROJ',
                'created_tickets' => 1,
            ]);

        $this->setProjectRole($project);

        $this->get('/ticket-realization?selected_company_id=' .
            $company->id . '&limit=100');

        $this->verifyValidationResponse(['from', 'limit']);
    }

    /** @test */
    public function indexCheckListUsers_success()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        //other company
        $other_user_2 = factory(User::class)->create();
        $other_company = factory(Company::class)->create();

        UserCompany::create([
            'user_id' => $other_user_2->id,
            'company_id' => $other_company->id,
            'role_id' => Role::findByName(RoleType::ADMIN)->id,
            'status' => UserCompanyStatus::APPROVED,
        ]);

        //current company
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $this->user->update(['first_name' => 'a', 'last_name' => 'a']);
        $other_admin = factory(User::class)->create(['first_name' => 'b', 'last_name' => 'b']);
        UserCompany::create([
            'user_id' => $other_admin->id,
            'company_id' => $company->id,
            'role_id' => Role::findByName(RoleType::ADMIN)->id,
            'status' => UserCompanyStatus::APPROVED,
        ]);

        $client = factory(User::class)->create();
        UserCompany::create([
            'user_id' => $client->id,
            'company_id' => $company->id,
            'role_id' => Role::findByName(RoleType::CLIENT)->id,
            'status' => UserCompanyStatus::APPROVED,
        ]);

        $client = factory(User::class)->create();
        UserCompany::create([
            'user_id' => $client->id,
            'company_id' => $company->id,
            'role_id' => Role::findByName(RoleType::ADMIN)->id,
            'status' => UserCompanyStatus::SUSPENDED,
        ]);

        $project =
            factory(Project::class)->create([
                'company_id' => $company->id,
                'short_name' => 'PROJ',
                'created_tickets' => 1,
            ]);

        $project->users()->attach(
            [$this->user->id, $other_admin],
            ['role_id' => Role::findByName(RoleType::ADMIN)->id]
        );

        $this->get('/ticket-realization?selected_company_id=' .
            $company->id . '&from=' . $now->format('Y-m-d'));

        $response = $this->decodeResponseJson();

        $start = $now->startOfWeek();
        $this->assertSame($start->format('Y-m-d'), $response['date_start']);
        $this->assertSame($start->addDays(9)->format('Y-m-d'), $response['date_end']);
        $this->assertSame(2, count($response['data']));
        $this->assertSame($this->user->id, $response['data'][0]['id']);
        $this->assertSame($other_admin->id, $response['data'][1]['id']);
    }

    /** @test */
    public function indexCheckRealization_success()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        //current company
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);

        $other_company = factory(Company::class)->create();
        UserCompany::create([
            'user_id' => $this->user->id,
            'company_id' => $other_company->id,
            'role_id' => Role::findByName(RoleType::ADMIN)->id,
            'status' => UserCompanyStatus::APPROVED,
        ]);

        $project =
            factory(Project::class)->create([
                'company_id' => $company->id,
                'short_name' => 'PROJ',
                'created_tickets' => 1,
            ]);

        $project->users()->attach(
            $this->user->id,
            ['role_id' => Role::findByName(RoleType::ADMIN)->id]
        );

        $other_project =
            factory(Project::class)->create([
                'company_id' => $other_company->id,
                'short_name' => 'PROJ',
                'created_tickets' => 1,
            ]);

        $other_project->users()->attach(
            $this->user->id,
            ['role_id' => Role::findByName(RoleType::ADMIN)->id]
        );

        $ticket_1 = factory(Ticket::class)->create(['project_id' => $project->id]);
        $ticket_2 = factory(Ticket::class)->create(['project_id' => $project->id]);
        $ticket_3 = factory(Ticket::class)->create(['project_id' => $project->id]);

        $other_project_ticket = factory(Ticket::class)->create(['project_id' => $other_project->id]);

        $realizatioan_ticket_1 = factory(TicketRealization::class)->create([
            'ticket_id' => $ticket_1->id,
            'user_id' => $this->user->id,
            'start_at' => Carbon::parse('2016-02-04 08:09:10'),
            'end_at' => Carbon::parse('2016-02-20 08:09:10'),
        ]);

        $realizatioan_ticket_2 = factory(TicketRealization::class)->create([
            'ticket_id' => $ticket_2->id,
            'user_id' => $this->user->id,
            'start_at' => Carbon::parse('2016-01-04 08:09:10'),
            'end_at' => Carbon::parse('2016-02-04 08:09:10'),
        ]);

        $realizatioan_ticket_3 = factory(TicketRealization::class)->create([
            'ticket_id' => $ticket_3->id,
            'user_id' => $this->user->id,
            'start_at' => Carbon::parse('2016-01-04 08:09:10'),
            'end_at' => Carbon::parse('2016-01-05 08:09:10'),
        ]);

        $realizatioan_ticket_4 = factory(TicketRealization::class)->create([
            'ticket_id' => $ticket_2->id,
            'user_id' => $this->user->id,
            'start_at' => Carbon::parse('2016-01-05 08:09:10'),
            'end_at' => Carbon::parse('2016-03-01 08:09:10'),
        ]);

        $realizatioan_other_project_ticket = factory(TicketRealization::class)->create([
            'ticket_id' => $other_project_ticket->id,
            'user_id' => $this->user->id,
            'start_at' => Carbon::parse('2016-01-04 08:09:10'),
            'end_at' => Carbon::parse('2016-02-04 08:09:10'),
        ]);

        $this->get('/ticket-realization?selected_company_id=' .
            $company->id . '&from=' . $now->format('Y-m-d'));

        $response = $this->decodeResponseJson();

        $this->assertSame(1, count($response['data']));
        $this->assertSame($this->user->id, $response['data'][0]['id']);
        $ticket_realizations = $response['data'][0]['ticket_realization']['data'];
        $this->assertSame(3, count($ticket_realizations));
        $this->assertSame($realizatioan_ticket_2->id, $ticket_realizations[0]['id']);
        $this->assertSame($realizatioan_ticket_2->ticket_id, $ticket_realizations[0]['ticket_id']);
        $this->assertSame($realizatioan_ticket_2->user_id, $ticket_realizations[0]['user_id']);
        $this->assertSame($realizatioan_ticket_2->start_at->toDateTimeString(), $ticket_realizations[0]['start_at']);
        $this->assertSame($realizatioan_ticket_2->end_at->toDateTimeString(), $ticket_realizations[0]['end_at']);
        $this->assertSame($ticket_2->id, $ticket_realizations[0]['ticket']['data']['id']);
        $this->assertSame($project->color, $ticket_realizations[0]['ticket']['data']['project']['data']['color']);
        $this->assertSame($realizatioan_ticket_4->id, $ticket_realizations[1]['id']);
        $this->assertSame($realizatioan_ticket_4->ticket_id, $ticket_realizations[1]['ticket_id']);
        $this->assertSame($realizatioan_ticket_4->user_id, $ticket_realizations[1]['user_id']);
        $this->assertSame($realizatioan_ticket_4->start_at->toDateTimeString(), $ticket_realizations[1]['start_at']);
        $this->assertSame($realizatioan_ticket_4->end_at->toDateTimeString(), $ticket_realizations[1]['end_at']);
        $this->assertSame($ticket_2->id, $ticket_realizations[1]['ticket']['data']['id']);
        $this->assertSame($project->color, $ticket_realizations[1]['ticket']['data']['project']['data']['color']);
        $this->assertSame($realizatioan_ticket_1->id, $ticket_realizations[2]['id']);
        $this->assertSame($realizatioan_ticket_1->ticket_id, $ticket_realizations[2]['ticket_id']);
        $this->assertSame($realizatioan_ticket_1->user_id, $ticket_realizations[2]['user_id']);
        $this->assertSame($realizatioan_ticket_1->start_at->toDateTimeString(), $ticket_realizations[2]['start_at']);
        $this->assertSame($realizatioan_ticket_1->end_at->toDateTimeString(), $ticket_realizations[2]['end_at']);
        $this->assertSame($ticket_1->id, $ticket_realizations[2]['ticket']['data']['id']);
        $this->assertSame($project->color, $ticket_realizations[2]['ticket']['data']['project']['data']['color']);
    }
}
