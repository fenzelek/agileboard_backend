<?php

namespace Tests\Unit\App\Models\Db\ProjectPermission\CheckSelection;

use App\Models\Db\Project;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

abstract class ProjectPermissionTest extends TestCase
{
    use DatabaseTransactions;

    protected $test_attribute;

    protected function setUp():void
    {
        parent::setUp();
        if (empty($this->test_attribute)) {
            throw new \Exception('test_attribute must be defined');
        }
    }

    /**
     * @covers \App\Models\Db\ProjectPermission
     * @test
     */
    public function set_to_false_all_permissions_when_all_option_was_unselected()
    {
        $init_data = [
            ['name' => 'all', 'value' => true],
            ['name' => 'reporter', 'value' => true],
            ['name' => 'assigned', 'value' => true],
            ['name' => 'not_assigned', 'value' => true],
        ];
        $updated_data = [
            ['name' => 'all', 'value' => false],
            ['name' => 'reporter', 'value' => true],
            ['name' => 'assigned', 'value' => true],
            ['name' => 'not_assigned', 'value' => true],
        ];
        $expected_data = [
            ['name' => 'all', 'value' => false],
            ['name' => 'reporter', 'value' => false],
            ['name' => 'assigned', 'value' => false],
            ['name' => 'not_assigned', 'value' => false],
        ];

        $project_permission = factory(Project::class)->create()->permission;
        $this->assertEquals($init_data, $project_permission->{$this->test_attribute});

        $project_permission->{$this->test_attribute} = $updated_data;
        $this->assertEquals($project_permission->{$this->test_attribute}, $expected_data);
    }

    /**
     * @covers \App\Models\Db\ProjectPermission
     * @test
     */
    public function set_to_true_all_permissions_when_all_option_was_selected()
    {
        $init_data = [
            ['name' => 'all', 'value' => false],
            ['name' => 'reporter', 'value' => false],
            ['name' => 'assigned', 'value' => false],
            ['name' => 'not_assigned', 'value' => false],
        ];
        $updated_data = [
            ['name' => 'all', 'value' => true],
            ['name' => 'reporter', 'value' => false],
            ['name' => 'assigned', 'value' => false],
            ['name' => 'not_assigned', 'value' => false],
        ];
        $expected_data = [
            ['name' => 'all', 'value' => true],
            ['name' => 'reporter', 'value' => true],
            ['name' => 'assigned', 'value' => true],
            ['name' => 'not_assigned', 'value' => true],
        ];

        $project_permission = factory(Project::class)->create()->permission;
        $project_permission->{$this->test_attribute} = $init_data;
        $project_permission->save();
        $this->assertEquals($init_data, $project_permission->{$this->test_attribute});

        $project_permission->{$this->test_attribute} = $updated_data;
        $this->assertEquals($project_permission->{$this->test_attribute}, $expected_data);
    }

    /**
     * @covers \App\Models\Db\ProjectPermission
     * @test
     */
    public function set_option_all_to_false_when_one_of_other_option_was_turn_off()
    {
        $init_data = [
            ['name' => 'all', 'value' => true],
            ['name' => 'reporter', 'value' => true],
            ['name' => 'assigned', 'value' => true],
            ['name' => 'not_assigned', 'value' => true],
        ];
        $updated_data = [
            ['name' => 'all', 'value' => true],
            ['name' => 'reporter', 'value' => false],
            ['name' => 'assigned', 'value' => true],
            ['name' => 'not_assigned', 'value' => true],
        ];
        $expected_data = [
            ['name' => 'all', 'value' => false],
            ['name' => 'reporter', 'value' => false],
            ['name' => 'assigned', 'value' => true],
            ['name' => 'not_assigned', 'value' => true],
        ];

        $project_permission = factory(Project::class)->create()->permission;
        $project_permission->{$this->test_attribute} = $init_data;
        $project_permission->save();
        $this->assertEquals($init_data, $project_permission->{$this->test_attribute});

        $project_permission->{$this->test_attribute} = $updated_data;
        $this->assertEquals($project_permission->{$this->test_attribute}, $expected_data);
    }

    /**
     * @covers \App\Models\Db\ProjectPermission
     * @test
     */
    public function set_automatically_option_all_to_true_when_all_other_options_are_turn_on()
    {
        $init_data = [
            ['name' => 'all', 'value' => false],
            ['name' => 'reporter', 'value' => false],
            ['name' => 'assigned', 'value' => true],
            ['name' => 'not_assigned', 'value' => true],
        ];
        $updated_data = [
            ['name' => 'all', 'value' => false],
            ['name' => 'reporter', 'value' => true],
            ['name' => 'assigned', 'value' => true],
            ['name' => 'not_assigned', 'value' => true],
        ];
        $expected_data = [
            ['name' => 'all', 'value' => true],
            ['name' => 'reporter', 'value' => true],
            ['name' => 'assigned', 'value' => true],
            ['name' => 'not_assigned', 'value' => true],
        ];

        $project_permission = factory(Project::class)->create()->permission;
        $project_permission->{$this->test_attribute} = $init_data;
        $project_permission->save();
        $this->assertEquals($init_data, $project_permission->{$this->test_attribute});

        $project_permission->{$this->test_attribute} = $updated_data;
        $this->assertEquals($project_permission->{$this->test_attribute}, $expected_data);
    }
}
