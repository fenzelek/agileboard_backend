<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Db\Contractor;
use App\Models\Db\ContractorAddress;
use App\Models\Other\ContractorAddressType;

class ContractorAddressesAddDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Contractor::whereDoesntHave('addresses', function ($query) {
            $query->where('type', ContractorAddressType::DELIVERY);
        })->get()->each(function ($contractor) {
            $address = [
               'type' => ContractorAddressType::DELIVERY,
               'street' => $contractor->main_address_street,
               'number' => $contractor->main_address_number,
               'zip_code' => $contractor->main_address_zip_code,
               'city' => $contractor->main_address_city,
               'country' => $contractor->main_address_country,
           ];
            $address['name'] = ContractorAddress::getDefaultName($address);
            $contractor->addresses()->create($address);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        ContractorAddress::where('type', ContractorAddressType::DELIVERY)
            ->get()->each(function ($contractor_address) {
                $contractor_address->delete();
            });
    }
}
