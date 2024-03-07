<?php

namespace Tests\Unit\App\Helpers;

use Tests\TestCase;

class FormattedQuantityTest extends TestCase
{
    /** @test */
    public function formatted_to_integer()
    {
        $this->assertFormattedQuantity('5', 5000);
    }

    /** @test */
    public function formatted_to_first_decimal()
    {
        $this->assertFormattedQuantity('1,5', 1500);
    }

    /** @test */
    public function formatted_to_second_decimal()
    {
        $this->assertFormattedQuantity('1,55', 1550);
    }

    /** @test */
    public function formatted_to_third_decimal()
    {
        $this->assertFormattedQuantity('1,555', 1555);
    }

    protected function assertFormattedQuantity($expected_format, $quantity)
    {
        $this->assertSame($expected_format, formatted_quantity($quantity));
    }
}
