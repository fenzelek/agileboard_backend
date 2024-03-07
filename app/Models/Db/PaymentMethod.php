<?php

namespace App\Models\Db;

use App\Models\Other\PaymentMethodType;

class PaymentMethod extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'invoice_restrict',
    ];

    /**
     * Get payment method by slug.
     *
     * @param string $slug
     * @param bool $soft
     *
     * @return mixed
     */
    public static function findBySlug($slug, $soft = false)
    {
        $query = self::where('slug', $slug);

        return $soft ? $query->first() : $query->firstOrFail();
    }

    public static function paymentInAdvance($payment_method_id)
    {
        return self::where('id', $payment_method_id)
                ->whereIn('slug', static::getAdvancedPaymentMethods())->count() > 0;
    }

    /**
     * Verify whether this payment method is in advance.
     *
     * @return bool
     */
    public function isInAdvance()
    {
        return in_array($this->slug, static::getAdvancedPaymentMethods());
    }

    /**
     * Check if payment available postponed.
     *
     * @return bool
     */
    public function paymentPostponed()
    {
        return in_array($this->slug, [
            PaymentMethodType::BANK_TRANSFER,
            PaymentMethodType::PREPAID,
            PaymentMethodType::OTHER,
        ]);
    }

    /**
     * Get payment methods considered as in advance.
     *
     * @return array
     */
    public static function getAdvancedPaymentMethods()
    {
        return [
            PaymentMethodType::CASH,
            PaymentMethodType::DEBIT_CARD,
            PaymentMethodType::CASH_CARD,
            PaymentMethodType::PAYU,
        ];
    }
}
