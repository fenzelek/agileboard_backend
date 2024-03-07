<?php

namespace Tests\Feature\App\Console\Commands;

use App\Models\Db\File;
use App\Models\Db\Project;
use App\Models\Db\Role;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DeleteTempFilesTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function deleteFiles_success()
    {
        $project = factory(Project::class)->create();
        $user = factory(User::class)->create();

        $role_id = Role::findByName(RoleType::DEVELOPER)->id;

        $file = factory(File::class)->create([
            'project_id' => $project->id,
            'temp' => false,
            'created_at' => Carbon::now()->subHour()->subMinute(),
        ]);

        $file->roles()->attach($role_id);
        $file->users()->attach($user->id);

        $file2 = factory(File::class)->create([
            'project_id' => $project->id,
            'temp' => true,
            'created_at' => Carbon::now(),
        ]);
        $file2->roles()->attach($role_id);
        $file2->users()->attach($user->id);

        $file3 = factory(File::class)->create([
            'project_id' => $project->id,
            'temp' => true,
            'created_at' => Carbon::now()->subHour()->subMinute(),
        ]);
        $file3->roles()->attach($role_id);
        $file3->users()->attach($user->id);

        $count = File::count();

        Artisan::call('file:delete-temp', []);

        $this->assertSame($count - 1, File::count());

        $this->assertTrue($file->fresh() != null);
        $this->assertTrue($file2->fresh() != null);
        $this->assertSame(null, $file3->fresh());
    }
}
