<?php

namespace App\Modules\Knowledge\Contracts;

use App\Interfaces\Interactions\IInteractionRequest;

interface ICommentCreateRequest extends IInteractionRequest
{
    public function getProjectId(): int;

    public function getKnowledgePageId(): int;

    public function getText(): ?string;

    public function getRef(): ?string;

    public function getType(): string;
}
