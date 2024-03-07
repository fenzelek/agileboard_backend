<?php

namespace App\Modules\Project\Services;

use App\Models\Db\Company;
use App\Models\Db\Model;
use App\Models\Db\ModuleMod;
use App\Models\Db\User;
use App\Models\Other\ModuleType;
use Intervention\Image\Facades\Image;
use Illuminate\Http\Request;
use App\Models\Db\Project;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Connection as DB;
use App\Modules\Project\Services\Storage as ServiceStorage;
use App\Models\Db\File as ModelFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Carbon\Carbon;

class File
{
    /**
     * @var Guard
     */
    protected $auth;

    /**
     * @var DB
     */
    protected $db;

    /**
     * @var Storage
     */
    protected $service_storage;

    /**
     * @var ModelFile
     */
    protected $model_file;

    /**
     * @var User
     */
    protected $user;

    /**
     * File constructor.
     *
     * @param Guard $auth
     * @param DB $db
     * @param Storage $service_storage
     * @param ModelFile $model_file
     */
    public function __construct(
        Guard $auth,
        DB $db,
        ServiceStorage $service_storage,
        ModelFile $model_file
    ) {
        $this->user = $auth->user();
        $this->db = $db;
        $this->service_storage = $service_storage;
        $this->model_file = $model_file;
    }

    /**
     *  List of files to which user has access.
     *
     * @param Project $project
     * @param Request $request
     *
     * @return ModelFile
     */
    public function list(Project $project, Request $request)
    {
        $fileable_type = $request->input('fileable_type');
        // For knowledge_pages type correct relation is pages()
        if ($fileable_type == 'knowledge_pages') {
            $fileable_type = 'pages';
        }

        $fileable_id = $request->input('fileable_id');
        $model_file = $this->model_file->assignedToUser($project, $this->user)
            ->with('owner')
            ->where('temp', false);

        //filter by name/description
        if ($request->input('search')) {
            $model_file->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->input('search') . '%')
                    ->orWhere('description', 'LIKE', '%' . $request->input('search') . '%');
            });
        }

        if ($request->input('file_type') && $request->input('file_type') != ModelFile::TYPE_NONE) {
            if ($request->input('file_type') == ModelFile::TYPE_OTHER) {
                $model_file->whereNotIn('extension', ModelFile::getNamedGroupsExtensions());
            } else {
                $model_file->whereIn('extension', ModelFile::types()[$request->input('file_type')]);
            }
        }

        // return all files assigned to the project
        if (empty($fileable_type)) {
            return $model_file;
        }

        // return all files not assigned to any resources
        if ($fileable_type == 'none') {
            return $model_file->whereDoesntHave('resources');
        }

        // return all files assigned to the resource when exists ID resource
        if (! empty($fileable_id)) {
            return $model_file->whereHas($fileable_type, function ($query) use ($fileable_id) {
                $query->where('id', $fileable_id);
            });
        }

        // return all files assigned to the resource
        return $model_file->has($fileable_type);
    }

    /**
     * Add a file to the project.
     *
     * @param Project $project
     * @param Request $request
     *
     * @return ModelFile
     * @throws \Exception
     */
    public function save(Project $project, Request $request)
    {
        // prepare file roles and file users permission
        $file = $request->file;
        $roles = array_unique($request->input('roles', []));
        $users = array_unique($request->input('users', []));
        $tickets = array_unique($request->input('tickets', []));
        $pages = array_unique($request->input('pages', []));
        $stories = array_unique($request->input('stories', []));

        $this->db->beginTransaction();

        try {
            // save records in database
            $file_record = $this->initializeFileRecord($project->id, $this->user->id, $request, $file);
            $file_record->roles()->attach($roles);
            $file_record->users()->attach($users);
            $file_record->tickets()->attach($tickets);
            $file_record->pages()->attach($pages);
            $file_record->stories()->attach($stories);

            // save file storage
            $storage_name = $this->service_storage
                ->save($project->id, $this->user, $file, $file_record->id);

            // update records in database
            $this->updateFileRecord($file_record, $storage_name);
            $this->db->commit();

            return $file_record;
        } catch (\Exception $e) {
            $this->db->rollBack();

            if (isset($storage_name)) {
                $this->service_storage->remove($this->user->getSelectedCompanyId(), $project->id, $storage_name);
            }

            throw $e;
        }
    }

    /**
     * Delete the file from the project.
     *
     * @param Project $project
     * @param ModelFile $file
     * @return mixed
     * @throws \Throwable
     */
    public function remove(Project $project, ModelFile $file)
    {
        return $this->db->transaction(function () use ($project, $file) {
            // remove records in database
            $this->removeFileRecord($file);
            $file->roles()->detach();
            $file->users()->detach();

            // remove file storage
            $this->service_storage->remove($this->user->getSelectedCompanyId(), $project->id, $file->storage_name);

            return true;
        });
    }

    /**
     * Update the file record fields.
     *
     * @param ModelFile $file
     * @param Request $request
     * @return mixed
     * @throws \Throwable
     */
    public function update(ModelFile $file, Request $request)
    {
        $temp = $file->temp;

        $tickets = (array) $request->input('tickets');
        $pages = (array) $request->input('pages');

        if ($temp && (count($tickets) || count($pages))) {
            $temp = false;
        }

        return $this->db->transaction(function () use ($file, $request, $temp, $tickets, $pages) {
            $file->update($request->only('name', 'description'));
            $file->update(['temp' => $temp]);
            $file->roles()->sync((array) $request->input('roles'));
            $file->users()->sync((array) $request->input('users'));
            $file->tickets()->sync($tickets);
            $file->pages()->sync($pages);
            $file->stories()->sync((array) $request->input('stories'));

            return $file;
        });
    }

    /**
     * Download the file assigned to the project.
     *
     * @param Project $project
     * @param ModelFile $file
     *
     * @return \stdClass
     */
    public function download(Project $project, ModelFile $file)
    {
        return $this->service_storage->get($project, $file);
    }

    /**
     * Get thumbunail.
     *
     * @param Project $project
     * @param ModelFile $file
     * @param $width
     * @param $height
     * @return mixed
     */
    public function getThumbnail(Project $project, ModelFile $file, $width, $height)
    {
        $img = Image::make($this->download($project, $file)->root_path);

        if ($width) {
            $img = $img->widen($width);
        } elseif ($height) {
            $img = $img->heighten($height);
        }

        return $img;
    }

    /**
     * @param $company_id
     * @param $file_size
     * @return bool
     */
    public function cantAddFile($company_id, $file_size)
    {
        $setting = Company::findOrFail($company_id)->appSettings(ModuleType::PROJECTS_DISC_VOLUME);

        if ($setting == ModuleMod::UNLIMITED) {
            return false;
        }

        //two times limit
        $setting *= 2;

        $current_volume = ModelFile::wherehas('project', function ($q) use ($company_id) {
            $q->where('company_id', $company_id);
        })->sum('size');

        if ($setting * 1024 * 1024 * 1024 > $current_volume + $file_size) {
            return false;
        }

        return true;
    }

    /**
     * @param Model $source_object
     * @param Model $object
     * @param Project $base_project
     * @param Project $project
     */
    public function cloneFilesInProject(
        Model $source_object,
        Model $object,
        Project $base_project,
        Project $project
    ) {
        if (empty($source_object->files)) {
            return;
        }

        $storage_service = app()->make(Storage::class);

        foreach ($source_object->files as $file) {
            $cloned_file = $file->replicate();
            $cloned_file->storage_name = '';
            $cloned_file->push();

            $project->files()->save($cloned_file);
            $object->files()->attach($cloned_file);

            $storage_name = $storage_service->cloneFile($file, $cloned_file, $base_project, $project);
            $cloned_file->storage_name = $storage_name;
            $cloned_file->save();
        }
    }

    /**
     * Create a new record with the data part of the database in the files table.
     *
     * @param int $project_id
     * @param int $user_id
     * @param Request $request
     * @param UploadedFile $file
     *
     * @return ModelFile
     */
    protected function initializeFileRecord(
        $project_id,
        $user_id,
        Request $request,
        UploadedFile $file
    ) {
        return $this->model_file->create([
            'project_id' => $project_id,
            'user_id' => $user_id,
            'name' => $this->getFileName($file),
            'size' => $file->getSize(),
            'extension' => $file->getClientOriginalExtension(),
            'description' => $request->input('description'),
            'temp' => $request->input('temp', 0),
        ]);
    }

    /**
     * Get file name, modify if "image".
     *
     * @param UploadedFile $file
     *
     * @return string
     */
    protected function getFileName(UploadedFile $file)
    {
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        if ($name === 'image') {
            $name = str_slug($name . '-' . Carbon::now()->format('Y-m-d-h-i-s'));
        }

        return $name;
    }

    /**
     * Save a unique name to the newly created record.
     *
     * @param ModelFile $file_record
     * @param string $storage_name
     *
     * @return bool
     */
    protected function updateFileRecord(ModelFile $file_record, $storage_name)
    {
        return $file_record->update([
            'storage_name' => $storage_name,
        ]);
    }

    /**
     * Remove the record from the database.
     *
     * @param ModelFile $file
     */
    protected function removeFileRecord(ModelFile $file)
    {
        $file->delete();
    }
}
