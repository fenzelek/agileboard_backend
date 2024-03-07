<?php

declare(strict_types=1);

namespace App\Modules\Notification\Models;

use App\Models\Db\Company;
use App\Models\Db\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\DatabaseNotification as VendorDatabaseNotification;

/**
 * @property string $id
 * @property string $type
 * @property ?Carbon $read_at
 * @property array $data
 * @property ?int $company_id
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @method static Builder|DatabaseNotification newQuery()
 * @method static Builder|DatabaseNotification byCompany(int $company_id)
 */
class DatabaseNotification extends VendorDatabaseNotification
{
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function scopeByCompany(Builder $builder, int $company_id): Builder
    {
        return $builder->where('company_id', $company_id);
    }
}
