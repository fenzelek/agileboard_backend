<?php

namespace App\Modules\Integration\Models;

class ManualTicketActivityDto
{
    private int $ticket_id;

    private string $ticket_title;

    private string $ticket_name;

    private int $manual_activity;

    public function __construct(int $ticket_id, string $ticket_title, string $ticket_name, int $manual_activity)
    {
        $this->ticket_id = $ticket_id;
        $this->ticket_title = $ticket_title;
        $this->ticket_name = $ticket_name;
        $this->manual_activity = $manual_activity;
    }

    public function getTicketId(): int
    {
        return $this->ticket_id;
    }

    public function getTicketTitle(): string
    {
        return $this->ticket_title;
    }

    public function getTicketName(): string
    {
        return $this->ticket_name;
    }

    public function getManualActivity(): int
    {
        return $this->manual_activity;
    }
}
