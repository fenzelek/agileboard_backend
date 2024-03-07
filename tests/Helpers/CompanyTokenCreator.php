<?php

namespace Tests\Helpers;

use App\Models\Db\Company;
use App\Models\Db\CompanyToken;
use App\Models\Db\Role;
use App\Models\Db\User;
use App\Modules\Company\Services\Token;

trait CompanyTokenCreator
{
    /**
     * Create API token for given user in given company.
     *
     * @param User $user
     * @param Company $company
     * @param string $company_token_role_slug
     *
     * @return array
     */
    protected function createCompanyTokenForUser(User $user, Company $company, $company_token_role_slug)
    {
        $token = factory(CompanyToken::class)->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'role_id' => Role::findByName($company_token_role_slug)->id,
            'ttl' => 30,
            'domain' => null,
            'ip_from' => null,
            'ip_to' => null,
        ]);
        $token_service = app()->make(Token::class);

        $api_token = $token_service->encode($token->toApiToken());

        return [$token, $api_token];
    }
}
