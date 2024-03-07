<?php

declare(strict_types=1);

namespace App\Modules\Knowledge\Http\Requests;

use App\Http\Requests\Request;
use App\Http\Requests\Traits\HasInteractions;
use App\Models\Other\KnowledgePageCommentType;
use App\Modules\Knowledge\Contracts\ICommentCreateRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class StoreKnowledgePageCommentRequest extends Request implements ICommentCreateRequest
{
    use HasInteractions;

    public function rules(): array
    {
        $rules =  [
            'type' => ['required', Rule::in(KnowledgePageCommentType::all())],
            'text' => ['string', 'max:10000'],
            'ref' => ['string', 'max:255'],
        ];

        return array_merge($rules, $this->interactionRules());
    }

    public function getType(): string
    {
        return $this->input('type');
    }

    public function getRef(): ?string
    {
        return $this->input('ref');
    }

    public function getText(): ?string
    {
        return $this->input('text');
    }

    public function getProjectId(): int
    {
        return $this->route('project')->id;
    }

    public function getKnowledgePageId(): int
    {
        return $this->route('page')->id;
    }

    public function passedValidation()
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
}
