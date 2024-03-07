<?php

use App\Models\Db\PaymentMethod;
use App\Models\Other\PaymentMethodType;
use Illuminate\Database\Migrations\Migration;

class AddPayuPaymentMethod extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            $payment_method = PaymentMethod::findBySlug(PaymentMethodType::PAYU, true);
            if (null === $payment_method) {
                PaymentMethod::create([
                    'name' => 'payu',
                    'slug' => PaymentMethodType::PAYU,
                    'invoice_restrict' => false,
                ]);
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
        DB::transaction(function () {
            $payment_method = PaymentMethod::findBySlug(PaymentMethodType::PAYU, true);
            if ($payment_method) {
                $payment_method->delete();
            }
        });
    }
}
