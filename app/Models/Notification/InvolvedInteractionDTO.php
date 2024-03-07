<?php
declare(strict_types=1);

namespace App\Models\Notification;

use App\Interfaces\Interactions\IInteractionRequest;
use Illuminate\Support\Collection;

class InvolvedInteractionDTO implements IInteractionRequest
{
    private int $company_id;
    private Collection $new_involved_ids;
    private Collection $integration_pings;

    public function __construct(int $company_id, Collection $new_involved_ids)
    {
        $this->company_id = $company_id;
        $this->new_involved_ids = $new_involved_ids;
        $this->prepareIntegrationPings();
    }

    public function getInteractionPings(): Collection
    {
        return $this->integration_pings;
    }

    public function getSelectedCompanyId(): int
    {
        return $this->company_id;
    }

    private function prepareIntegrationPings(): void
    {
        $this->integration_pings = new Collection();
        foreach ($this->new_involved_ids as $involved_id){
            $this->integration_pings->add(new InteractionPingDTO($involved_id));
        }
    }
}