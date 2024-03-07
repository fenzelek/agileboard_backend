<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->string('description')->nullable();
            $table->boolean('visible')->default(true);
            $table->boolean('available')->default(true);
            $table->timestamps();
        });

        $hidden = [
            'projects.active',
            'general.invite.enabled',
            'general.welcome_url',
            'general.companies.visible',
            'invoices.active',
            'receipts.active',
        ];

        foreach (DB::table('application_settings')->get() as $setting) {
            $model = new \App\Models\Db\Module();
            $model->id = $setting->id;
            $model->name = $setting->description;
            $model->slug = $setting->slug;
            $model->description = $setting->description;
            $model->visible = (in_array($setting->slug, $hidden)) ? false : true;
            $model->available = true;
            $model->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('modules');
    }
}
