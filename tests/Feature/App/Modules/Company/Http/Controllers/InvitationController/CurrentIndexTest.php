<?php

namespace Tests\Feature\App\Modules\Company\Http\Controllers\InvitationController;

use App\Helpers\ErrorCode;
use App\Models\Db\Company;
use App\Models\Db\CountryVatinPrefix;
use App\Models\Db\Invitation;
use App\Models\Db\User;
use App\Models\Other\InvitationStatus;
use App\Models\Other\RoleType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;

class CurrentIndexTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_401_when_not_logged()
    {
        $this->get('users/current/invitations');
        $this->verifyErrorResponse(401, ErrorCode::AUTH_INVALID_TOKEN);
    }

    /** @test */
    public function it_gets_no_invitations_when_no_created()
    {
        $this->createUser();
        auth()->login($this->user);
        $this->get('users/current/invitations')->seeStatusCode(200);

        $data = $this->decodeResponseJson()['data'];
        $this->assertEquals([], $data);
    }

    /** @test */
    public function it_gets_only_current_user_invitations()
    {
        $now = Carbon::parse('2017-07-01');
        Carbon::setTestNow($now);

        $this->createUser();
        auth()->login($this->user);
        $company = factory(Company::class)->create([
            'country_vatin_prefix_id' => 1,
        ]);
        $this->assignUsersToCompany(User::all(), $company, RoleType::OWNER);

        $invitations = $this->createInvitations($company);
        $this->get('users/current/invitations')->seeStatusCode(200);
        $data = $this->decodeResponseJson()['data'];
        $this->assertSame($invitations->count(), count($data));

        // because we sort by created_at it might happen that it will be in random order when dates
        // are same so we need to ignore the order here
        if ($data[0]['token'] != $invitations[0]->unique_hash) {
            $invitations = $invitations->reverse()->values();
        }

        // finally we test data
        foreach ($data as $index => $element) {
            $this->assertEquals($this->formatInvitation($invitations[$index], $this->user), $element);
        }
    }

    /** @test */
    public function it_gets_only_active_invitation()
    {
        $this->createUser();
        auth()->login($this->user);
        $company = factory(Company::class)->create();
        $this->assignUsersToCompany(User::all(), $company, RoleType::OWNER);

        $invitations = $this->createInvitations($company);
        $this->get('users/current/invitations?active=1')->seeStatusCode(200);

        $data = $this->decodeResponseJson()['data'];
        $this->assertSame(1, count($data));
        $active_invitation = $data[0];
        $this->assertGreaterThanOrEqual(Carbon::now(), $active_invitation['expiration_time']);
    }

    protected function formatInvitation(Invitation $invitation, User $user)
    {
        $output = $invitation->toArray();
        unset($output['unique_hash']);
        $output['token'] = $invitation->unique_hash;
        $output['company']['data'] = $this->mapArrayToExpectedStructure(
            $invitation->company->toArray(),
            ['id', 'name', 'vatin']
        );
        $output['company']['data']['owner']['data'] = [
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'avatar' => $user->avatar,
            'activated' => $user->activated,
            'deleted' => $user->deleted,
        ];
        $vatin_prefix = CountryVatinPrefix::find(1);
        $output['company']['data']['vatin_prefix']['data'] = [
            'id' => 1,
            'name' => 'Afganistan',
            'key' => 'AF',
            'created_at' => $vatin_prefix->created_at,
            'updated_at' => $vatin_prefix->updated_at,
        ];
        $output['role']['data'] = $invitation->role->toArray();

        return $output;
    }

    protected function createInvitations(Company $company)
    {
        $pendingInvitations = factory(Invitation::class, 2)->create([
            'email' => $this->user->email,
            'status' => InvitationStatus::PENDING,
            'company_id' => $company->id,
            'expiration_time' => Carbon::tomorrow(),
        ]);
        $pendingInvitations[0]->update(['expiration_time' => Carbon::yesterday()]);

        $approvedInvitations = factory(Invitation::class, 3)->create([
            'email' => $this->user->email,
            'status' => InvitationStatus::APPROVED,
            'company_id' => $company->id,
        ]);

        $rejectedInvitations = factory(Invitation::class, 4)->create([
            'email' => $this->user->email,
            'status' => InvitationStatus::REJECTED,
            'company_id' => $company->id,
        ]);

        $deletedInvitations = factory(Invitation::class, 5)->create([
            'email' => $this->user->email,
            'status' => InvitationStatus::DELETED,
            'company_id' => $company->id,
        ]);

        $pendingInvitationsForOtherUser = factory(Invitation::class, 6)->create([
            'email' => time() . '@example.com',
            'status' => InvitationStatus::PENDING,
            'company_id' => $company->id,
        ]);

        return $pendingInvitations;
    }
}
