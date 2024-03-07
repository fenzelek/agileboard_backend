<?php

namespace Tests\Helpers;

use Storage;
use App\Models\Db\Package;
use App\Models\Db\Role;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use App\Models\Db\Project;
use App\Models\Db\Company;
use App\Models\Db\File as ModelFile;
use App\Models\Other\UserCompanyStatus;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait ProjectHelper
{
    protected $file_settings;

    /**
     * @param string $name
     * @param string $mime
     * @param null $error
     * @param string $realTestFileName
     *
     * @return UploadedFile
     */
    protected function getFile(
        $name = 'test.jpg',
        $mime = 'image/jpeg',
        $error = null,
        $realTestFileName = 'phpunit_test.jpg'
    ) {
        $realFile = storage_path('phpunit_tests/samples/' . $realTestFileName);
        $uploadedLocation = storage_path('phpunit_tests/test/' . $name);
        mkdir(storage_path('phpunit_tests/test/'));
        copy($realFile, $uploadedLocation);

        return new UploadedFile($uploadedLocation, $name, $mime, $error, true);
    }

    /**
     * Assigned user to the company with the role assigned.
     *
     * @param $role_type
     *
     * @return Company
     */
    protected function setCompanyWithRole($role_type)
    {
        $this->company = $this->createCompanyWithRole($role_type, UserCompanyStatus::APPROVED, [], Package::CEP_FREE);

        return $this->company;
    }

    /**
     * Project creation and assign user.
     *
     * @param $company
     * @param $user_id
     *
     * @return Project
     */
    protected function getProject($company, $user_id, $role_type = RoleType::OWNER)
    {
        $project = factory(Project::class)->create([
            'id' => 9999,
            'company_id' => $company->id,
            'name' => 'Test remove project',
            'short_name' => 'trp',
        ]);

        $project->users()->attach($user_id, ['role_id' => Role::findByName($role_type)->id]);

        return $project;
    }

    /**
     * Prepare file for tests.
     *
     * @param $company_id
     * @param string $project_id
     * @param string $file_name
     */
    protected function prepareFile($company_id, $project_id = '9999', $file_name = '1')
    {
        $this->file_settings = [
            'desc_file_path' => storage_path('phpunit_tests/samples/phpunit_test.jpg'),
            'src_path' => $company_id . '/projects/' . $project_id,
            'company_id' => $company_id,
            'src_full_file_path' => storage_path('company/' . $company_id . '/projects/' . $project_id . '/' . $file_name . '.jpg'),
        ];

        Storage::disk('company')->makeDirectory($this->file_settings['src_path']);

        copy($this->file_settings['desc_file_path'], $this->file_settings['src_full_file_path']);
    }

    /**
     * Reverse prepareFile().
     */
    protected function deleteFile()
    {
        if ($this->file_settings && Storage::disk('company')->exists($this->file_settings['company_id'])) {
            Storage::disk('company')->deleteDirectory($this->file_settings['company_id']);
        }
    }

    /**
     * Save the database record associated with the file.
     *
     * @param int $id
     * @param int $project_id
     * @param string $storage_name
     * @param string $role_type
     * @param bool $temp
     * @param string $description
     *
     * @return ModelFile
     */
    protected function prepareFileDatabase(
        $id = 1,
        $project_id = 9999,
        $storage_name = '1.jpg',
        $role_type = RoleType::OWNER,
        $temp = false,
        $description = ''
    ) {
        $file = factory(ModelFile::class)->create([
            'id' => $id,
            'project_id' => $project_id,
            'storage_name' => $storage_name,
            'name' => explode('.', $storage_name)[0],
            'extension' => explode('.', $storage_name)[1],
            'user_id' => $this->user->id,
            'temp' => $temp,
            'description' => $description,
        ]);

        $file->roles()->attach(Role::findByName($role_type)->id);
        $file->users()->attach($this->user->id);

        return $file;
    }

    /**
     * Set role in project for given user.
     *
     * @param Project|null $project
     * @param string $role
     * @param User|null $user
     */
    protected function setProjectRole(
        Project $project = null,
        $role = RoleType::ADMIN,
        User $user = null
    ) {
        $user = $user ?: $this->user;
        $project = $project ?: $this->project;
        $project->users()->detach($user->id);
        $project->users()->attach($user->id, ['role_id' => Role::findByName($role)->id]);
    }

    protected function calculateActivityLevel($tracked, $activity)
    {
        return round(100 * $activity / (float) $tracked, 2);
    }
}
