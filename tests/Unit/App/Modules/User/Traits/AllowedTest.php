<?php

namespace Tests\Unit\App\Modules\User\Traits;

use App\Models\Db\Model;
use App\Models\Other\UserCompanyStatus;
use Mockery;
use PHPUnit\Framework\TestCase;
use stdClass;
use App\Modules\User\Traits\Allowed;

class AllowedTest extends TestCase
{
    protected function tearDown():void
    {
        Mockery::close();
    }

    /** @test */
    public function zero_results_for_non_existing_user()
    {
        // Arrange
        $model = new class() {
            use Allowed;

            public static function find($id)
            {
                return;
            }
        };
        $query = Mockery::mock(stdClass::class);
        // Assert (set expectations)
        $query->shouldReceive('whereRaw')->once()->with('1 = 0')->andReturn($query);

        // Act and Assert
        $this->assertSame($query, $model->scopeAllowed($query, 'wrong_id'));
    }

    /** @test */
    public function zero_results_when_no_selected_company()
    {
        // Arrange
        $model = new class() {
            use Allowed;

            public static function find($id)
            {
                return;
            }
        };
        $query = Mockery::mock(stdClass::class);
        $user = Mockery::mock(Model::class);
        // Assert (expect)
        $user->shouldReceive('getSelectedCompanyId')->once()->andReturn(null);

        // Assert (set expectations)
        $query->shouldReceive('whereRaw')->once()->with('1 = 0')->andReturn($query);

        // Act and Assert
        $this->assertSame($query, $model->scopeAllowed($query, $user));
    }

    /** @test */
    public function dont_filter_full_for_admin_user()
    {
        // Arrange
        $model = new class() {
            use Allowed;
        };
        $query = Mockery::mock(stdClass::class);
        $admin = Mockery::mock(Model::class);
        // Assert (expect)
        $admin->shouldReceive('getSelectedCompanyId')->twice()->andReturn(5);

        $nestedWhere = Mockery::on(function ($callback) use ($query) {
            $result = call_user_func($callback, $query);

            return true;
        });

        $query->shouldReceive('whereHas')->with('companies', $nestedWhere)->once()
            ->andReturn($query);

        $query->shouldReceive('where')->with('companies.id', 5)->once()->andReturn($query);
        $query->shouldReceive('where')->with('status', UserCompanyStatus::APPROVED)->once()
            ->andReturn($query);
        $admin->shouldReceive('isAdmin')->once()->andReturn(true);

        // Act and Assert
        $this->assertSame($query, $model->scopeAllowed($query, $admin));
    }

    /** @test */
    public function dont_filter_full_for_owner_user()
    {
        // Arrange
        $model = new class() {
            use Allowed;
        };
        $query = Mockery::mock(stdClass::class);
        $admin = Mockery::mock(Model::class);
        // Assert (expect)
        $admin->shouldReceive('getSelectedCompanyId')->twice()->andReturn(5);

        $nestedWhere = Mockery::on(function ($callback) use ($query) {
            $result = call_user_func($callback, $query);

            return true;
        });

        $query->shouldReceive('whereHas')->with('companies', $nestedWhere)->once()
            ->andReturn($query);

        $query->shouldReceive('where')->with('companies.id', 5)->once()->andReturn($query);
        $query->shouldReceive('where')->with('status', UserCompanyStatus::APPROVED)->once()
            ->andReturn($query);
        $admin->shouldReceive('isAdmin')->once()->andReturn(false);
        $admin->shouldReceive('isOwner')->once()->andReturn(true);

        // Act and Assert
        $this->assertSame($query, $model->scopeAllowed($query, $admin));
    }

    /** @test */
    public function dont_filter_full_for_system_admin_user()
    {
        // Arrange
        $model = new class() {
            use Allowed;
        };
        $query = Mockery::mock(stdClass::class);
        $admin = Mockery::mock(Model::class);
        // Assert (expect)
        $admin->shouldReceive('getSelectedCompanyId')->twice()->andReturn(5);

        $nestedWhere = Mockery::on(function ($callback) use ($query) {
            call_user_func($callback, $query);

            return true;
        });

        $query->shouldReceive('whereHas')->with('companies', $nestedWhere)->once()
            ->andReturn($query);

        $query->shouldReceive('where')->with('companies.id', 5)->once()->andReturn($query);
        $query->shouldReceive('where')->with('status', UserCompanyStatus::APPROVED)->once()
            ->andReturn($query);
        $admin->shouldReceive('isAdmin')->once()->andReturn(false);
        $admin->shouldReceive('isOwner')->once()->andReturn(false);
        $admin->shouldReceive('isSystemAdmin')->once()->andReturn(true);

        // Act and Assert
        $this->assertSame($query, $model->scopeAllowed($query, $admin));
    }

    /** @test */
    public function apply_filters_for_ordinary_user()
    {
        // Arrange and expect
        $model = new class() {
            use Allowed;
        };
        $user = Mockery::mock(Model::class);
        $user->shouldReceive('isAdmin')->once()->andReturn(false);
        $user->shouldReceive('isOwner')->once()->andReturn(false);
        $user->shouldReceive('isSystemAdmin')->once()->andReturn(false);
        $user->shouldReceive('getAttribute')->times(2)->with('id')->andReturn('user_id');
        $user->shouldReceive('getSelectedCompanyId')->times(2)->andReturn(5);

        $query = Mockery::mock('StdClass');

        // these 2 are tricky - due to the fact that php never ever
        // evaluates Closure == Closure to true, we need to work
        // it around, with Mockery::on calls for expectations.
        $nestedWhere = Mockery::on(function ($callback) use ($query) {
            call_user_func($callback, $query);

            return true;
        });
        $orWhereHas = Mockery::on(function ($callback) use ($query) {
            call_user_func($callback, $query);

            return true;
        });

        $innerWhere = Mockery::on(function ($callback) use ($query) {
            call_user_func($callback, $query);

            return true;
        });

        // here are the lines from trait that are tested:
        $nestedCompanyWhere = Mockery::on(function ($callback) use ($query) {
            call_user_func($callback, $query);

            return true;
        });

        // $query->whereHas('companies', function ($q) use ($user) {
        $query->shouldReceive('whereHas')->with('companies', $nestedCompanyWhere)->once()
            ->andReturn($query);
        $query->shouldReceive('where')->with('status', UserCompanyStatus::APPROVED)->once()
            ->andReturn($query);
        // $q->where('companies.id', $user->getSelectedCompanyId());
        $query->shouldReceive('where')->with('companies.id', 5)->once()->andReturn($query);

        // return $query->where(function ($q) use ($user) {
        $query->shouldReceive('where')->with($nestedWhere)->once()->andReturn($query);
        // $q->where('id', $user->id)
        $query->shouldReceive('where')->with('id', 'user_id')->once()->andReturn($query);
        // ->orWhereHas('projects', function ($q) use ($user) {
        $query->shouldReceive('orWhereHas')->with('projects', $orWhereHas)->once()
            ->andReturn($query);
        // $q->inCompany($user)
        $query->shouldReceive('inCompany')->once()->andReturn($query);
        // ->whereHas('users', function ($q) use ($user) {
        $query->shouldReceive('whereHas')->with('users', $innerWhere)->once()->andReturn($query);
        // $q->where('project_user.user_id', $user->id);
        $query->shouldReceive('where')->with('project_user.user_id', 'user_id')->once()
            ->andReturn($query);

        $this->assertSame($query, $model->scopeAllowed($query, $user));
    }
}
