<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Other\PaymentMethodType;
use App\Models\Db\PaymentMethod;
use Illuminate\Support\Facades\DB;

class PaymentMethodsAddValueCashCardPaymentMatod extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            $payment_method = DB::table('payment_methods')
                ->select('id')
                ->where('slug', PaymentMethodType::CASH_CARD)
                ->first();
            if (null === $payment_method) {
                PaymentMethod::create([
                    'name' => 'Gotówka/Karta',
                    'slug' => PaymentMethodType::CASH_CARD,
                    'invoice_restrict' => true,
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
            $payment_method = PaymentMethod::where('slug', PaymentMethodType::CASH_CARD)
                ->first();
            if ($payment_method) {
                $payment_method->delete();
            }
        });
    }
}
