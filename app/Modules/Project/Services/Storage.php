<?php

namespace App\Modules\Project\Services;

use App\Exceptions\FileException;
use App\Models\Db\Project;
use App\Models\Db\File as ModelFile;
use App\Models\Filesystem\Store as ModelStore;
use App\Models\Filesystem\Extension;
use Illuminate\Filesystem\FilesystemManager as Filesystem;
use stdClass;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Models\Db\User;
use Exception;
use Illuminate\Contracts\Auth\Guard;
use App\Services\Storage as BaseStorage;

class Storage extends BaseStorage
{
    /**
     * @var ModelStore
     */
    protected $model_store;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var stdClass
     */
    protected $data;

    /**
     * Storage constructor.
     *
     * @param Guard $auth
     * @param ModelStore $model_store
     * @param Extension $extension
     * @param Filesystem $filesystem
     */
    public function __construct(
        Guard $auth,
        ModelStore $model_store,
        Extension $extension,
        Filesystem $filesystem
    ) {
        parent::__construct($model_store, $filesystem);

        $this->user = $auth->user();
        $this->extension = $extension;
        $this->data = new stdClass();
    }

    /**
     * @param int $project_id
     * @param User $user
     * @param UploadedFile $file
     * @param string $name
     *
     * @return string
     * @throws Exception
     */
    public function save($project_id, User $user, UploadedFile $file, $name)
    {
        $company_id = $user->getSelectedCompanyId();
        $directory = $this->getPath($company_id, $project_id);

        $storage_name = $this->getFileName('company', $directory, $name, $file, false, true);

        // move file to storage
        $this->putFileAs(
            'company',
            $this->getPath($company_id, $project_id),
            $file,
            $storage_name
        );

        if (! $this->model_store->fileExists(
            'company',
            $this->getPath($company_id, $project_id),
            $storage_name
        )
        ) {
            throw new Exception('Failed to save file');
        }

        return $storage_name;
    }

    public function cloneFile(
        ModelFile $base_file,
        ModelFile $file,
        Project $base_project,
        Project $project
    ) {
        $file_name = $file->id . '.' . $file->extension;
        $base_directory = $this->getPath($base_project->company_id, $base_project->id);
        $directory = $this->getPath($project->company_id, $project->id);

        $this->filesystem->disk('company')
            ->copy($base_directory . $base_file->storage_name, $directory . $file_name);

        return $file_name;
    }

    /**
     * Remove the file from the disk.
     *
     * @param int $company_id
     * @param int $project_id
     * @param string $storage_name
     *
     * @throws Exception
     */
    public function remove($company_id, $project_id, $storage_name)
    {
        if ($this->model_store->fileExists(
            'company',
            $this->getPath($company_id, $project_id),
            $storage_name
        )
        ) {
            $this->deleteFile('company', $this->getPath($company_id, $project_id), $storage_name);
        }
    }

    /**
     * Get file assigned to the project.
     *
     * @param Project $project
     * @param ModelFile $file
     *
     * @return stdClass
     * @throws Exception
     */
    public function get(Project $project, ModelFile $file)
    {
        $directory =
            $this->model_store->getPath($this->user->getSelectedCompanyId(), $project->id) . '/';

        if (! $this->model_store->fileExists('company', $directory, $file->storage_name)) {
            throw new FileException('File does not exist');
        }

        $this->data->root_path = $this->rootPath($directory, $file);
        $this->data->mime_type = $this->mimeType($directory, $file);

        return $this->data;
    }

    /**
     * Get path to the file.
     *
     * @param int $company_id
     * @param int $project_id
     *
     * @return string path file
     */
    protected function getPath($company_id, $project_id)
    {
        return $this->model_store->getPath($company_id, $project_id) . '/';
    }

    /**
     * File root path.
     *
     * @param $directory
     * @param ModelFile $file
     *
     * @return string
     */
    protected function rootPath($directory, ModelFile $file)
    {
        return $this->filesystem->disk('company')->getDriver()->getAdapter()->getPathPrefix() .
            '/' .
            $directory . '/' . $file->storage_name;
    }

    /**
     * File mime type.
     *
     * @param $directory
     * @param ModelFile $file
     *
     * @return string
     */
    protected function mimeType($directory, ModelFile $file)
    {
        return $this->filesystem->disk('company')->mimeType($directory . '/' . $file->storage_name);
    }
}
