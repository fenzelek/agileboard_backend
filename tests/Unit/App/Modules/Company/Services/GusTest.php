<?php

namespace Tests\Unit\App\Modules\Company\Services;

use App\Modules\Company\Services\Gus;
use Illuminate\Support\Collection;
use Mockery as m;
use Tests\TestCase;

class GusTest extends TestCase
{
    private $service;
    private $gus;

    public function setUp():void
    {
        parent::setUp();

        $this->gus = m::mock(\App\Models\Db\GusCompany::class);
        $this->gus->shouldReceive('findByVatin')->andReturn(Collection::make());
        $this->gus->shouldReceive('findAndDestroy')->andReturn(null);

        \Config::set('services.gus_api_user_key', 'gus_api_user_key');

        $this->service = new Gus($this->gus);
    }

    protected function tearDown():void
    {
        m::close();
        parent::tearDown();
    }

    /** @test */
    public function getDataByVatim_return_false_if_not_found_company_by_vatin()
    {
        $this->assertFalse($this->service->getDataByVatin('123'));
    }
}
