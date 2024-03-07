<?php

declare(strict_types=1);

namespace App\Modules\Knowledge\Contracts;

use App\Interfaces\Interactions\IInteractionRequest;

interface IUpdateCommentRequest extends IInteractionRequest
{
    public function getKnowledgePageCommentId(): int;

    public function getText(): ?string;

    public function getRef(): ?string;

    public function getProjectId(): int;
}
