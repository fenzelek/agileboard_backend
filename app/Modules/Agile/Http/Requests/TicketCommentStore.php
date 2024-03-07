<?php

namespace App\Modules\Agile\Http\Requests;

use App\Http\Requests\Request;
use App\Http\Requests\Traits\HasInteractions;
use App\Interfaces\Interactions\IInteractionRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class TicketCommentStore extends Request implements IInteractionRequest
{
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
            'text' => ['required', 'max:10000'],
            'ticket_id' => [
                'required',
                Rule::exists('tickets', 'id')
                    ->where('project_id', $project->id),
            ],
        ];

        return array_merge($rules, $this->interactionRules());
    }

    /**
     * @inheritdoc
     */
    public function all($keys = null)
    {
        $data = parent::all();

        // make sure data will be trimmed before validation
        foreach ($data as $key => $val) {
            $data[$key] = is_string($val) ? trim($val) : $val;
        }

        return $data;
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
}
