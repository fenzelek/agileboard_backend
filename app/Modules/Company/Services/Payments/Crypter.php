<?php

namespace App\Modules\Company\Services\Payments;

use Carbon\Carbon;

class Crypter
{
    const MINUTES = 10;

    public static function encrypt($id, $price)
    {
        $data = [
            'id' => $id,
            'price' => $price,
            'time' => Carbon::now()->toDateTimeString(),
        ];

        return encrypt($data);
    }

    public static function decrypt($checksum)
    {
        try {
            $data = decrypt($checksum);

            if (Carbon::now()->diffInMinutes(Carbon::parse($data['time'])) < self::MINUTES) {
                return $data;
            }
        } catch (\Throwable $e) {
            return false;
        }

        return false;
    }
}
