<?php

namespace Tests\Unit\App\Modules\Company\Services;

use App\Modules\Company\Services\BankAccountService;
use App\Modules\Company\Services\Company;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class CompanyTest extends TestCase
{
    public function setUp():void
    {
        parent::setUp();

        $this->app = m::mock(\Illuminate\Contracts\Foundation\Application::class);
        $this->user = m::mock(\App\Models\Db\User::class);
        $this->guard = m::mock(\Illuminate\Contracts\Auth\Guard::class);
        $this->company = m::mock(\App\Models\Db\Company::class);
        $this->userCompany = m::mock(\App\Models\Db\UserCompany::class);
        $this->role = m::mock(\App\Models\Db\Role::class);
        $this->package = m::mock(\App\Models\Db\Package::class);
        $this->filesystem = m::mock(\Illuminate\Filesystem\FilesystemManager::class);
        $this->db = m::mock(\Illuminate\Database\Connection::class);
        $this->bank_account_service = m::mock(BankAccountService::class);

        $this->guard->shouldReceive('user')->once()->andReturn($this->user);

        $this->service = new Company(
            $this->app,
            $this->guard,
            $this->company,
            $this->userCompany,
            $this->role,
            $this->package,
            $this->filesystem,
            $this->db,
            $this->bank_account_service
        );
    }

    protected function tearDown():void
    {
        m::close();
        parent::tearDown();
    }

    /** @test */
    public function canCreate_returns_true_when_does_not_own_a_company()
    {
        $collection = m::mock(\Illuminate\Database\Eloquent\Collection::class);
        $collection->shouldReceive('first')->once()->andReturn(null);

        $this->user->shouldReceive('ownedCompanies')->once()
            ->andReturn($collection);

        $this->assertEquals(true, $this->service->canCreate());
    }

    /** @test */
    public function canCreate_returns_false_when_owns_a_company()
    {
        $collection = m::mock(\Illuminate\Database\Eloquent\Collection::class);

        // return only not null value - doesn't matter what exactly
        $collection->shouldReceive('first')->once()->andReturn(2);

        $this->user->shouldReceive('ownedCompanies')->once()
            ->andReturn($collection);

        $this->assertEquals(false, $this->service->canCreate());
    }
}
