<?php

use App\Models\Db\PaymentMethod;
use Illuminate\Database\Migrations\Migration;

class AddPaymentMethods extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $payment_methods_data = [
            [
                'name' => 'gotówka',
            ],
            [
                'name' => 'przelew',
            ],
            [
                'name' => 'karta',
            ],
            [
                'name' => 'przedpłata',
            ],
            [
                'name' => 'inne',
            ],
        ];

        foreach ($payment_methods_data as $item) {
            $payment_method = new PaymentMethod();
            $payment_method->slug = str_slug($item['name'], '-');
            $payment_method->name = $item['name'];
            $payment_method->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        PaymentMethod::truncate();
    }
}
