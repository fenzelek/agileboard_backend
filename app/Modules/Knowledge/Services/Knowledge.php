<?php

namespace App\Modules\Knowledge\Services;

use App\Models\Db\Knowledge\KnowledgeDirectory;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\Project;
use App\Models\Db\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Knowledge
{
    /**
     * Knowledge page class.
     *
     * @var KnowledgePage
     */
    protected $knowledge_page;
    protected $knowledge_directory;

    /**
     * Knowledge constructor.
     *
     * @param KnowledgePage $knowledge_page
     * @param KnowledgeDirectory $knowledge_directory
     */
    public function __construct(
        KnowledgePage $knowledge_page,
        KnowledgeDirectory $knowledge_directory
    ) {
        $this->knowledge_page = $knowledge_page;
        $this->knowledge_directory = $knowledge_directory;
    }

    /**
     * It creates page and attach users and/or roles permissions.
     *
     * @param Request $request
     * @param Project $project
     * @param User $user
     *
     * @return KnowledgePage $page
     */
    public function createPage(Request $request, Project $project, User $user): KnowledgePage
    {
        $knowledge_page = $this->knowledge_page;

        return DB::transaction(function () use ($request, $project, $user, $knowledge_page) {
            $page = $knowledge_page->create([
                    'project_id' => $project->id,
                    'creator_id' => $user->id,
                    'knowledge_directory_id' => $request->input('knowledge_directory_id'),
                ] + $request->only(['name', 'content', 'pinned']));

            $page->users()->attach($request->input('users'));
            $page->roles()->attach($request->input('roles'));
            $page->stories()->attach($request->input('stories'));

            $page->load('users', 'roles');

            return $page;
        });
    }

    /**
     * It updates page and edit users and/or roles permissions.
     *
     * @param Request $request
     * @param KnowledgePage $page
     * @param User $user
     *
     * @return KnowledgePage $page
     */
    public function updatePage(Request $request, KnowledgePage $page, User $user)
    {
        $page->update($request->only(['name', 'content', 'knowledge_directory_id', 'pinned']));

        $page->users()->sync((array) $request->input('users'));
        $page->roles()->sync((array) $request->input('roles'));
        $page->stories()->sync((array) $request->input('stories'));

        $page->load('users', 'roles');

        return $page;
    }

    /**
     * It creates directory and attach users and/or roles permissions.
     *
     * @param Request $request
     * @param Project $project
     * @param User $user
     *
     * @return KnowledgePage $page
     */
    public function createDirectory(Request $request, Project $project, User $user)
    {
        $knowledge_directory = $this->knowledge_directory;

        return DB::transaction(function () use ($request, $project, $user, $knowledge_directory) {
            $directory = $knowledge_directory->create([
                    'project_id' => $project->id,
                    'creator_id' => $user->id,
                ] + $request->only(['name']));

            $directory->users()->attach($request->input('users'));
            $directory->roles()->attach($request->input('roles'));

            $directory->load('users', 'roles');

            return $directory;
        });
    }

    /**
     * It updates directory and edit users and/or roles permissions.
     *
     * @param Request $request
     * @param KnowledgeDirectory $directory
     * @param User $user
     *
     * @return KnowledgeDirectory $directory
     */
    public function updateDirectory(Request $request, KnowledgeDirectory $directory, User $user)
    {
        return DB::transaction(function () use ($request, $user, $directory) {
            $directory->update($request->only(['name']));

            $directory->users()->sync((array) $request->input('users'));
            $directory->roles()->sync((array) $request->input('roles'));

            $directory->load('users', 'roles');

            return $directory;
        });
    }

    /**
     * Delete directory and move pages in it to another directory or directly to project.
     *
     * @param KnowledgeDirectory $directory
     * @param Request $request
     */
    public function deleteDirectory(KnowledgeDirectory $directory, Request $request)
    {
        $target_id = $request->input('knowledge_directory_id');

        DB::transaction(function () use ($directory, $target_id) {
            $directory->pages()->update([
                'knowledge_directory_id' => $target_id ? $target_id : null,
            ]);
            $directory->delete();
        });
    }

    /**
     * Return query for list of pages user can access.
     *
     * @param Project $project
     * @param User $user
     * @param Request $request
     *
     * @return Builder
     */
    public function pagesList(Project $project, User $user, Request $request)
    {
        $directory_id = $request->input('knowledge_directory_id');

        // If user want to get only pages from main project directory (not in any directory)
        if ($directory_id == '0') {
            $directories = [null];
        // If user want to get only pages from certain directory
        } elseif ($directory_id) {
            $directories = [$directory_id];
        // User want to get all pages in project that he can access
        } else {
            // Get directories that user can access
            $directories = $this->knowledge_directory->assignedToUser($project, $user)->get()
                ->pluck('id');
        }

        $story_id = (int) $request->input('story_id');
        $search = $request->input('search', '');

        // Get list of pages user can access and if page is in directory match it with directories
        // user can access
        return $this->knowledge_page->assignedToUser($project, $user)
            ->when($request->input('pinned') == '1', function ($query) {
                return $query->where('pinned', true);
            })
            ->where(function ($query) use ($directories, $directory_id) {
                $query->when($directory_id, function ($query) use ($directories, $directory_id) {
                    return $query->whereIn('knowledge_directory_id', $directories)->orderBy('name');
                }, function ($query) use ($directories) {
                    return $query->whereIn('knowledge_directory_id', $directories)
                        ->orWhere('knowledge_directory_id', null);
                });
            })->when(($search != ''), function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'LIKE', '%' . $search . '%')
                        ->orWhere('content', 'LIKE', '%' . $search . '%');
                });
            })->when($story_id, function ($query) use ($story_id) {
                return $query->whereHas('stories', function ($query) use ($story_id) {
                    $query->where('id', $story_id);
                });
            })->with('stories')->orderBy('name');
    }

    /**
     * Get query for directories list in the project accessible to the user.
     *
     * @param Project $project
     * @param User $user
     *
     * @return Builder
     */
    public function directoryList(Project $project, User $user)
    {
        return $this->knowledge_directory
            ->assignedToUser($project, $user)
            ->with([
                'pages' => function ($q) use ($project, $user) {
                    $q->assignedToUser($project, $user)->with('stories');
                },'roles', 'users', ])
            ->orderBy('name');
    }
}
