<?php

/**
 * Normalize price for storing in database. If price is 0 / null it returns it unchanged.
 *
 * @param float $price
 *
 * @return int
 */
function normalize_price($price)
{
    return $price ? ($price * 100) : $price;
}

/**
 * Denormalize price after getting from database. If price is 0 / null it returns it unchanged.
 *
 * @param int $price
 *
 * @return float
 */
function denormalize_price($price)
{
    return $price ? round(($price / (float) 100), 2) : $price;
}

/**
 * Formatting number to output.
 *
 * @param $price
 * @param string $dec_point
 *
 * @return string
 */
function number_format_output($price, $dec_point = ',')
{
    return number_format(denormalize_price($price), 2, $dec_point, '');
}

/**
 * Formatting number to output with thousands separators.
 *
 * @param $price
 *
 * @return string
 */
function separators_format_output($price)
{
    return nonBreakableSpaces(number_format(denormalize_price($price), 2, ',', ' '));
}

/**
 * Formats quantity to be displayed (for example in PDF).
 *
 * @param float $quantity
 *
 * @return int|string
 */
function formatted_quantity($quantity)
{
    $module = 1000;
    $decimals = 0;
    do {
        if ($quantity % $module) {
            ++$decimals;
        }
        $module /= 10;
    } while ($module > 1);

    return nonBreakableSpaces(number_format(denormalize_quantity($quantity), $decimals, ',', ' '));
}

/**
 * Decimal Formatting number to output.
 *
 * @param $price
 *
 * @return string
 */
function number_format_decimal($price)
{
    return number_format($price, 2, ',', '');
}

/**
 * Decimal Formatting number to output with thousands separators.
 *
 * @param $price
 *
 * @return string
 */
function separators_format_decimal($price)
{
    return nonBreakableSpaces(number_format($price, 2, ',', ' '));
}

/**
 * Trimming given param if is string
 * otherwise return otherwise return same as incoming.
 *
 * @param mixed $value
 * @return string
 */
function trimInput($value)
{
    return is_string($value) ? trim($value) : $value;
}

/**
 * Change spaces for non-breaking ones.
 *
 * @param string|int $number
 *
 * @return string|int
 */
function nonBreakableSpaces($number)
{
//    if (is_string($number)) {
//        $number = str_replace(' ', '&nbsp;', $number);
//    }

    return $number;
}

/**
 * Normalize quantity for storing in database. If quantity is 0 / null it returns it unchanged.
 *
 * @param float $quantity
 *
 * @return int
 */
function normalize_quantity($quantity)
{
    return $quantity ? ($quantity * 1000) : $quantity;
}

/**
 * Denormalize quantity after getting from database. If quantity is 0 / null it returns it unchanged.
 *
 * @param int $quantity
 *
 * @return float
 */
function denormalize_quantity($quantity)
{
    return $quantity ? round(($quantity / (float) 1000), 3) : $quantity;
}

/**
 * Calculate activity level.
 *
 * @param int $tracked
 * @param int $activity
 *
 * @return float
 */
function activity_level($tracked, $activity)
{
    return $tracked ? round(100.0 * $activity / $tracked, 2) : 0;
}
