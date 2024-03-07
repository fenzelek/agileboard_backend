<?php

namespace Tests\Unit\App\Models;

use App\Models\Db\Company;
use App\Models\Db\Contractor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FullVatinTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_test_getting_full_vatin_when_prefix_not_empty()
    {
        factory(Company::class)->create([
            'country_vatin_prefix_id' => 1,
            'vatin' => 123456789,
        ]);
        factory(Contractor::class)->create([
            'country_vatin_prefix_id' => 2,
            'vatin' => 789456123,
        ]);

        $this->assertEquals('AF123456789', Company::first()->full_vatin);
        $this->assertEquals('AL789456123', Contractor::first()->full_vatin);
    }

    /** @test */
    public function it_test_getting_full_vatin_when_prefix_empty()
    {
        factory(Company::class)->create([
            'country_vatin_prefix_id' => null,
            'vatin' => 123456789,
        ]);
        factory(Contractor::class)->create([
            'country_vatin_prefix_id' => null,
            'vatin' => 789456123,
        ]);

        $this->assertEquals('123456789', Company::first()->full_vatin);
        $this->assertEquals('789456123', Contractor::first()->full_vatin);
    }
}
