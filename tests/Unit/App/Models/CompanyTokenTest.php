<?php

namespace Tests\Unit\App\Models;

use App\Models\Db\CompanyToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CompanyTokenTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function toApiToken_it_returns_valid_api_token()
    {
        $token = factory(CompanyToken::class)->create([
            'token' => 'sample token',
        ]);

        $this->assertSame($token->id . '.sample token', $token->toApiToken());
    }

    /** @test */
    public function validForServer_it_returns_true_when_no_constrains_set()
    {
        $token = factory(CompanyToken::class)->make([
            'domain' => null,
            'ip_from' => null,
            'ip_to' => null,
        ]);

        $this->assertTrue($token->validForServer('example.com', '123.23.12.13'));
    }

    /** @test */
    public function validForServer_it_returns_true_when_valid_domain_used()
    {
        $token = factory(CompanyToken::class)->make([
            'domain' => 'example.com',
            'ip_from' => null,
            'ip_to' => null,
        ]);

        $this->assertTrue($token->validForServer('example.com', '123.23.12.13'));
    }

    /** @test */
    public function validForServer_it_returns_false_when_invalid_domain_used()
    {
        $token = factory(CompanyToken::class)->make([
            'domain' => 'example.com',
            'ip_from' => null,
            'ip_to' => null,
        ]);

        $this->assertFalse($token->validForServer('example-other.com', '123.23.12.13'));
    }

    /** @test */
    public function validForServer_it_returns_true_when_valid_ip_used()
    {
        $token = factory(CompanyToken::class)->make([
            'domain' => null,
            'ip_from' => '123.23.12.13',
            'ip_to' => null,
        ]);

        $this->assertTrue($token->validForServer('example.com', '123.23.12.13'));
    }

    /** @test */
    public function validForServer_it_returns_true_when_valid_ip_used_for_ip_range()
    {
        $token = factory(CompanyToken::class)->make([
            'domain' => null,
            'ip_from' => '123.23.12.13',
            'ip_to' => '123.23.12.16',
        ]);

        $this->assertTrue($token->validForServer('example.com', '123.23.12.14'));
    }

    /** @test */
    public function validForServer_it_returns_false_when_invalid_ip_used()
    {
        $token = factory(CompanyToken::class)->make([
            'domain' => null,
            'ip_from' => '123.23.12.13',
            'ip_to' => null,
        ]);

        $this->assertFalse($token->validForServer('example.com', '123.23.12.17'));
    }

    /** @test */
    public function validForServer_it_returns_false_when_invalid_ip_used_for_ip_range()
    {
        $token = factory(CompanyToken::class)->make([
            'domain' => null,
            'ip_from' => '123.23.12.13',
            'ip_to' => '123.23.12.16',
        ]);

        $this->assertFalse($token->validForServer('example.com', '123.23.12.17'));
    }

    /** @test */
    public function validForServer_it_returns_true_when_valid_domain_and_ip_used()
    {
        $token = factory(CompanyToken::class)->make([
            'domain' => 'example.com',
            'ip_from' => '123.23.12.13',
            'ip_to' => null,
        ]);

        $this->assertTrue($token->validForServer('example.com', '123.23.12.13'));
    }

    /** @test */
    public function validForServer_it_returns_true_when_valid_domain_and_ip_used_for_ip_range()
    {
        $token = factory(CompanyToken::class)->make([
            'domain' => 'example.com',
            'ip_from' => '123.23.12.13',
            'ip_to' => '123.23.12.15',
        ]);

        $this->assertTrue($token->validForServer('example.com', '123.23.12.15'));
    }

    /** @test */
    public function validForServer_it_returns_false_when_invalid_domain_used_for_domain_and_ip_constraint()
    {
        $token = factory(CompanyToken::class)->make([
            'domain' => 'example.com',
            'ip_from' => '123.23.12.13',
            'ip_to' => '123.23.12.15',
        ]);

        $this->assertFalse($token->validForServer('example2.com', '123.23.12.15'));
    }

    /** @test */
    public function validForServer_it_returns_false_when_invalid_ip_used_for_domain_and_ip_constraint()
    {
        $token = factory(CompanyToken::class)->make([
            'domain' => 'example.com',
            'ip_from' => '123.23.12.13',
            'ip_to' => '123.23.12.15',
        ]);

        $this->assertFalse($token->validForServer('example.com', '123.23.12.16'));
    }
}
