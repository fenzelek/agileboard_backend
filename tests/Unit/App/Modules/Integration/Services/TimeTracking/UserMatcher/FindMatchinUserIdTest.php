<?php

namespace Tests\Unit\App\Modules\Integration\Services\TimeTracking\UserMatcher;

use App\Models\Db\Company;
use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\IntegrationProvider;
use App\Models\Db\User;
use App\Modules\Integration\Services\TimeTracking\UserMatcher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FindMatchinUserIdTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var Company
     */
    protected $company;

    /**
     * @var Integration
     */
    protected $hubstaff_integration;

    /**
     * @inheritdoc
     */
    public function setUp():void
    {
        parent::setUp();

        $this->company = factory(Company::class)->create();

        $this->hubstaff_integration = $this->company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::HUBSTAFF)->id,
        ]);
    }

    /** @test */
    public function it_finds_matching_user_and_returns_its_id()
    {
        $user_matcher = app()->make(UserMatcher::class);

        $email = 'abctest@example.com';

        $user = factory(User::class)->create(['email' => $email, 'id' => 51231]);

        $this->assignUsersToCompany(collect([$user]), $this->company);

        $result = $user_matcher->findMatchingUserId($email, $this->hubstaff_integration);

        $this->assertSame($user->id, $result);
    }

    /** @test */
    public function it_doesnt_find_user_if_its_created_for_different_company()
    {
        $user_matcher = app()->make(UserMatcher::class);

        $email = 'abctest@example.com';

        $user = factory(User::class)->create(['email' => $email, 'id' => 51231]);

        $other_company = factory(Company::class)->create();

        $this->assignUsersToCompany(collect([$user]), $other_company);

        $result = $user_matcher->findMatchingUserId($email, $this->hubstaff_integration);

        $this->assertNull($result);
    }

    /** @test */
    public function it_doesnt_find_user_if_no_user_with_given_email_is_assigned()
    {
        $user_matcher = app()->make(UserMatcher::class);

        $email = 'abctest@example.com';

        $user = factory(User::class)->create(['email' => 'other@example.com', 'id' => 51231]);

        $this->assignUsersToCompany(collect([$user]), $this->company);

        $result = $user_matcher->findMatchingUserId($email, $this->hubstaff_integration);

        $this->assertNull($result);
    }
}
