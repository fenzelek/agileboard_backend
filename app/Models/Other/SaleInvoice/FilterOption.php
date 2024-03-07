<?php

namespace App\Models\Other\SaleInvoice;

class FilterOption
{
    const ALL = 'all';
    const PAID = 'paid';
    const NOT_PAID = 'not_paid';
    const PAID_LATE = 'paid_late';
    const DELETED = 'deleted';
    const NOT_DELETED = 'not_deleted';

    public static function all()
    {
        return [
          self::ALL,
          self::PAID,
          self::NOT_PAID,
          self::PAID_LATE,
          self::DELETED,
          self::NOT_DELETED,
        ];
    }

    public static function description()
    {
        return [
            self::ALL => 'wszystkie',
            self::PAID => 'opłacone',
            self::NOT_PAID => 'nieopłacone',
            self::PAID_LATE => 'opłacone po terminie',
            self::DELETED => 'anulowane',
            self::NOT_DELETED => 'nie anulowane',
        ];
    }

    public static function translate($status)
    {
        return array_get(self::description(), $status, $status);
    }

    public static function isAll($status)
    {
        return self::is(self::ALL, $status);
    }

    public static function isNotPaid($status)
    {
        return self::is(self::NOT_PAID, $status);
    }

    public static function is($expected, $status)
    {
        return $expected === $status;
    }
}
