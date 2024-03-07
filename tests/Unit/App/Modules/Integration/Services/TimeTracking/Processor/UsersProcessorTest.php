<?php

namespace Tests\Unit\App\Modules\Integration\Services\TimeTracking\Processor;

use App\Models\Db\Company;
use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\TimeTracking\User as UserModel;
use App\Models\Other\Integration\TimeTracking\User;
use App\Modules\Integration\Services\TimeTracking\Processor\UsersProcessor;
use App\Modules\Integration\Services\TimeTracking\UserMatcher;
use Illuminate\Support\Collection;
use stdClass;
use Tests\BrowserKitTestCase;
use Mockery as m;

class UsersProcessorTest extends BrowserKitTestCase
{
    /** @test */
    public function it_updates_record_when_they_exist_and_creates_when_they_dont_exist()
    {
        $user = m::mock(UserModel::class);
        $user_matcher = m::mock(UserMatcher::class);

        $company = m::mock(Company::class);

        $integration = m::mock(Integration::class)->makePartial();
        $integration->id = 523;
        $integration->company = $company;

        $users = collect([
            new User(150, 'sample@example.com', 'test user 1'),
            new User(178, 'sample2@example.com', 'test user 2'),
            new User(215, 'sample3@example.com', 'test user 3'),
        ]);

        $builder_1 = m::mock(stdClass::class);

        $builder_2 = m::mock(stdClass::class);
        $builder_3 = m::mock(stdClass::class);

        $existing_model_1 = m::mock(UserModel::class)->makePartial();
        $existing_model_1->id = 718;
        $new_model = m::mock(stdClass::class);
        $new_model->id = 1612;
        $existing_model_3 = m::mock(UserModel::class)->makePartial();
        $existing_model_3->id = 211;

        // 1st record should be updated
        $user->shouldReceive('where')->once()->with('integration_id', 523)->andReturn($builder_1);
        $builder_1->shouldReceive('where')->once()->with('external_user_id', 150)
            ->andReturn($builder_1);
        $builder_1->shouldReceive('first')->once()->withNoArgs()->andReturn($existing_model_1);
        $existing_model_1->shouldReceive('update')->once()->with([
            'external_user_email' => 'sample@example.com',
            'external_user_name' => 'test user 1',
        ]);
        $user_matcher->shouldReceive('process')->once()->with(m::on(function ($arg) {
            return $arg instanceof UserModel && $arg->id == 718;
        }));

        // 2nd record should be created
        $user->shouldReceive('where')->once()->with('integration_id', 523)->andReturn($builder_2);
        $builder_2->shouldReceive('where')->once()->with('external_user_id', 178)
            ->andReturn($builder_2);
        $builder_2->shouldReceive('first')->once()->withNoArgs()->andReturn(null);

        $user_matcher->shouldReceive('findMatchingUserId')->once()->with('sample2@example.com', m::on(function ($arg) {
            //return true;
            return $arg instanceof Integration && $arg->id == 523;
        }))->andReturn(8123);

        $user->shouldReceive('create')->once()->with([
            'integration_id' => 523,
            'user_id' => 8123,
            'external_user_id' => 178,
            'external_user_email' => 'sample2@example.com',
            'external_user_name' => 'test user 2',
        ])->andReturn($new_model);

        // 3rd record should be updated
        $user->shouldReceive('where')->once()->with('integration_id', 523)->andReturn($builder_3);
        $builder_3->shouldReceive('where')->once()->with('external_user_id', 215)
            ->andReturn($builder_3);
        $builder_3->shouldReceive('first')->once()->withNoArgs()->andReturn($existing_model_3);
        $existing_model_3->shouldReceive('update')->once()->with([
            'external_user_email' => 'sample3@example.com',
            'external_user_name' => 'test user 3',
        ]);
        $user_matcher->shouldReceive('process')->once()->with(m::on(function ($arg) {
            return $arg instanceof UserModel && $arg->id == 211;
        }));

        $processor = new UsersProcessor($user, $user_matcher);

        $result = $processor->save($integration, $users);

        $this->assertTrue($result instanceof Collection);
        $this->assertCount(3, $result);
        $this->assertSame(718, $result->get(150));
        $this->assertSame(1612, $result->get(178));
        $this->assertSame(211, $result->get(215));
    }
}
