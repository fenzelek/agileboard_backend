<?php

declare(strict_types=1);

namespace App\Modules\Knowledge\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\KnowledgePageCommentBasic;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\KnowledgePageComment;
use App\Models\Db\Project;
use App\Modules\Knowledge\Http\Requests\StoreKnowledgePageCommentRequest;
use App\Modules\Knowledge\Http\Requests\UpdateKnowledgePageCommentRequest;
use App\Modules\Knowledge\Services\KnowledgePageInteractionFactory;
use App\Modules\Knowledge\Services\KnowledgePageCommentService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\JsonResponse;

class KnowledgePageCommentController extends Controller
{
    private KnowledgePageCommentService $service;
    private KnowledgePageInteractionFactory $interaction_factory;

    public function __construct(KnowledgePageCommentService $service, KnowledgePageInteractionFactory $interaction_factory)
    {
        $this->service = $service;
        $this->interaction_factory = $interaction_factory;
    }

    public function store(
        StoreKnowledgePageCommentRequest $request,
        Project $project,
        KnowledgePage $page,
        Guard $guard
    ): KnowledgePageCommentBasic {
        $comment = $this->service->create($request, $guard->id());

        $this->interaction_factory->forNewComment($request, $comment, $guard->id());

        return new KnowledgePageCommentBasic($comment);
    }

    public function update(
        UpdateKnowledgePageCommentRequest $request,
        Project $project,
        KnowledgePageComment $page_comment,
        Guard $guard
    ): KnowledgePageCommentBasic {
        $comment = $this->service->update($request);

        $this->interaction_factory->forCommentEdit($request, $comment, $guard->id());

        return new KnowledgePageCommentBasic($comment);
    }

    public function destroy(Project $project, KnowledgePageComment $page_comment): JsonResponse
    {
        $this->service->destroy($page_comment->id);

        return new JsonResponse();
    }
}
