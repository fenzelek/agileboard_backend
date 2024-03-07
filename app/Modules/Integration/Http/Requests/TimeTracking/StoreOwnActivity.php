<?php

namespace App\Modules\Integration\Http\Requests\TimeTracking;

use App\Http\Requests\Request;
use App\Modules\Integration\Http\Requests\TimeTracking\Traits\StoreActivityTrait;
use App\Modules\Integration\Services\Contracts\ManualActivityDataProvider;
use Illuminate\Support\Carbon;

class StoreOwnActivity extends Request implements ManualActivityDataProvider
{
    use StoreActivityTrait;

    public function rules()
    {
        return $this->commonStoreActivityRules();
    }

    public function getUserId(): int
    {
        return $this->currentUser()->id;
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
