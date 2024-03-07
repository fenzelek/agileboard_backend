<?php

use App\Models\Db\ServiceUnit;
use Illuminate\Database\Migrations\Migration;

class ServiceUnitsFillOrderNumber extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        ServiceUnit::whereIn('slug', $this->setIndexing())->get()->each(function ($unit) {
            $order_number = array_search($unit->slug, $this->setIndexing());
            $unit->update(['order_number' => $order_number]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::table('service_units')->update(['order_number' => ServiceUnit::NO_INDEXING]);
    }

    public function setIndexing()
    {
        return [
            ServiceUnit::SERVICE,
            ServiceUnit::UNIT,
            ServiceUnit::METR,
            ServiceUnit::MONTH,
            ServiceUnit::PACKAGE,
        ];
    }
}
