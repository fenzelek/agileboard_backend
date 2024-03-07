<?php

namespace App\Models\Db;

use App\Interfaces\CompanyInterface;
use App\Models\Db\Integration\Integration;
use App\Models\Other\RoleType;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 */
class Company extends Model implements CompanyInterface
{
    use FullVatin;

    /**
     * @inheritdoc
     */
    protected $fillable = [
        'name',
        'package_id',
        'editor_id',
        'default_payment_method_id',
        'default_payment_term_days',
        'default_invoice_gross_counted',
        'vat_payer',
        'vat_release_reason_id',
        'vat_release_reason_note',
    ];

    /**
     * @inheritdoc
     */
    protected $casts = [
        'vat_payer' => 'boolean',
    ];

    /**
     * Company application settings.
     *
     * @Collection
     */
    protected $app_settings;

    public function getCompanyId()
    {
        return $this->id;
    }

    /**
     * There might be multiple invitations for users for given company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function invitations()
    {
        return $this->hasMany(Invitation::class, 'company_id');
    }

    /**
     * Company has multiple projects.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function projects()
    {
        return $this->hasMany(Project::class, 'company_id');
    }

    /**
     * There are multiple user availabilities for company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function availabilities()
    {
        return $this->hasMany(UserAvailability::class, 'company_id');
    }

    /**
     * Company can have multiple user roles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Registries for company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function registries()
    {
        return $this->hasMany(InvoiceRegistry::class, 'company_id');
    }

    /**
     * Users for company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_company');
    }

    /**
     * Users company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function usersCompany()
    {
        return $this->hasMany(UserCompany::class);
    }

    /**
     * Company invoices.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Company Bank Accounts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bankAccounts()
    {
        return $this->hasMany(BankAccount::class);
    }

    /**
     * Company Clipboard.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Clipboard()
    {
        return $this->hasMany(Clipboard::class, 'company_id');
    }

    /**
     * Custom company settings.
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function companyModules()
    {
        return $this->hasMany(CompanyModule::class);
    }

    /**
     * History of modules.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function companyModulesHistory()
    {
        return $this->hasMany(CompanyModuleHistory::class);
    }

    /**
     * Load company application settings. If not specified for this company, defaults will be
     * loaded.
     */
    public function loadAppSettings()
    {
        $companyModules = $this->companyModules;
        $this->app_settings = collect();
        Module::all()->each(function ($setting) use ($companyModules) {
            $module = $companyModules->where('module_id', $setting->id)->first();

            $this->app_settings->push([
                'slug' => $setting->slug,
                'value' => $module ? $module->value : null,
            ]);
        });
    }

    /**
     * Get all/indicated company application settings.
     *
     * @param $setting
     *
     * @return mixed
     */
    public function appSettings($setting = null)
    {
        if (empty($this->app_settings)) {
            $this->loadAppSettings();
        }
        if (is_string($setting)) {
            $found_setting =
                $this->app_settings->first(function ($verified_setting) use ($setting) {
                    return $verified_setting['slug'] == $setting;
                });

            return $found_setting ? $found_setting['value'] : null;
        }

        return $this->app_settings;
    }

    /**
     * Company's contractors.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contractors()
    {
        return $this->hasMany(Contractor::class);
    }

    /**
     * Get real package assigned to company.
     *
     * @return Package
     */
    public function realPackage()
    {
        return $this->companyModules()->whereNotNull('package_id')->orderBy('created_at', 'desc')
            ->first()->package;
    }

    /**
     * Get expiring date of current package.
     *
     * @return string|null
     */
    public function realPackageExpiringDate()
    {
        return $this->companyModules()->whereNotNull('package_id')->orderBy('created_at', 'desc')
            ->first()->expiration_date;
    }

    /**
     * Ged days to expiring current package.
     *
     * @return int
     */
    public function packageExpirationInDays()
    {
        if (Carbon::now()->gt(Carbon::parse($this->realPackageExpiringDate()))) {
            return 0;
        }

        return Carbon::parse($this->realPackageExpiringDate())->diffInDays(Carbon::now());
    }

    /**
     * Has premium package.
     *
     * @return bool
     */
    public function hasPremiumPackage()
    {
        return $this->packageExpirationInDays() > 0;
    }

    /**
     * Has test package.
     *
     * @return bool
     */
    public function hasTestPackage()
    {
        return $this->companyModules()->whereNotNull('package_id')
            ->orderBy('created_at', 'desc')->first()->companyModuleHistory->moduleMod->test;
    }

    /**
     * Company might have single JPK detail data.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function jpkDetail()
    {
        return $this->hasOne(CompanyJpkDetail::class, 'company_id');
    }

    /**
     * Company's reason of release vat.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function vatReleaseReason()
    {
        return $this->belongsTo(VatReleaseReason::class);
    }

    /**
     * Check if selected company is VAT payer.
     *
     * @return bool
     */
    public function isVatPayer()
    {
        return (bool) $this->vat_payer;
    }

    /**
     * Check that company vat settings is editable.
     *
     * @return bool
     */
    public function vatSettingsIsEditable()
    {
        return ! $this->hasInvoices();
    }

    /**
     * Check that company has any invoice.
     *
     * @return mixed
     */
    public function hasInvoices()
    {
        return $this->invoices()->withTrashed()->count();
    }

    /**
     * Default back account.
     *
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Relations\HasMany|null
     */
    public function defaultBankAccount()
    {
        return $this->bankAccounts()->whereDefault(true)->first() ?? $this->bankAccounts()->first();
    }

    /**
     * Check has free module.
     *
     * @param $module_id
     *
     * @return bool
     */
    public function hasPremiumModule($module_id)
    {
        return (bool) $this->companyModules()->where('module_id', $module_id)->notFree()->first();
    }

    /**
     * Get selected company module.
     *
     * @param Module $module
     *
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Relations\HasMany|null
     */
    public function getCompanyModule(Module $module)
    {
        return $this->companyModules()->where('module_id', $module->id)->first();
    }

    /**
     * Check this is active mod.
     *
     * @param ModuleMod $moduleMod
     *
     * @return bool
     */
    public function hasActiveMod(ModuleMod $moduleMod)
    {
        $module = $this->getCompanyModule($moduleMod->module);
        if ($module && $module->value == $moduleMod->value) {
            return true;
        }

        return false;
    }

    /**
     * Check module is active and has subscription in this company.
     *
     * @param ModuleMod $moduleMod
     *
     * @return bool
     */
    public function hasActiveModWithSubscription(ModuleMod $moduleMod)
    {
        $module = $this->getCompanyModule($moduleMod->module);
        if ($module && $module->value == $moduleMod->value && $module->subscription_id) {
            return true;
        }

        return false;
    }

    /**
     * Check this is active mod.
     *
     * @param ModuleMod $moduleMod
     *
     * @return bool
     */
    public function getExpirationDaysActiveMod(ModuleMod $moduleMod)
    {
        $module = $this->getCompanyModule($moduleMod->module);

        if (Carbon::now()->gt(Carbon::parse($module->expiration_date))) {
            return 0;
        }

        return Carbon::parse($module->expiration_date)->diffInDays(Carbon::now());
    }

    /**
     * Check has pending mod.
     *
     * @param ModuleMod $moduleMod
     *
     * @return bool
     */
    public function hasPendingMod(ModuleMod $moduleMod)
    {
        if ($this->companyModulesHistory()->where('module_id', $moduleMod->module->id)->isPending()
            ->first()) {
            return true;
        }

        return false;
    }

    /**
     * Check is active premium mod.
     *
     * @param Module $module
     *
     * @return bool
     */
    public function hasActivePremiumMod(Module $module)
    {
        $module = $this->getCompanyModule($module);
        if ($module) {
            $package_id = Package::findDefault()->id;
            $mod_price = ModPrice::where(function ($q) use ($package_id) {
                $q->where('package_id', $package_id)
                    ->orWhere(function ($q) {
                        $q->whereNull('package_id');
                        $q->where(function ($q) {
                            $q->where(function ($q) {
                                $q->default('PLN');
                            });
                            $q->orWhere(function ($q) {
                                $q->default('EUR');
                            });
                        });
                    });
            })->whereHas('moduleMod', function ($q) use ($module) {
                $q->where('module_id', $module->module_id);
            })->first();

            if (! $mod_price) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get owners.
     *
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Relations\BelongsToMany[]
     */
    public function getOwners()
    {
        return $this->usersCompany()->whereHas('role', function ($query) {
            $query->where('name', RoleType::OWNER);
        })->get();
    }

    /**
     * User can be assigned to multiple companies.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function getAdministration()
    {
        return $this->belongsToMany(
            User::class,
            'user_company',
            'company_id',
            'user_id'
        )->withPivot(['role_id'])->whereIn(
            'role_id',
            [
                Role::findByName(RoleType::OWNER)->id,
                Role::findByName(RoleType::ADMIN)->id,
            ]
        );
    }

    /**
     * Company might have multiple integrations set.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function integrations()
    {
        return $this->hasMany(Integration::class, 'company_id');
    }

    /**
     * Company might have multiple active integrations set.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activeIntegrations()
    {
        return $this->integrations()->active();
    }

    /**
     * Company might have multiple disabled integrations set.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function disabledIntegrations()
    {
        return $this->integrations()->disabled();
    }
}
