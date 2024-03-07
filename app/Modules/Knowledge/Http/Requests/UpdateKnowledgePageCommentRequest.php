<?php

declare(strict_types=1);

namespace App\Modules\Knowledge\Http\Requests;

use App\Http\Requests\Request;
use App\Http\Requests\Traits\HasInteractions;
use App\Interfaces\Interactions\IInteractionRequest;
use App\Modules\Knowledge\Contracts\IUpdateCommentRequest;
use Illuminate\Support\Collection;

class UpdateKnowledgePageCommentRequest extends Request implements IUpdateCommentRequest
{
    use HasInteractions;

    public function rules(): array
    {
        $rules = [
            'text' => ['string', 'max:10000'],
            'ref' => ['string', 'max:255'],
        ];

        return array_merge($rules, $this->interactionRules());
    }

    public function getKnowledgePageCommentId(): int
    {
        return $this->route('page_comment')->id;
    }

    public function getRef(): ?string
    {
        return $this->input('ref');
    }

    public function getText(): ?string
    {
        return $this->input('text');
    }

    protected function passedValidation()
    {
        $this->prepareInteractionItems();
    }

    public function getInteractionPings(): Collection
    {
        return $this->interaction_items;
    }

    public function getSelectedCompanyId(): int
    {
        return (int) $this->input('selected_company_id');
    }

    public function getProjectId(): int
    {
        return $this->route('project')->id;
    }
}
