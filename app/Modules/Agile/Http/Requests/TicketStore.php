<?php

namespace App\Modules\Agile\Http\Requests;

use App\Http\Requests\Request;
use App\Http\Requests\Traits\HasInteractions;
use App\Interfaces\Interactions\IInteractionRequest;
use Illuminate\Support\Collection;
use App\Interfaces\Involved\IInvolvedRequest;
use App\Rules\UsersInProjectRule;
use Illuminate\Validation\Rule;

class TicketStore extends Request implements IInteractionRequest, IInvolvedRequest
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
            'parent_ticket_ids' => ['nullable', 'array'],
            'parent_ticket_ids.*' => ['integer'],
            'sub_ticket_ids' => ['nullable', 'array'],
            'sub_ticket_ids.*' => ['integer'],
            'name' => ['required', 'max:255'],
            'type_id' => [
                'required',
                Rule::exists('ticket_types', 'id'),
            ],
            'assigned_id' => [
                'nullable',
                Rule::exists('user_company', 'user_id')
                    ->where('company_id', $this->input('selected_company_id')),
            ],
            'reporter_id' => [
                'nullable',
                Rule::exists('user_company', 'user_id')
                    ->where('company_id', $this->input('selected_company_id')),
            ],
            'description' => ['max:30000'],
            'estimate_time' => ['required', 'numeric'],
            'scheduled_time_start' => [
                'nullable',
                'date_format:Y-m-d H:i:s',
            ],
            'scheduled_time_end' => [
                'nullable',
                'date_format:Y-m-d H:i:s',
            ],
            'story_id.*' => [
                'nullable',
                Rule::exists('stories', 'id')
                    ->where('project_id', $project->id),
            ],
            'involved_ids' => ['array', new UsersInProjectRule($project->id)],
        ];

        if ($this->input('scheduled_time_end')) {
            $rules['scheduled_time_start'][] = 'before_or_equal:' . $this->input('scheduled_time_end', '');
        }

        if ($this->input('scheduled_time_start')) {
            $rules['scheduled_time_end'][] = 'after_or_equal:' . $this->input('scheduled_time_start', '');
        }

        //if not backlog
        if ($this->input('sprint_id') !== 0) {
            $rules['sprint_id'] = [
                'required',
                Rule::exists('sprints', 'id')
                    ->where('project_id', $project->id),
            ];
        }

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

    public function getProjectId(): int
    {
        return (int) $this->route('project')->id;
    }

    public function getInvolvedIds(): array
    {
        return  $this->input('involved_ids', []);
    }
}
