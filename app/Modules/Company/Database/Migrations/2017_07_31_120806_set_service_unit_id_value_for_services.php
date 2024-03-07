<?php

use App\Models\Db\CompanyService;
use App\Models\Db\InvoiceItem;
use App\Models\Db\ServiceUnit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SetServiceUnitIdValueForServices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            $usluga_id = ServiceUnit::findBySlug('usl')->id;
            foreach (CompanyService::all() as $service) {
                if ($service->type == CompanyService::TYPE_SERVICE) {
                    $service->service_unit_id = $usluga_id;
                    $service->save();
                }
            }
            foreach (InvoiceItem::all() as $item) {
                if ($item->type == CompanyService::TYPE_SERVICE) {
                    $item->service_unit_id = $usluga_id;
                    $item->save();
                }
            }
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
    }
}
