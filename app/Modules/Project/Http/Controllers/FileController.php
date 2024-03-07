<?php

namespace App\Modules\Project\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use App\Http\Resources\FileKnowledgePage;
use App\Http\Resources\FileRole;
use App\Http\Resources\FileStory;
use App\Http\Resources\FileUser;
use App\Http\Resources\Story as TransformerStory;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Http\Resources\TicketShort;
use App\Models\Db\Project;
use App\Models\Db\File;
use App\Models\Db\Role;
use App\Models\Db\Story;
use App\Models\Db\Ticket;
use App\Models\Db\User;
use App\Modules\Project\Http\Requests\FileDownload;
use App\Modules\Project\Services\File as ServiceFile;
use App\Modules\Project\Http\Requests\FileIndex as RequestsFileIndex;
use App\Modules\Project\Http\Requests\FileStore as RequestsFileStore;
use App\Modules\Project\Http\Requests\FileUpdate as RequestsFileUpdate;
use App\Services\Paginator;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;

class FileController extends Controller
{
    /**
     * Takes the list of files to which user has access.
     *
     * @param Project $project
     * @param RequestsFileIndex $request
     * @param ServiceFile $service
     * @param Paginator $paginator
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(
        Project $project,
        RequestsFileIndex $request,
        ServiceFile $service,
        Paginator $paginator
    ) {
        $files_query = $service->list($project, $request);
        $files = $paginator->get($files_query, 'project-file.index', ['project' => $project]);

        return ApiResponse::transResponseOk($files, 200, [
            User::class => FileUser::class,
        ]);
    }

    /**
     * Add a file to the project.
     *
     * @param Project $project
     * @param Guard $auth
     * @param RequestsFileStore $request
     * @param ServiceFile $service
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function store(Project $project, Guard $auth, RequestsFileStore $request, ServiceFile $service)
    {
        if ($service->cantAddFile($auth->user()->getSelectedCompanyId(), $request->file->getSize())) {
            return ApiResponse::responseError(ErrorCode::PACKAGE_LIMIT_REACHED, 409);
        }

        $file = $service->save($project, $request);

        return ApiResponse::transResponseOk(
            $file->load('roles', 'users', 'pages', 'stories', 'tickets'),
            201,
            [
                Role::class => FileRole::class,
                User::class => FileUser::class,
                KnowledgePage::class => FileKnowledgePage::class,
                Story::class => FileStory::class,
                Ticket::class => TicketShort::class,
            ]
        );
    }

    /**
     * Delete the file from the project.
     *
     * @param Project $project
     * @param File $file
     * @param ServiceFile $service
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function destroy(Project $project, File $file, ServiceFile $service)
    {
        if ($service->remove($project, $file) === true) {
            return ApiResponse::responseOk([], 204);
        }

        return ApiResponse::responseError(ErrorCode::DATABASE_ERROR, 500);
    }

    /**
     * Update the file record fields.
     *
     * @param Project $project
     * @param File $file
     * @param RequestsFileUpdate $request
     * @param ServiceFile $service
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function update(
        Project $project,
        File $file,
        RequestsFileUpdate $request,
        ServiceFile $service
    ) {
        $file = $service->update($file, $request);

        return ApiResponse::transResponseOk($file->load(
            'roles',
            'users',
            'pages',
            'stories',
            'tickets'
        ), 200, [
            Role::class => FileRole::class,
            User::class => FileUser::class,
            KnowledgePage::class => FileKnowledgePage::class,
            Story::class => FileStory::class,
            Ticket::class => TicketShort::class,
        ]);
    }

    /**
     * Display the file with relations from the project.
     *
     * @param Project $project
     * @param File $file
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Project $project, File $file)
    {
        return ApiResponse::transResponseOk($file->load(
            'roles',
            'users',
            'pages',
            'stories',
            'tickets',
            'owner'
        ), 200, [
            Role::class => FileRole::class,
            User::class => FileUser::class,
            KnowledgePage::class => FileKnowledgePage::class,
            Story::class => TransformerStory::class,
            Ticket::class => TicketShort::class,
        ]);
    }

    /**
     * Download the file assigned to the project.
     *
     * @param Project $project
     * @param File $file
     * @param FileDownload $request
     * @param ServiceFile $service
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download(Project $project, File $file, FileDownload $request, ServiceFile $service)
    {
        if (in_array($file->extension, File::types()['images']) &&
            ($request->input('width') || $request->input('height'))) {
            return $service->getThumbnail(
                $project,
                $file,
                $request->input('width'),
                $request->input('height')
            )->response();
        }

        $data = $service->download($project, $file);

        $name = Str::ascii($file->name) . '.' . $file->extension;
        $headers = ['Content-Type: ' . $data->mime_type];

        return response()->download($data->root_path, $name, $headers);
    }

    /**
     * Return types list.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function types()
    {
        return ApiResponse::responseOk(File::types());
    }
}
