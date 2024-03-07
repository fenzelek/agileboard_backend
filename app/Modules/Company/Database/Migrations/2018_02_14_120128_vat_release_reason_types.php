<?php

use App\Models\Other\VatReleaseReasonType;
use App\Models\Db\VatReleaseReason;
use Illuminate\Database\Migrations\Migration;

class VatReleaseReasonTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        collect($this->vatReleaseReason())->each(function ($vat_release_reason) {
            if (! VatReleaseReason::where('slug', $vat_release_reason['slug'])->first()) {
                VatReleaseReason::create($vat_release_reason);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::table('vat_release_reasons')->truncate();
    }

    protected function vatReleaseReason()
    {
        return [
            [
                'slug' => VatReleaseReasonType::TYPE,
                'description' => 'Zwolnienie ze względu na rodzaj prowadzonej działalności.',
            ],
            [
                'slug' => VatReleaseReasonType::INCOME,
                'description' => 'Zwolnienie ze względu na nieprzekroczenie 200 000 PLN obrotu.',
            ],
            [
                'slug' => VatReleaseReasonType::LEGAL_REGULATION,
                'description' => 'Zwolnienie na mocy rozporządzenia MF.',
            ],
            [
                'slug' => VatReleaseReasonType::LEGAL_BASIS,
                'description' => 'Inna podstawa prawna.',
            ],
        ];
    }
}
