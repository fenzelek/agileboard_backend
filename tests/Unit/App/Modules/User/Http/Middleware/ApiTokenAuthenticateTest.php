<?php

namespace Tests\Unit\App\Modules\User\Http\Middleware;

use App\Helpers\ErrorCode;
use App\Models\Db\CompanyToken;
use App\Models\Db\Package;
use App\Models\Db\Role;
use App\Models\Other\RoleType;
use App\Modules\Company\Services\Token;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ApiTokenAuthenticateTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_returns_missing_token_response_without_token()
    {
        $this->createUser();
        $response = $this->post('receipts');
        $this->verifyResponseError($response, 401, ErrorCode::AUTH_EXTERNAL_API_MISSING_TOKEN);

        $this->assertFalse(auth()->check());
    }

    /** @test */
    public function it_returns_invalid_token_when_completely_invalid_token_sent()
    {
        $this->createUser();
        $response = $this->post('receipts', [], ['Authorization-Api-Token' => 'abc']);
        $this->verifyResponseError($response, 401, ErrorCode::AUTH_EXTERNAL_INVALID_TOKEN);

        $this->assertFalse(auth()->check());
    }

    /** @test */
    public function it_returns_expired_token_when_expired_token_sent()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        $token = factory(CompanyToken::class)->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'role_id' => Role::findByName(RoleType::API_USER)->id,
            'ttl' => 30,
        ]);

        $expired_timestamp = Carbon::now()->subMinutes(31)->timestamp;

        $token_service = app()->make(Token::class);
        $api_token = $token_service->encode($token->toApiToken(), $expired_timestamp);

        $response = $this->post('receipts', [], ['Authorization-Api-Token' => $api_token]);
        $this->verifyResponseError($response, 401, ErrorCode::AUTH_EXTERNAL_EXPIRED_TOKEN);

        $this->assertFalse(auth()->check());
    }

    /** @test */
    public function it_returns_invalid_token_when_invalid_token_was_sent()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        $token = factory(CompanyToken::class)->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'role_id' => Role::findByName(RoleType::API_USER)->id,
            'ttl' => 30,
        ]);

        $token_service = app()->make(Token::class);
        $api_token = $token_service->encode($token->toApiToken() . 'sthinvalidhere.er');

        $response = $this->post('receipts', [], ['Authorization-Api-Token' => $api_token]);
        $this->verifyResponseError($response, 401, ErrorCode::AUTH_EXTERNAL_INVALID_TOKEN);

        $this->assertFalse(auth()->check());
    }

    /** @test */
    public function it_returns_invalid_token_when_other_key_was_used()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        $token = factory(CompanyToken::class)->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'role_id' => Role::findByName(RoleType::API_USER)->id,
            'ttl' => 30,
        ]);

        $other_key = str_limit(str_repeat('someRandomKey', 10), 31, '');

        $token_service = new Token(app()->make(CompanyToken::class), $other_key);
        $api_token = $token_service->encode($token->toApiToken());

        $response = $this->post('receipts', [], ['Authorization-Api-Token' => $api_token]);
        $this->verifyResponseError($response, 401, ErrorCode::AUTH_EXTERNAL_INVALID_TOKEN);

        $this->assertFalse(auth()->check());
    }

    /** @test */
    public function it_returns_user_was_not_found_if_user_was_deleted()
    {
        $this->createUser(1);
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        $token = factory(CompanyToken::class)->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'role_id' => Role::findByName(RoleType::API_USER)->id,
            'ttl' => 30,
            'domain' => null,
            'ip_from' => null,
            'ip_to' => null,
        ]);

        $token_service = $token_service = app()->make(Token::class);
        $api_token = $token_service->encode($token->toApiToken());

        $response = $this->post('receipts', [], ['Authorization-Api-Token' => $api_token]);
        $this->verifyResponseError($response, 404, ErrorCode::AUTH_USER_NOT_FOUND);

        $this->assertFalse(auth()->check());
    }

    /** @test */
    public function it_returns_user_was_not_activated_if_user_has_not_been_activated_yet()
    {
        $this->createUser(0, 0);
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        $token = factory(CompanyToken::class)->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'role_id' => Role::findByName(RoleType::API_USER)->id,
            'ttl' => 30,
            'domain' => null,
            'ip_from' => null,
            'ip_to' => null,
        ]);

        $token_service = $token_service = app()->make(Token::class);
        $api_token = $token_service->encode($token->toApiToken());

        $response = $this->post('receipts', [], ['Authorization-Api-Token' => $api_token]);
        $this->verifyResponseError($response, 409, ErrorCode::AUTH_NOT_ACTIVATED);

        $this->assertFalse(auth()->check());
    }

    /** @test */
    public function it_return_invalid_token_when_request_from_other_domain()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        $token = factory(CompanyToken::class)->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'role_id' => Role::findByName(RoleType::API_USER)->id,
            'ttl' => 30,
            'domain' => 'example.com',
            'ip_from' => null,
            'ip_to' => null,
        ]);

        $token_service = app()->make(Token::class);
        $api_token = $token_service->encode($token->toApiToken());

        $this->assertFalse(auth()->check());

        $response = $this->post(
            'http://other.example.com/receipts',
            [],
            ['Authorization-Api-Token' => $api_token]
        );

        $this->verifyResponseError($response, 401, ErrorCode::AUTH_EXTERNAL_INVALID_TOKEN);

        $this->assertFalse(auth()->check());
    }

    /** @test */
    public function it_logs_in_user_with_valid_roles()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        $token = factory(CompanyToken::class)->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'role_id' => Role::findByName(RoleType::API_USER)->id,
            'ttl' => 30,
            'domain' => 'example.com',
            'ip_from' => null,
            'ip_to' => null,
        ]);

        $token_service = app()->make(Token::class);
        $api_token = $token_service->encode($token->toApiToken());

        $this->assertFalse(auth()->check());

        $response = $this->post(
            'http://example.com/receipts',
            [],
            ['Authorization-Api-Token' => $api_token]
        );
        $response->assertStatus(422);

        $this->assertTrue(auth()->check());

        $user = auth()->user();
        $this->assertSame($this->user->id, $user->id);
        $this->assertEquals([RoleType::SYSTEM_USER, RoleType::API_USER], $user->getRoles());

        $this->verifyResponseValidation($response, [
            'transaction_number',
            'sale_date',
            'price_net',
            'price_gross',
            'vat_sum',
            'payment_method',
            'number',
            'items',
        ]);
    }

    /** @test */
    public function it_logs_in_user_with_valid_company_api_roles()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        $token = factory(CompanyToken::class)->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'role_id' => Role::findByName(RoleType::API_COMPANY)->id,
            'ttl' => 30,
            'domain' => 'example.com',
            'ip_from' => null,
            'ip_to' => null,
        ]);

        $token_service = app()->make(Token::class);
        $api_token = $token_service->encode($token->toApiToken());

        $this->assertFalse(auth()->check());

        $response = $this->post(
            'http://example.com/online-sales',
            [],
            ['Authorization-Api-Token' => $api_token]
        );
        $response->assertStatus(422);

        $this->assertTrue(auth()->check());

        $user = auth()->user();
        $this->assertSame($this->user->id, $user->id);
        $this->assertEquals([RoleType::SYSTEM_USER, RoleType::API_COMPANY], $user->getRoles());
    }

    /** @test */
    public function it_logs_in_user_using_unexpired_token()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::PREMIUM);
        $token = $this->createToken($company);

        $expired_timestamp = Carbon::now()->subMinutes(31)->timestamp;

        $token_service = app()->make(Token::class);
        $api_token = $token_service->encode($token->toApiToken(), $expired_timestamp);

        $this->assertFalse(auth()->check());

        $response = $this->post(
            'http://example.com/online-sales',
            [],
            ['Authorization-Api-Token' => $api_token]
        );
        $response->assertStatus(422);

        $this->assertTrue(auth()->check());

        $user = auth()->user();
        $this->assertSame($this->user->id, $user->id);
        $this->assertEquals([RoleType::SYSTEM_USER, RoleType::API_COMPANY], $user->getRoles());
    }

    /**
     * @param $company
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|mixed
     */
    private function createToken($company)
    {
        $token = factory(CompanyToken::class)->create([
            'user_id' => $this->user->id,
            'company_id' => $company->id,
            'role_id' => Role::findByName(RoleType::API_COMPANY)->id,
            'ttl' => 30,
            'unexpired' => true,
            'domain' => 'example.com',
            'ip_from' => null,
            'ip_to' => null,
        ]);
        return $token;
    }
}
