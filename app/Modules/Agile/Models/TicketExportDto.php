<?php

declare(strict_types=1);

namespace App\Modules\Agile\Models;

class TicketExportDto
{
    private int $id;

    private string $name;

    private string $title;

    private string $user_first_name;

    private string $user_last_name;

    private int $estimated_seconds;

    private int $tracked_seconds;

    public function __construct(
        int $id,
        string $name,
        string $title,
        string $user_first_name,
        string $user_last_name,
        int $estimated_seconds,
        int $tracked_seconds
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->title = $title;
        $this->user_first_name = $user_first_name;
        $this->user_last_name = $user_last_name;
        $this->estimated_seconds = $estimated_seconds;
        $this->tracked_seconds = $tracked_seconds;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUserFirstName(): string
    {
        return $this->user_first_name;
    }

    public function getUserLastName(): string
    {
        return $this->user_last_name;
    }

    public function getTrackedSeconds(): int
    {
        return $this->tracked_seconds;
    }

    public function getEstimatedSeconds(): int
    {
        return $this->estimated_seconds;
    }
}
