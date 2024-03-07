<?php

namespace Tests\Unit\App\Modules\Company\Services;

use App\Models\Db\CompanyToken;
use App\Modules\Company\Exceptions\ExpiredToken;
use App\Modules\Company\Exceptions\InvalidToken;
use App\Modules\Company\Exceptions\NoTokenFound;
use App\Modules\Company\Exceptions\TooShortToken;
use App\Modules\Company\Services\Token;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TokenTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var CompanyToken
     */
    protected $token;

    /**
     * @var Token
     */
    protected $service;

    protected function setUp():void
    {
        parent::setUp();
        $this->token = factory(CompanyToken::class)->create(['ttl' => 30]);
        $this->service = app()->make(Token::class);
    }

    /** @test */
    public function it_encodes_token_with_empty_timestamp_so_it_can_be_decoded()
    {
        $api_token = $this->service->encode($this->token->toApiToken());
        $received_token = $this->service->decode($api_token);
        $this->assertTrue($received_token instanceof CompanyToken);
        $this->assertSame($this->token->id, $received_token->id);
    }

    /** @test */
    public function it_encodes_token_with_empty_timestamp_so_it_can_be_decoded_when_token_is_about_to_expire()
    {
        $timestamp = Carbon::now()->subMinutes(29)->subSeconds(59)->timestamp;
        $api_token = $this->service->encode($this->token->toApiToken(), $timestamp);
        $received_token = $this->service->decode($api_token);
        $this->assertTrue($received_token instanceof CompanyToken);
        $this->assertSame($this->token->id, $received_token->id);
    }

    /** @test */
    public function it_throws_exception_when_decoding_expired_token()
    {
        $timestamp = Carbon::now()->subMinutes(30)->subSeconds(1)->timestamp;
        $api_token = $this->service->encode($this->token->toApiToken(), $timestamp);

        $this->expectException(ExpiredToken::class);
        $this->service->decode($api_token);
    }

    /** @test */
    public function it_throws_exception_when_decoding_completely_invalid_token()
    {
        $this->expectException(InvalidToken::class);
        $this->service->decode('abc');
    }

    /** @test */
    public function it_throws_exception_when_decoding_token_signed_with_different_key()
    {
        $service = new Token(
            app()->make(CompanyToken::class),
            'invalid' . config('services.external_api.key')
        );

        $api_token = $service->encode($this->token);

        $this->expectException(InvalidToken::class);
        $this->service->decode($api_token);
    }

    /** @test */
    public function it_throws_exception_when_using_too_short_key()
    {
        $this->expectException(TooShortToken::class);
        new Token(app()->make(CompanyToken::class), 'short');
    }

    /** @test */
    public function it_throws_exception_when_no_token_record_found_because_of_invalid_id()
    {
        $api_token = $this->service->encode($this->token->id + 5 . '.' . $this->token->token);

        $this->expectException(NoTokenFound::class);
        $this->service->decode($api_token);
    }

    /** @test */
    public function it_throws_exception_when_no_token_record_found_because_of_invalid_token()
    {
        $api_token = $this->service->encode(
            $this->token->id . '.' . $this->token->token . 'something'
        );

        $this->expectException(NoTokenFound::class);
        $this->service->decode($api_token);
    }

    /** @test */
    public function it_throws_exception_when_empty_string_was_send_as_token()
    {
        $api_token = $this->service->encode('');

        $this->expectException(NoTokenFound::class);
        $this->service->decode($api_token);
    }

    /** @test */
    public function it_throws_exception_when_invalid_token_format_was_used()
    {
        $api_token = $this->service->encode('.');

        $this->expectException(NoTokenFound::class);
        $this->service->decode($api_token);
    }
}
