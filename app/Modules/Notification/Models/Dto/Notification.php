<?php

declare(strict_types=1);

namespace App\Modules\Notification\Models\Dto;

use Carbon\Carbon;

class Notification
{
    private string $id;

    private Carbon $created_at;

    private ?Carbon $read_at;

    private string $type;

    private array $data;

    private ?int $company_id;

    public function __construct(
        string $id,
        Carbon $created_at,
        ?Carbon $read_at,
        string $type,
        array $data,
        ?int $company_id
    ) {
        $this->id = $id;
        $this->created_at = $created_at;
        $this->read_at = $read_at;
        $this->type = $type;
        $this->data = $data;
        $this->company_id = $company_id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->created_at;
    }

    public function getReadAt(): ?Carbon
    {
        return $this->read_at;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getCompanyId(): ?int
    {
        return $this->company_id;
    }
}
