<?php

namespace App\Modules\Knowledge\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvolvedTransformer;
use App\Http\Resources\KnowledgePageWithoutContent;
use App\Models\Db\Involved;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\Project;
use App\Modules\Involved\Services\InvolvedService;
use App\Modules\Knowledge\Services\Knowledge;
use App\Modules\Knowledge\Services\KnowledgePageInteractionFactory;
use App\Services\Paginator;
use Illuminate\Contracts\Auth\Guard;
use App\Modules\Knowledge\Http\Requests\StoreUpdatePageRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Container\Container;

/**
 *
 */
class KnowledgePageController extends Controller
{
    /**
     * Show list of pages in project.
     *
     * @param Request $request
     * @param Project $project
     * @param Paginator $paginator
     * @param Knowledge $knowledge
     * @param Guard $auth
     *
     * @return JsonResponse
     */
    public function index(
        Request $request,
        Project $project,
        Paginator $paginator,
        Knowledge $knowledge,
        Guard $auth
    ) {
        $pages = $paginator->get(
            $knowledge->pagesList($project, $auth->user(), $request),
            'knowledge-page.index',
            ['project' => $project]
        );

        return ApiResponse::transResponseOk(
            $pages,
            200,
            [KnowledgePage::class => KnowledgePageWithoutContent::class]
        );
    }

    /**
     * Show page all details.
     *
     * @param Project $project
     * @param KnowledgePage $page
     *
     * @return JsonResponse
     */
    public function show(Project $project, KnowledgePage $page)
    {
        $relationships = [
            'files',
            'users',
            'roles',
            'stories',
            'comments.user',
            'involved.user'
        ];

        $page->load($relationships);

        return ApiResponse::transResponseOk($page, 200, [
            Involved::class => InvolvedTransformer::class,
        ]);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreUpdatePageRequest $request
     * @param Project $project
     * @param Guard $auth
     * @param Knowledge $knowledge
     * @param KnowledgePageInteractionFactory $knowledge_interaction_factory
     * @param InvolvedService $involved_service
     *
     * @return JsonResponse
     */
    public function store(
        StoreUpdatePageRequest $request,
        Project $project,
        Guard $auth,
        Knowledge $knowledge,
        KnowledgePageInteractionFactory $knowledge_interaction_factory,
        InvolvedService $involved_service
    ): JsonResponse {
        $user = $auth->user();

        return DB::transaction(function () use ($request, $project, $user, $knowledge, $knowledge_interaction_factory, $involved_service) {
            $page = $knowledge->createPage($request, $project, $user);

            $knowledge_interaction_factory->forNewPage(
                $request,
                $page,
                $project->id,
                $user->id
            );

            $new_involved_ids = $involved_service->getNewInvolvedIds($request, $page);
            $involved_service->syncInvolved($request, $page);
            $knowledge_interaction_factory->forInvolvedAssigned(
                $new_involved_ids,
                $page,
                $request->getSelectedCompanyId(),
                $project->id,
                $user->id
            );

            return ApiResponse::responseOk($page, 201);
        });
    }

    /**
     * Soft delete a page.
     *
     * @param Project $project
     * @param KnowledgePage $page
     * @param InvolvedService $involved_service
     * @param KnowledgePageInteractionFactory $knowledge_interaction_factory
     *
     * @return JsonResponse
     */
    public function destroy(
        Project $project,
        KnowledgePage $page,
        Guard $auth,
        InvolvedService $involved_service,
        KnowledgePageInteractionFactory $knowledge_interaction_factory): JsonResponse
    {
        $user = $auth->user();

        return DB::transaction(function () use ($project, $page, $involved_service, $knowledge_interaction_factory, $user) {

            $involved_users = $involved_service->getInvolvedUsers($page);

            $page->interactions()->delete();
            $involved_service->deleteInvolved($page);
            $page->delete();

            $knowledge_interaction_factory->forInvolvedDeleted(
                $involved_users->pluck('user_id'),
                $page,
                $project,
                $user->id);

            return ApiResponse::responseOk([], 204);
        });
    }

    /**
     * Updates knowledge page data and permissions.
     *
     * @param StoreUpdatePageRequest $request
     * @param Project $project
     * @param KnowledgePage $page
     * @param Guard $auth
     * @param Knowledge $knowledge
     * @param KnowledgePageInteractionFactory $knowledge_interaction_factory
     * @param InvolvedService $involved_service
     *
     * @return JsonResponse
     */
    public function update(
        StoreUpdatePageRequest $request,
        Project $project,
        KnowledgePage $page,
        Guard $auth,
        Knowledge $knowledge,
        KnowledgePageInteractionFactory $knowledge_interaction_factory,
        InvolvedService $involved_service
    ): JsonResponse {
        $user = $auth->user();

        return DB::transaction(function () use ($request, $project, $user, $knowledge, $page, $knowledge_interaction_factory, $involved_service) {
            $page = $knowledge->updatePage($request, $page, $user);

            $knowledge_interaction_factory->forPageEdit(
                $request,
                $page,
                $project->id,
                $user->id
            );

            $new_involved_ids = $involved_service->getNewInvolvedIds($request, $page);
            $involved_service->syncInvolved($request, $page);
            $knowledge_interaction_factory->forInvolvedAssigned(
                $new_involved_ids,
                $page,
                $request->getSelectedCompanyId(),
                $project->id,
                $user->id);

            return ApiResponse::responseOk($page, 200);
        });
    }
}
