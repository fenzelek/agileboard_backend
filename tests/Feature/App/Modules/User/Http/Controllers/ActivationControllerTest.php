<?php

namespace Tests\Feature\App\Modules\User\Http\Controllers;

use App\Helpers\ErrorCode;
use App\Models\Db\User;
use App\Modules\User\Events\ActivationTokenWasRequested;
use App\Modules\User\Events\UserWasActivated;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use JWTAuth;
use Event;
use Tests\BrowserKitTestCase;

class ActivationControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function activate_without_data()
    {
        Event::fake();
        $this->put('/activation');
        $this->verifyValidationResponse(['activation_token']);
        Event::assertNotDispatched(UserWasActivated::class);
    }

    /** @test */
    public function activate_with_invalid_email()
    {
        Event::fake();
        $this->put('/activation', ['activation_token' => 'token']);
        $this->verifyErrorResponse(404, ErrorCode::ACTIVATION_INVALID_TOKEN_OR_USER);
        Event::assertNotDispatched(UserWasActivated::class);
    }

    /** @test */
    public function activate_for_deleted_user()
    {
        Event::fake();
        $this->createUser(true);
        $this->put('/activation', ['activation_token' => $this->user->activate_hash]);
        $this->verifyErrorResponse(404, ErrorCode::ACTIVATION_INVALID_TOKEN_OR_USER);
        Event::assertNotDispatched(UserWasActivated::class);
    }

    /** @test */
    public function activate_for_activated_user()
    {
        Event::fake();
        $this->createUser();
        $this->put('/activation', ['activation_token' => $this->user->activate_hash]);
        $this->verifyErrorResponse(409, ErrorCode::ACTIVATION_ALREADY_ACTIVATED);
        Event::assertNotDispatched(UserWasActivated::class);
    }

    /** @test */
    public function activate_for_not_activated_user_with_valid_token()
    {
        Event::fake();
        $this->createUser(false, false);

        // before user not activated and activation token filled
        $user = User::find($this->user->id);
        $this->assertEquals(false, $user->activated);

        $this->put('/activation', ['activation_token' => $this->user->activate_hash]);

        $this->seeStatusCode(200)->seeJsonStructure(['data' => ['token']])->isJson();

        // after user activated
        $user = User::find($this->user->id);
        $this->assertEquals(true, $user->activated);

        // get token and verify if it's valid
        $this->assertFalse(auth()->check());
        $response = $this->decodeResponseJson();
        $token = $response['data']['token'];
        $this->assertEquals($this->user->id, JWTAuth::setToken($token)->authenticate()->id);
        $this->assertTrue(auth()->check());

        Event::assertDispatched(UserWasActivated::class, function ($e) use ($user) {
            return $e->user->id == $user->id;
        });
    }

    /** @test */
    public function resend_without_data()
    {
        Event::fake();
        $this->put('/activation/resend');
        $this->verifyValidationResponse(['email', 'url']);
        Event::assertNotDispatched(ActivationTokenWasRequested::class);
    }

    /** @test */
    public function resend_with_invalid_email()
    {
        Event::fake();
        $this->put('/activation/resend', ['email' => 'sample@email.com', 'url' => 'http://example.com', 'language' => 'pl']);
        $this->verifyErrorResponse(404, ErrorCode::AUTH_USER_NOT_FOUND);
        Event::assertNotDispatched(ActivationTokenWasRequested::class);
    }

    /** @test */
    public function resend_for_deleted_user()
    {
        Event::fake();
        $this->createUser(true, false);
        $this->put('/activation/resend', ['email' => $this->user->email, 'url' => 'http://example.com']);
        $this->verifyErrorResponse(404, ErrorCode::AUTH_USER_NOT_FOUND);
        Event::assertNotDispatched(ActivationTokenWasRequested::class);
    }

    /** @test */
    public function resend_for_activated_user()
    {
        Event::fake();
        $this->createUser(false, true);
        $this->put('/activation/resend', ['email' => $this->user->email, 'url' => 'http://example.com']);
        $this->verifyErrorResponse(409, ErrorCode::ACTIVATION_ALREADY_ACTIVATED);
        Event::assertNotDispatched(ActivationTokenWasRequested::class);
    }

    /** @test */
    public function resend_for_not_activated_user()
    {
        Event::fake();
        $this->createUser(false, false);
        $url = 'http://example.com';

        // before user was not activated
        $user = User::find($this->user->id);
        $token = $user->activation_token;
        $this->assertSame(0, $user->activated);

        $this->put('/activation/resend', ['email' => $this->user->email, 'url' => $url]);
        $this->seeStatusCode(200)->isJson();

        // after user was not activated and token is the same
        $user = User::find($this->user->id);
        $this->assertSame(0, $user->activated);
        $this->assertSame($token, $user->activation_token);

        // no other data in response
        $response = $this->decodeResponseJson();
        $this->assertEquals([], $response['data']);

        // verify event
        Event::assertDispatched(ActivationTokenWasRequested::class, function ($e) use ($user, $url) {
            return $e->user->id == $user->id && $e->url == $url;
        });
    }
}
