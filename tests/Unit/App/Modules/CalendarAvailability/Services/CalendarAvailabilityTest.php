<?php

namespace Tests\Unit\App\Modules\CalendarAvailability\Services;

use App\Models\Db\Role;
use App\Models\Other\RoleType;
use App\Modules\CalendarAvailability\Services\CalendarAvailabilitySorter;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Collection;
use Mockery;
use Carbon\Carbon;
use App\Modules\CalendarAvailability\Services\CalendarAvailability;
use PHPUnit\Framework\TestCase;
use stdClass;

class CalendarAvailabilityTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_calls_eloquent_scopes_to_find_available_users()
    {
        $collection = Mockery::mock(Collection::class);
        $model = Mockery::mock(\App\Models\Db\User::class);
        $guard = Mockery::mock(Guard::class);
        $query = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $role = Mockery::mock(\App\Models\Db\Role::class);
        $user = Mockery::mock(stdClass::class);
        $from = Carbon::parse('2016-02-22');
        $till = Carbon::parse('2016-02-29');
        $sorting_service = Mockery::mock(CalendarAvailabilitySorter::class);
        $companyId = 23;

        $service = new CalendarAvailability($model, $guard, $role, $sorting_service);

        $single_role = Mockery::mock(Role::class);
        $single_role->shouldReceive('getAttribute')->once()->with('id')->andReturn(5555);
        $role->shouldReceive('findByName')->once()->with(RoleType::CLIENT)->andReturn($single_role);

        $model->shouldReceive('newQuery')->once()->andReturn($query);
        $query->shouldReceive('active')->once()->andReturn($query);
        $query->shouldReceive('when')->once()->andReturn($query);
        $query->shouldReceive('allowed')->once()->with(null, [5555])->andReturn($query);
        $guard->shouldReceive('user')->withNoArgs()->once()->andReturn($user);
        $user->shouldReceive('getSelectedCompanyId')->withNoArgs()->once()->andReturn($companyId);
        $query->shouldReceive('withAvailabilities')->with($from, $till, $companyId)->once()
            ->andReturn($query);
        $query->shouldReceive('orderBy')->once()->andReturn($query);
        $query->shouldReceive('get')->once()->andReturn($collection);

        $result = $service->find($from, $till);

        $this->assertSame($collection, $result);
    }

    /** @test */
    public function it_calls_eloquent_scopes_to_findByIds_available_users()
    {
        $collection = Mockery::mock(Collection::class);
        $model = Mockery::mock(\App\Models\Db\User::class);
        $array_ids = [1,2,3];
        $guard = Mockery::mock(Guard::class);
        $query = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $role = Mockery::mock(\App\Models\Db\Role::class);
        $user = Mockery::mock(stdClass::class);
        $from = Carbon::parse('2016-02-22');
        $till = Carbon::parse('2016-02-29');
        $companyId = 23;
        $sorting_service = Mockery::mock(CalendarAvailabilitySorter::class);

        $service = new CalendarAvailability($model, $guard, $role, $sorting_service);

        $single_role = Mockery::mock(Role::class);
        $single_role->shouldReceive('getAttribute')->once()->with('id')->andReturn(5555);
        $role->shouldReceive('findByName')->once()->with(RoleType::CLIENT)->andReturn($single_role);

        $model->shouldReceive('newQuery')->once()->andReturn($query);
        $query->shouldReceive('active')->once()->andReturn($query);
        $query->shouldReceive('byIds')->with($array_ids)->once()->andReturn($query);
        $query->shouldReceive('allowed')->once()->with(null, [5555])->andReturn($query);
        $guard->shouldReceive('user')->withNoArgs()->once()->andReturn($user);
        $user->shouldReceive('getSelectedCompanyId')->withNoArgs()->once()->andReturn($companyId);
        $query->shouldReceive('withAvailabilities')->with($from, $till, $companyId)->once()
            ->andReturn($query);
        $query->shouldReceive('orderBy')->once()->andReturn($query);
        $query->shouldReceive('get')->once()->andReturn($collection);

        $result = $service->findByIds($from, $till, $array_ids);

        $this->assertSame($collection, $result);
    }
}
