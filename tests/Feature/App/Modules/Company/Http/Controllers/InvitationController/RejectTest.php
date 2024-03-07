<?php

namespace Tests\Feature\App\Modules\Company\Http\Controllers\InvitationController;

use App\Helpers\ErrorCode;
use App\Models\Db\Invitation;
use App\Models\Other\InvitationStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Response;
use Tests\BrowserKitTestCase;

class RejectTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_422_when_no_data()
    {
        $this->put('companies/invitations/reject');

        $this->verifyValidationResponse([
            'token',
        ]);
    }

    /** @test */
    public function it_gets_404_when_invalid_token()
    {
        $this->put('companies/invitations/reject', ['token' => 'sample token']);

        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function it_gets_409_when_invitation_is_not_pending()
    {
        $invitation = new Invitation();
        $invitation->unique_hash = str_random(20);
        $invitation->status = InvitationStatus::APPROVED;
        $invitation->save();

        $this->put('companies/invitations/reject', ['token' => $invitation->unique_hash]);

        $this->verifyErrorResponse(409, ErrorCode::COMPANY_INVITATION_NOT_PENDING);

        $this->assertSame($invitation->status, $invitation->fresh()->status);
    }

    /** @test */
    public function it_gets_409_when_invitation_is_already_expired()
    {
        $invitation = new Invitation();
        $invitation->unique_hash = str_random(20);
        $invitation->status = InvitationStatus::PENDING;
        $invitation->expiration_time = Carbon::now()->subMinute(1);
        $invitation->save();

        $this->put('companies/invitations/reject', ['token' => $invitation->unique_hash]);

        $this->verifyErrorResponse(409, ErrorCode::COMPANY_INVITATION_EXPIRED);

        $this->assertSame($invitation->status, $invitation->fresh()->status);
    }

    /** @test */
    public function it_sets_rejected_status_when_everything_ok()
    {
        $invitation = new Invitation();
        $invitation->unique_hash = str_random(20);
        $invitation->status = InvitationStatus::PENDING;
        $invitation->expiration_time = Carbon::now()->addMinute(1);
        $invitation->save();

        $this->put('companies/invitations/reject', ['token' => $invitation->unique_hash])
            ->seeStatusCode(200);

        // verify whether response is empty
        $response = $this->decodeResponseJson();
        $this->assertEquals([], $response['data']);

        $this->assertSame(InvitationStatus::REJECTED, $invitation->fresh()->status);
    }
}
