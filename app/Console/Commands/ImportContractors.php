<?php

namespace App\Console\Commands;

use App\Models\Db\Company;
use App\Models\Db\ContractorAddress;
use App\Models\Other\ContractorAddressType;
use Illuminate\Console\Command;
use File;
use DB;

class ImportContractors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:contractors {company_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Contractors';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    /**
     * @var Company
     */
    protected $company;

    protected $filepath = 'app/importContractors.csv';

    public function __construct(Company $company)
    {
        parent::__construct();

        $this->company = $company;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $company = $this->company->findOrFail($this->argument('company_id'));
        if ($company->contractors()->count()) {
            $this->info('Company has contractors already');

            return;
        }

        if (File::exists(storage_path($this->filepath))) {
            $records = file(storage_path($this->filepath));
            array_shift($records);

            DB::transaction(function () use ($records, $company) {
                collect($records)->each(function ($record) use ($company) {
                    $values = explode(';', $record);

                    array_walk($values, function (&$value) {
                        $value = trim($value);
                    });

                    $fields = [];

                    $base_labels = [
                        'name',
                        'vatin',
                        'email',
                        'phone',
                        'bank_name',
                        'bank_account_number',
                        'main_address_street',
                        'main_address_number',
                        'main_address_zip_code',
                        'main_address_city',
                        'main_address_country',
                        ];

                    $fields = $fields + $this->prepareData($values, $base_labels);

                    collect($fields)->each(function ($field, $label) {
                        if (empty($field) && $label == 'name') {
                            throw new \Exception('Empty base parameter. Label: ' . $label);
                        }
                    });

                    $contact_labels = [
                        'contact_address_street',
                        'contact_address_number',
                        'contact_address_zip_code',
                        'contact_address_city',
                        'contact_address_country',
                    ];

                    $contact_details = $this->prepareData($values, $contact_labels);

                    $fields = $fields + $this->setMissingAddressDetails($contact_details, $fields, 'contact_address_');

                    $contractor = $company->contractors()->create($fields);

                    while (count($values)) {
                        $delivery_address_labels = [
                            'street',
                            'number',
                            'zip_code',
                            'city',
                            'country',
                        ];

                        $delivery_address = $this->prepareData($values, $delivery_address_labels);
                        $count_contractor_addresses = $contractor->addresses()->count();
                        if (! $count_contractor_addresses) {
                            $delivery_address = $this->setMissingAddressDetails($delivery_address, $fields);
                            $delivery_address['default'] = true;
                        }
                        if (! $count_contractor_addresses || $this->notEmptyValues($delivery_address)) {
                            $contractor->addresses()->create(
                                $delivery_address + [
                                    'name' => ContractorAddress::getDefaultName($delivery_address),
                                    'type' => ContractorAddressType::DELIVERY,
                                ]
                            );
                        }
                    }
                });
            });
        }

        $this->info('Import finished');
    }

    /**
     * Marking field according to the sequence in importing file.
     *
     * @param $values
     * @param $labels
     * @return array
     */
    public function prepareData(&$values, $labels): array
    {
        $count_labels = count($labels);
        $params = array_slice($values, 0, $count_labels);
        $values = array_slice($values, $count_labels);

        return array_combine($labels, $params);
    }

    /**
     * Set missing address field by main address details.
     *
     * @param $contact_details
     * @param $fields
     * @return mixed
     */
    public function setMissingAddressDetails($contact_details, $fields, $part_label = '')
    {
        collect($contact_details)->each(function ($detail, $label) use ($fields, &$contact_details, $part_label) {
            if (empty($detail)) {
                $address_part = mb_substr($label, mb_strlen($part_label));
                $contact_details[$label] = $fields['main_address_' . $address_part];
            }
        });

        return $contact_details;
    }

    /**
     * Check existing not fully address details.
     *
     * @param $address
     * @return bool
     */
    protected function notEmptyValues($address)
    {
        foreach ($address as $part) {
            if (empty($part)) {
                return false;
            }
        }

        return true;
    }
}
