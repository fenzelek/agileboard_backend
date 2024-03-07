<?php

namespace  Tests\Feature\App\Modules\Company\Http\Controllers;

use App\Helpers\ErrorCode;
use App\Models\Db\Company;
use App\Models\Db\CompanyToken;
use App\Models\Db\Role;
use App\Models\Other\RoleType;
use App\Models\Db\User;
use App\Models\Other\UserCompanyStatus;
use App\Modules\Company\Services\Token;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;

class TokenControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function store_it_returns_validation_error_with_empty_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->post('companies/tokens?selected_company_id=' . $company->id, [
            'user_id' => '',
            'role_id' => '',
            'ttl' => '',
            'domain' => '',
            'ip_from' => '',
            'ip_to' => '',
        ]);

        $this->verifyValidationResponse([
            'user_id',
            'role_id',
            'ttl',
        ], [
            'domain',
            'ip_from',
            'ip_to',
        ]);
    }

    /** @test */
    public function store_it_returns_validation_error_with_invalid_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $user = factory(User::class)->create();
        $this->assignUsersToCompany(
            collect([$user]),
            $company,
            RoleType::DEVELOPER,
            UserCompanyStatus::REFUSED
        );

        $this->post('companies/tokens?selected_company_id=' . $company->id, [
            'user_id' => $user->id,
            'role_id' => RoleType::API_USER,
            'ttl' => 50,
            'ip_from' => '123.456.789.012',
            'domain' => '',
            'ip_to' => '',
        ]);

        $this->verifyValidationResponse([
            'user_id',
            'role_id',
        ], [
            'ttl',
            'domain',
            'ip_from',
            'ip_to',
        ]);
    }

    /** @test */
    public function store_it_saves_valid_token()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $tokens_count = CompanyToken::count();

        $user = factory(User::class)->create();
        $this->assignUsersToCompany(
            collect([$user]),
            $company,
            RoleType::DEVELOPER,
            UserCompanyStatus::APPROVED
        );

        $data = [
            'user_id' => $user->id,
            'role_id' => Role::findByName(RoleType::API_USER)->id,
            'ttl' => 50,
            'ip_from' => '123.456.789.012',
            'domain' => '',
            'ip_to' => '',
        ];

        $this->post('companies/tokens?selected_company_id=' . $company->id, $data)
            ->assertResponseOk();

        $this->assertSame($tokens_count + 1, CompanyToken::count());

        $token = CompanyToken::latest('id')->first();

        // verify data
        $this->assertSame($company->id, $token->company_id);
        $this->assertSame($data['user_id'], $token->user_id);
        $this->assertSame($data['role_id'], $token->role_id);
        $this->assertTrue(mb_strlen($token->token) >= 200);
        $this->assertTrue(mb_strlen($token->token) <= 255);
        $this->assertNull($token->domain);
        $this->assertSame($data['ip_from'], $token->ip_from);
        $this->assertNull($token->ip_to);
        $this->assertSame($data['ttl'], $token->ttl);

        // verify response structure
        $this->seeJsonStructure([
            'data' => [
                'id',
                'company_id',
                'user_id',
                'role_id',
                'api_token',
                'domain',
                'ip_from',
                'ip_to',
            ],
        ]);
        // make sure token is not explicitly returned
        $this->dontSeeJson(['token' => $token->token]);

        // verify response data
        $response_data = $this->decodeResponseJson()['data'];
        collect([
            'id',
            'company_id',
            'user_id',
            'role_id',
            'domain',
            'ip_from',
            'ip_to',
            'ttl',
        ])->each(function ($field) use ($token, $response_data) {
            $this->assertSame($token->$field, $response_data[$field]);
        });
        $this->assertArrayNotHasKey('token', $response_data);
        $this->assertSame($token->toApiToken(), app(Token::class)->decode($response_data['api_token'])->toApiToken());
    }

    /** @test */
    public function index_get_all_tokens_without_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $tokens = factory(CompanyToken::class, 2)->create([
            'company_id' => $company->id,
            'user_id' => 15,
        ]);
        $tokens = $tokens->merge(factory(CompanyToken::class, 2)->create([
            'company_id' => $company->id,
            'user_id' => 20,
        ]));

        $other_company = factory(Company::class)->create();
        $other_company_tokens = factory(CompanyToken::class, 3)
            ->create(['company_id' => $other_company->id]);

        $this->get('/companies/tokens?selected_company_id=' . $company->id)->assertResponseOk();

        // verify structure
        $this->verifyTokensStructure();

        // verify data
        $this->verifyTokensInResponse($tokens);
    }

    /** @test */
    public function index_get_selected_tokens_with_user_id_filter()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $user_15_tokens = factory(CompanyToken::class, 2)->create([
            'company_id' => $company->id,
            'user_id' => 15,
        ]);
        $tokens = $user_15_tokens->merge(factory(CompanyToken::class, 2)->create([
            'company_id' => $company->id,
            'user_id' => 20,
        ]));

        $other_company = factory(Company::class)->create();
        $other_company_tokens = factory(CompanyToken::class, 3)
            ->create(['company_id' => $other_company->id]);

        $this->get('/companies/tokens?selected_company_id=' . $company->id . '&user_id=15')
            ->assertResponseOk();

        // verify structure
        $this->verifyTokensStructure();

        // verify data
        $this->verifyTokensInResponse($user_15_tokens);
    }

    /** @test */
    public function destroy_it_soft_deletes_token()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $token = factory(CompanyToken::class)->create(['company_id' => $company->id]);

        $this->assertNull($token->fresh()->deleted_at);

        $this->delete('companies/tokens/' . $token->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(204);

        $this->assertNotNull($token->withTrashed()->find($token->id)->deleted_at);
        $this->assertEmpty($this->response->getContent());
    }

    /** @test */
    public function destroy_it_doesnt_allow_to_remove_other_company_token()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $token = factory(CompanyToken::class)->create(['company_id' => $company->id + 15]);

        $this->assertNull($token->fresh()->deleted_at);

        $this->delete('companies/tokens/' . $token->id . '?selected_company_id=' . $company->id);

        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);

        $this->assertNull($token->withTrashed()->find($token->id)->deleted_at);
    }

    protected function verifyTokensStructure()
    {
        $this->seeJsonStructure([
            'data' => [
                [
                    'id',
                    'company_id',
                    'user_id',
                    'role_id',
                    'api_token',
                    'domain',
                    'ip_from',
                    'ip_to',
                    'ttl',
                    'role' => [
                        'data' => [
                            'id',
                            'name',
                            'default',
                        ],
                    ],
                ],
            ],
            'meta' => [
                'pagination' => [
                    'total',
                    'count',
                    'per_page',
                    'current_page',
                    'total_pages',
                    'links',
                ],
            ],
        ]);
    }

    protected function verifyTokensInResponse($expected_tokens)
    {
        $response_data = $this->decodeResponseJson()['data'];

        $this->assertSame($expected_tokens->count(), count($response_data));

        $fields = collect([
            'id',
            'company_id',
            'user_id',
            'role_id',
            'domain',
            'ip_from',
            'ip_to',
            'ttl',
        ]);

        $expected_tokens->each(function ($token, $key) use ($response_data, $fields) {
            $fields->each(function ($field) use ($token, $response_data, $key) {
                $this->assertSame(
                    $token->$field,
                    $response_data[$key][$field],
                    'Valid value for field ' . $field
                );
                $this->assertSame([
                    'id' => $token->role_id,
                    'name' => $token->role->name,
                    'default' => $token->role->default,
                ], $response_data[$key]['role']['data']);
            });
            $this->assertArrayNotHasKey('token', $response_data[$key]);
            $this->assertSame($token->toApiToken(), app(Token::class)->decode($response_data[$key]['api_token'])->toApiToken());
        });
    }
}
