<?php

namespace App\Modules\Company\Services;

use App\Models\Db\CompanyModule;
use App\Models\Db\CompanyModuleHistory;
use App\Models\Db\InvoiceCompany;
use App\Models\Db\ModPrice;
use App\Models\Db\Package;
use App\Models\Db\Role;
use App\Models\Db\Transaction;
use App\Models\Other\RoleType;
use App\Models\Db\User;
use App\Models\Db\UserCompany;
use App\Models\Other\SaleInvoice\Payers\NoVat;
use App\Models\Other\SaleInvoice\Payers\Vat;
use App\Models\Other\UserCompanyStatus;
use App\Modules\Company\Http\Requests\CompanyUpdate;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Foundation\Application;
use App\Models\Db\Company as CompanyModel;
use Illuminate\Database\Connection;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;

class Company
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var CompanyModel
     */
    protected $company;

    /**
     * @var UserCompany
     */
    protected $userCompany;

    /**
     * @var Package
     */
    protected $package;

    /**
     * @var FilesystemManager
     */
    protected $filesystem;

    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var Role
     */
    private $role;
    /**
     * @var BankAccountService
     */
    private $bank_account_service;

    /**
     * Company constructor.
     *
     * @param Application $app
     * @param Guard $guard
     * @param CompanyModel $company
     * @param UserCompany $userCompany
     * @param Role $role
     */
    public function __construct(
        Application $app,
        Guard $guard,
        CompanyModel $company,
        UserCompany $userCompany,
        Role $role,
        Package $package,
        FilesystemManager $filesystem,
        Connection $db,
        BankAccountService $bank_account_service
    ) {
        $this->app = $app;
        $this->user = $guard->user();
        $this->company = $company;
        $this->userCompany = $userCompany;
        $this->role = $role;
        $this->package = $package;
        $this->filesystem = $filesystem;
        $this->db = $db;
        $this->bank_account_service = $bank_account_service;
    }

    /**
     * Verifies whether user can create new company.
     *
     * @return bool
     */
    public function canCreate()
    {
        return ! (bool) $this->user->ownedCompanies()->first();
    }

    /**
     * Create new company and assign user as active owner.
     *
     * @param array $input
     *
     * @return CompanyModel
     */
    public function create(array $input)
    {
        return $this->app['db']->transaction(function () use ($input) {
            $company = $this->company->create($input);

            $transaction = Transaction::create();

            $package_id = $this->package::findDefault()->id;
            ModPrice::where(function ($q) use ($package_id) {
                $q->where('package_id', $package_id);
                $q->where('default', 1);
                $q->where('currency', 'PLN');
            })->orWhere(function ($q) {
                $q->orWhereNull('package_id');
                $q->where('default', 1);
                $q->where('currency', 'PLN');
            })->get()->each(function ($mod) use ($company, $package_id, $transaction) {
                $history = new CompanyModuleHistory();
                $history->company_id = $company->id;
                $history->module_id = $mod->moduleMod->module_id;
                $history->module_mod_id = $mod->module_mod_id;
                $history->package_id = $mod->package_id;
                $history->new_value = $mod->moduleMod->value;
                $history->transaction_id = $transaction->id;
                $history->currency = 'PLN';
                $history->save();

                $module = new CompanyModule();
                $module->company_id = $company->id;
                $module->module_id = $mod->moduleMod->module_id;
                $module->package_id = $mod->package_id;
                $module->value = $mod->moduleMod->value;
                $module->save();
            });

            $userCompany = $this->userCompany->newInstance();
            $userCompany->user_id = $this->user->id;
            $userCompany->company_id = $company->id;
            $userCompany->role_id = $this->role->findByName(RoleType::OWNER)->id;
            $userCompany->status = UserCompanyStatus::APPROVED;
            $userCompany->save();

            $company->roles()->attach(Role::where('default', 1)->get());

            return $company;
        });
    }

    public function update(User $user, Request $request)
    {
        $company = $this->company->findOrFail($user->getSelectedCompanyId());

        $file = $request->file('logotype');

        $this->db->beginTransaction();

        try {
            $fields = [
                'name',
                'country_vatin_prefix_id',
                'vatin',
                'vat_payer',
                'vat_release_reason_id',
                'vat_release_reason_note',
                'email',
                'website',
                'phone',
                'main_address_street',
                'main_address_number',
                'main_address_zip_code',
                'main_address_city',
                'main_address_country',
                'contact_address_street',
                'contact_address_number',
                'contact_address_zip_code',
                'contact_address_city',
                'contact_address_country',
            ];

            collect($fields)->each(function ($field) use ($request, $company) {
                $value = $request->input($field);
                $company->$field = is_string($value) ? trim($value) : $value;
            });

            $company->editor_id = $user->id;

            if ($request->input('remove_logotype')) {
                $this->removeLogotype($company);
                $company->logotype = '';
            }
            if ($file) {
                $this->removeLogotype($company);
                do {
                    $file_name = $company->id . str_random(20) . '.' . $file->extension();
                } while ($this->filesystem->disk('logotypes')->exists($file_name));

                $file->storeAs('', $file_name, 'logotypes');

                $this->resizeImage($file_name);

                $company->logotype = $file_name;
            }

            if (! $company->isVatPayer()) {
                $company->default_invoice_gross_counted = NoVat::COUNT_TYPE;
            } else {
                $company->vat_release_reason_id = Vat::VAT_RELEASE_REASON;
                $company->vat_release_reason_note = Vat::VAT_RELEASE_REASON_NOTE;
            }

            $company->save();

            $this->bank_account_service->sync($company, $request->bank_accounts ?: []);

            $this->db->commit();

            return $company->load('bankAccounts');
        } catch (\Exception $e) {
            if (isset($file_name)) {
                $this->filesystem->disk('logotypes')->delete($file_name);
            }
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateSettings(User $user, Request $request)
    {
        $company = $this->company->findOrFail($user->getSelectedCompanyId());

        $this->db->beginTransaction();

        try {
            $fields = [
                'force_calendar_to_complete',
                'enable_calendar',
                'enable_activity',
            ];

            collect($fields)->each(function ($field) use ($request, $company) {
                $value = $request->input($field);
                $company->$field = is_string($value) ? trim($value) : $value;
            });

            $company->save();

            $this->db->commit();

            return $company;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Check if vat payer setting blocked by issuing any invoices.
     *
     * @param User $user
     * @param CompanyUpdate $request
     * @return bool
     */
    public function blockedVatPayerSetting(User $user, CompanyUpdate $request)
    {
        $company = $this->company->findOrFail($user->getSelectedCompanyId());

        if ($company->vatSettingsIsEditable()) {
            return false;
        }

        if ($this->compatibilityVatPayerSetting($company, $request)) {
            return false;
        }

        return true;
    }

    /**
     * Remove logotype file from storage by name.
     *
     * @param string $logotype
     */
    protected function removeLogotype($company)
    {
        $logotype = $company->logotype;
        $used = InvoiceCompany::where('company_id', $company->id)->where('logotype', $logotype)
            ->count();
        if (! $used) {
            $this->filesystem->disk('logotypes')->delete($logotype);
        }
    }

    /**
     * Resizing image.
     *
     * @param $file_name
     */
    protected function resizeImage($file_name)
    {
        $image = Image::make($this->filesystem->disk('logotypes')->get($file_name));
        $width = $image->width();
        $height = $image->height();

        if ($width > 300 || $height > 300) {
            if ($width >= $height) {
                $image->resize(300, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
            } else {
                $image->resize(null, 300, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }

            $path = $this->filesystem->disk('logotypes')->getDriver()->getAdapter()
                ->getPathPrefix();
            $image->save($path . '/' . $file_name);
        }
    }

    /**
     * @param $company
     * @param $request
     * @return bool
     */
    protected function compatibilityVatPayerSetting($company, $request)
    {
        if ($request->vat_payer == $company->vat_payer) {
            return true;
        }

        return false;
    }
}
