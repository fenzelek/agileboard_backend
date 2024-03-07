<?php

namespace App\Models\Db;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\Mnabialek\LaravelEloquentFilter\Traits\Filterable;

class CompanyService extends Model
{
    use Filterable;
    const TYPE_SERVICE = 'service';
    const TYPE_ARTICLE = 'article';

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'print_on_invoice',
        'description',
        'pkwiu',
        'price_net',
        'price_gross',
        'vat_rate_id',
        'service_unit_id',
        'creator_id',
        'editor_id',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function vatRate()
    {
        return $this->belongsTo(VatRate::class);
    }

    /**
     * Company service belongs to one service unit.
     *
     * @return BelongsTo
     */
    public function serviceUnit()
    {
        return $this->belongsTo(ServiceUnit::class);
    }

    public function decrement($column, $amount = 1, array $extra = [])
    {
        if ($column === 'is_used' && ($this->is_used - $amount) < 0) {
            $amount = $this->is_used;
        }

        return parent::decrement($column, $amount, $extra);
    }
}
