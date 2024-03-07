<?php

namespace Tests\Feature\App\Modules\User\Http\Controllers;

use App\Helpers\ErrorCode;
use App\Models\Db\Package;
use App\Models\Other\RoleType;
use App\Models\Db\UserShortToken;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use JWTAuth;
use Mockery;
use Tests\BrowserKitTestCase;
use Tests\Helpers\CompanyTokenCreator;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions, CompanyTokenCreator;

    public function testLogin_withoutData()
    {
        $this->createUser();
        $this->post('/auth');

        $this->verifyValidationResponse(['email', 'password']);
    }

    public function testLogin_withMissingPassword()
    {
        $this->createUser();
        $this->post('/auth', [
            'email' => $this->userEmail,
        ]);

        $this->verifyValidationResponse(['password'], ['email']);
    }

    public function testLogin_withInvalidPassword()
    {
        $this->createUser();
        $data = [
            'email' => $this->userEmail,
            'password' => $this->userPassword . 'test',
        ];

        $this->post('/auth', $data);
        $this->verifyErrorResponse(401, ErrorCode::AUTH_INVALID_LOGIN_DATA);
    }

    public function testLogin_withValidPassword()
    {
        $this->createUser();
        $data = [
            'email' => $this->userEmail,
            'password' => $this->userPassword,
        ];

        $this->post('/auth', $data)
            ->seeStatusCode(201)
            ->seeJsonStructure(['data' => ['token']])
            ->isJson();

        // get token and verify if it's valid
        $json = $this->decodeResponseJson();
        $token = $json['data']['token'];
        $this->assertEquals($this->user->id, JWTAuth::setToken($token)->authenticate()->id);

        $this->assertTrue(auth()->check());
    }

    public function testLogin_withValidPasswordWhenUserDeleted()
    {
        $this->createUser(1);
        $data = [
            'email' => $this->userEmail,
            'password' => $this->userPassword,
        ];

        $this->post('/auth', $data);
        $this->verifyErrorResponse(401, ErrorCode::AUTH_INVALID_LOGIN_DATA);

        $this->assertFalse(auth()->check());
    }

    public function testLogin_withValidPasswordWhenUserNotActivated()
    {
        $this->createUser(0, 0);
        $data = [
            'email' => $this->userEmail,
            'password' => $this->userPassword,
        ];

        $this->post('/auth', $data);
        $this->verifyErrorResponse(401, ErrorCode::AUTH_NOT_ACTIVATED);

        $this->assertFalse(auth()->check());
    }

    public function testLogout_whenNotLoggedIn()
    {
        $this->createUser();
        $this->delete('/auth');

        $this->verifyErrorResponse(401, ErrorCode::AUTH_INVALID_TOKEN);
    }

    public function testLogout_whenLoggedIn()
    {
        $this->createUser();
        $token = JWTAuth::fromUser($this->user);

        $this->delete('/auth', [], ['Authorization' => 'Bearer ' . $token])
            ->seeStatusCode(204)
            ->isJson();
    }

    /** @test */
    public function apiToken_it_creates_user_short_token_when_logged_in_via_api_token()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        list($token, $api_token) = $this->createCompanyTokenForUser(
            $this->user,
            $company,
            RoleType::API_USER
        );

        $this->assertFalse(auth()->check());

        $initial_count = UserShortToken::count();

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $this->post('auth/api', [], ['Authorization-Api-Token' => $api_token])
            ->assertResponseStatus(201);

        $this->assertTrue(auth()->check());

        $this->assertSame($initial_count + 1, UserShortToken::count());
        $user_short_token = UserShortToken::latest('id')->first();

        // verify data
        $this->assertSame($token->user_id, $user_short_token->user_id);
        $this->assertTrue(mb_strlen($user_short_token->token) >= 100);
        $this->assertTrue(mb_strlen($user_short_token->token) <= 150);

        $this->assertSame(
            $now->toDateTimeString(),
            $user_short_token->created_at->toDateTimeString()
        );
        $this->assertSame(
            (clone $now)->addMinute(2)->toDateTimeString(),
            $user_short_token->expires_at->toDateTimeString()
        );
        $this->assertNull($user_short_token->deleted_at);

        // verify response structure
        $this->isJson();
        $this->seeJsonStructure([
            'data' => [
                'id',
                'user_id',
                'quick_token',
                'created_at',
                'expires_at',
            ],
        ]);

        // verify response data
        $response_token = $this->decodeResponseJson()['data'];
        $this->assertSame($user_short_token->id, $response_token['id']);
        $this->assertSame($user_short_token->user_id, $response_token['user_id']);
        $this->assertSame(
            $user_short_token->id . '.' . $user_short_token->token,
            $response_token['quick_token']
        );
        $this->assertArrayNotHasKey('token', $response_token);
        $this->assertSame(
            $user_short_token->created_at->toDateTimeString(),
            $response_token['created_at']
        );
        $this->assertSame(
            $user_short_token->expires_at->toDateTimeString(),
            $response_token['expires_at']
        );
    }

    /** @test */
    public function quickToken_it_returns_validation_error_when_no_token_was_sent()
    {
        $this->createUser();
        $this->createCompanyWithRole(RoleType::OWNER);

        $short_user_token = factory(UserShortToken::class)->create(['user_id' => $this->user->id]);

        $this->assertNull($short_user_token->deleted_at);

        $this->post('auth/quick');

        $this->verifyValidationResponse(['token']);
    }

    /** @test */
    public function quickToken_it_returns_404_response_when_invalid_token_used()
    {
        $this->createUser();
        $this->createCompanyWithRole(RoleType::OWNER);

        $short_user_token = factory(UserShortToken::class)->create(['user_id' => $this->user->id]);

        $this->assertNull($short_user_token->deleted_at);

        $this->post('auth/quick', ['token' => 'invalid token']);

        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function quickToken_it_soft_deletes_short_token_when_using_after_expiration_date()
    {
        $this->createUser();
        $this->createCompanyWithRole(RoleType::OWNER);

        $short_user_token = factory(UserShortToken::class)->create(['user_id' => $this->user->id]);

        Carbon::setTestNow(Carbon::now()->addMinutes(5));

        $this->assertNull($short_user_token->deleted_at);

        $this->post('auth/quick', ['token' => $short_user_token->toQuickToken()]);

        // verify data
        $short_user_token = UserShortToken::withTrashed()->find($short_user_token->id);
        $this->assertNotNull($short_user_token->deleted_at);

        // verify response
        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function quickToken_it_cannot_use_deleted_short_token()
    {
        $this->createUser();
        $this->createCompanyWithRole(RoleType::OWNER);

        $short_user_token = factory(UserShortToken::class)->create(['user_id' => $this->user->id]);
        $short_user_token->delete();

        $this->assertNotNull($short_user_token->deleted_at);

        $this->post('auth/quick', ['token' => $short_user_token->toQuickToken()]);

        // verify data
        $short_user_token = UserShortToken::withTrashed()->find($short_user_token->id);
        $this->assertNotNull($short_user_token->deleted_at);

        // verify response
        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function quickToken_it_generates_jwt_token_when_using_valid_short_token()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $short_user_token = factory(UserShortToken::class)->create(['user_id' => $this->user->id]);

        $this->assertNull($short_user_token->deleted_at);

        $this->post('auth/quick', ['token' => $short_user_token->toQuickToken()])
            ->assertResponseStatus(201);

        // verify data
        $short_user_token = UserShortToken::withTrashed()->find($short_user_token->id);
        $this->assertNotNull($short_user_token->deleted_at);

        // verify response data
        $this->isJson();
        $this->seeJsonStructure([
            'data' => [
                'token',
            ],
        ]);

        // verify response data
        $token = $this->decodeResponseJson()['data']['token'];

        $this->assertFalse(auth()->check());
        $this->assertEquals($this->user->id, JWTAuth::setToken($token)->authenticate()->id);
        $this->assertTrue(auth()->check());
        $this->assertSame($this->user->id, auth()->user()->id);
    }

    /** @test */
    public function quickToken_it_doesnt_delete_token_when_exception_was_thrown()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);

        $short_user_token = factory(UserShortToken::class)->create(['user_id' => $this->user->id]);

        $this->assertNull($short_user_token->deleted_at);

        $jwtAuth = Mockery::mock(\Tymon\JWTAuth\JWTAuth::class);
        $jwtAuth->shouldReceive('fromUser')->once()->andThrow(JWTException::class);
        app()->instance(\Tymon\JWTAuth\JWTAuth::class, $jwtAuth);

        $this->post('auth/quick', ['token' => $short_user_token->toQuickToken()])
            ->assertResponseStatus(500);

        // verify data (make sure token was not deleted)
        $short_user_token = UserShortToken::withTrashed()->find($short_user_token->id);
        $this->assertNull($short_user_token->deleted_at);

        // verify response
        $this->verifyErrorResponse(500, ErrorCode::AUTH_CANNOT_CREATE_TOKEN);
    }
}
