<?php

namespace Tests\Unit\App\Modules\SaleReport\Services\Optima\MazoviaConverter;

use App\Modules\SaleReport\Services\Optima\MazoviaConverter;
use Tests\TestCase;

class FromUtf8Test extends TestCase
{
    /** @test */
    public function it_leaves_standard_characters_not_changed()
    {
        $string = 'abc-ABC12345';

        $mazovia = new MazoviaConverter();
        $this->assertSame($string, $mazovia->fromUtf8($string));
    }

    /** @test */
    public function it_converts_small_a_in_valid_way()
    {
        $this->verifyLetterConversion('ą', 134);
    }

    /** @test */
    public function it_converts_small_c_in_valid_way()
    {
        $this->verifyLetterConversion('ć', 141);
    }

    /** @test */
    public function it_converts_small_e_in_valid_way()
    {
        $this->verifyLetterConversion('ę', 145);
    }

    /** @test */
    public function it_converts_small_l_in_valid_way()
    {
        $this->verifyLetterConversion('ł', 146);
    }

    /** @test */
    public function it_converts_small_n_in_valid_way()
    {
        $this->verifyLetterConversion('ń', 164);
    }

    /** @test */
    public function it_converts_small_o_in_valid_way()
    {
        $this->verifyLetterConversion('ó', 162);
    }

    /** @test */
    public function it_converts_small_s_in_valid_way()
    {
        $this->verifyLetterConversion('ś', 158);
    }

    /** @test */
    public function it_converts_small_z_zi_in_valid_way()
    {
        $this->verifyLetterConversion('ź', 166);
    }

    /** @test */
    public function it_converts_small_z_zet_in_valid_way()
    {
        $this->verifyLetterConversion('ż', 167);
    }

    /** @test */
    public function it_converts_big_a_in_valid_way()
    {
        $this->verifyLetterConversion('Ą', 143);
    }

    /** @test */
    public function it_converts_big_c_in_valid_way()
    {
        $this->verifyLetterConversion('Ć', 149);
    }

    /** @test */
    public function it_converts_big_e_in_valid_way()
    {
        $this->verifyLetterConversion('Ę', 144);
    }

    /** @test */
    public function it_converts_big_l_in_valid_way()
    {
        $this->verifyLetterConversion('Ł', 156);
    }

    /** @test */
    public function it_converts_big_n_in_valid_way()
    {
        $this->verifyLetterConversion('Ń', 165);
    }

    /** @test */
    public function it_converts_big_o_in_valid_way()
    {
        $this->verifyLetterConversion('Ó', 163);
    }

    /** @test */
    public function it_converts_big_s_in_valid_way()
    {
        $this->verifyLetterConversion('Ś', 152);
    }

    /** @test */
    public function it_converts_big_z_zi_in_valid_way()
    {
        $this->verifyLetterConversion('Ź', 160);
    }

    /** @test */
    public function it_converts_big_z_zet_in_valid_way()
    {
        $this->verifyLetterConversion('Ż', 161);
    }

    protected function verifyLetterConversion($letter, $expected_code)
    {
        $mazovia = new MazoviaConverter();
        $this->assertSame($expected_code, ord($mazovia->fromUtf8($letter)));
    }
}
