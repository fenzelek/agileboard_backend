<?php

use App\Models\Db\PaymentMethod;
use App\Models\Other\PaymentMethodType;
use Illuminate\Database\Migrations\Migration;

class AddCashOnDeliveryMethodPayment extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::transaction(function () {
            $payment_method = PaymentMethod::findBySlug(PaymentMethodType::CASH_ON_DELIVERY, true);
            if (null === $payment_method) {
                PaymentMethod::create([
                    'name' => 'pobranie',
                    'slug' => PaymentMethodType::CASH_ON_DELIVERY,
                    'invoice_restrict' => false,
                ]);
            }
        });
    }

    /**
     * Reverse the migration.
     */
    public function down()
    {
        DB::transaction(function () {
            $payment_method = PaymentMethod::findBySlug(PaymentMethodType::CASH_ON_DELIVERY, true);
            if ($payment_method) {
                $payment_method->delete();
            }
        });
    }
}
