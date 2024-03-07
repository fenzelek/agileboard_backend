<?php

namespace App\Modules\Knowledge\Http\Requests;

use App\Http\Requests\Request;
use App\Http\Requests\Traits\HasInteractions;
use App\Interfaces\Interactions\IInteractionRequest;
use App\Http\Requests\Traits\HasInvolved;
use App\Interfaces\Involved\IInvolvedRequest;
use App\Modules\Knowledge\Traits\UserAndRolePermissions;
use Illuminate\Support\Collection;
use App\Rules\UsersInProjectRule;
use Illuminate\Validation\Rule;

class StoreUpdatePageRequest extends Request implements IInteractionRequest, IInvolvedRequest
{
    use UserAndRolePermissions;
    use HasInteractions;

    public function rules(): array
    {
        $project = $this->route('project');

        $rules = [
                'selected_company_id' => [
                    'required',
                    'integer',
                    Rule::exists('companies', 'id'),
                ],
                'knowledge_directory_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('knowledge_directories', 'id')
                        ->where('project_id', $this->route('project')->id),
                ],
                'name' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'content' => [
                    'required',
                    'string',
                    'max:60000',
                ],
                'pinned' => [
                    'required',
                    'boolean',
                ],
                'stories' => [
                    'array',
                ],
                'stories.*' => [
                    'integer',
                    Rule::exists('stories', 'id')
                        ->where('project_id', $project->id),
                ],
                'involved_ids' => ['array', new UsersInProjectRule($project->id)],
            ] + $this->getRules();

        return array_merge($rules, $this->interactionRules());
    }

    protected function passedValidation(): void
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
        return (int) $this->route('project')->id;
    }

    public function getInvolvedIds(): array
    {
        return  $this->input('involved_ids', []);
    }
}
