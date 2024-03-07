<?php

namespace App\Models\Db;

use App\Services\Mnabialek\LaravelEloquentFilter\Traits\Filterable;

/**
 * @property ?string $department
 * @property ?string $contract_type
 */
class UserCompany extends Model
{
    use Filterable;

    /**
     * @inheritdoc
     */
    protected $table = 'user_company';

    /**
     * @inheritdoc
     */
    protected $fillable = [
        'user_id',
        'company_id',
        'role_id',
        'status',
        'department',
        'contract_type',
    ];

    /**
     * Single record belongs to single user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Single record belongs to single company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Single record belongs to single role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
