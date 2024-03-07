<?php

namespace App\Modules\Integration\Http\Requests\TimeTracking;

use App\Http\Requests\Request;
use App\Modules\Integration\Http\Requests\TimeTracking\Traits\StoreActivityTrait;
use App\Modules\Integration\Services\Contracts\ManualActivityDataProvider;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class StoreActivity extends Request implements ManualActivityDataProvider
{
    use StoreActivityTrait;

    public function rules()
    {
        $current_company_id = $this->currentUser()->getSelectedCompanyId();

        $common_rules = $this->commonStoreActivityRules();

        $user_rule = [
            'user_id' => [
                'required',
                Rule::exists('user_company', 'user_id')
                    ->where('company_id', $current_company_id),
            ],
        ];

        return array_merge($common_rules, $user_rule);
    }

    public function getUserId(): int
    {
        return $this->input('user_id');
    }

    public function getTicketId(): int
    {
        return $this->input('ticket_id');
    }

    public function getProjectId(): int
    {
        return $this->input('project_id');
    }

    public function getFrom(): Carbon
    {
        return Carbon::create($this->input('from'));
    }

    public function getTo(): Carbon
    {
        return Carbon::create($this->input('to'));
    }

    public function getComment(): ?string
    {
        return $this->input('comment', '');
    }
}
