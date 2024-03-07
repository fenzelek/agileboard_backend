<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveContractorsSnapsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::drop('contractor_snaps');
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('contractor_snaps', function (Blueprint $table) {

            // Id
            $table->increments('id');

            // Contractor
            $table->unsignedInteger('contractor_id')->index();

            // Data
            $table->string('name', 255);
            $table->string('email', 63);
            $table->string('phone', 15);
            $table->string('bank_name', 63);
            $table->string('bank_account_number', 63);

            // Addresses
            $table->string('main_address_street', 255);
            $table->string('main_address_number', 31);
            $table->string('main_address_zip_code', 7);
            $table->string('main_address_city', 63);
            $table->string('main_address_country', 63);

            $table->string('contact_address_street', 255);
            $table->string('contact_address_number', 31);
            $table->string('contact_address_zip_code', 7);
            $table->string('contact_address_city', 63);
            $table->string('contact_address_country', 63);

            // Used
            $table->timestamp('using_from');
            $table->timestamp('using_to')->nullable();

            // Ownerships
            $table->unsignedInteger('creator_id');

            // Times
            $table->timestamps();
        });
    }
}
