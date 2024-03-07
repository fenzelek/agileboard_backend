<?php

use App\Models\Db\PaymentMethod;
use App\Models\Other\PaymentMethodType;
use Illuminate\Database\Migrations\Migration;

class SetLowestOrderToOtherPaymentMethod extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $payment_method = PaymentMethod::findBySlug(PaymentMethodType::OTHER, true);
        if ($payment_method) {
            $payment_method->order = 100;
            $payment_method->save();
        }
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        $payment_method = PaymentMethod::findBySlug(PaymentMethodType::OTHER, true);
        if ($payment_method) {
            $payment_method->order = 0;
            $payment_method->save();
        }
    }
}
