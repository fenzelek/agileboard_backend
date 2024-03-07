<?php

namespace App\Modules\Agile\Http\Requests;

use App\Http\Requests\Request;
use App\Http\Requests\Traits\HasInteractions;
use App\Interfaces\Interactions\IInteractionRequest;
use Illuminate\Support\Collection;

class TicketCommentUpdate extends Request implements IInteractionRequest
{
    use HasInteractions;

    public function rules(): array
    {
        $rules = [
            'text' => ['required', 'max:10000'],
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
