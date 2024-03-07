<?php

namespace Tests\Unit\App\Modules\SaleReport\Services\Optima\MazoviaConverter;

use App\Modules\SaleReport\Services\Optima\MazoviaConverter;
use Tests\TestCase;

class ToUtf8Test extends TestCase
{
    /** @test */
    public function it_leaves_standard_characters_not_changed()
    {
        $string = 'abc-ABC12345';

        $mazovia = new MazoviaConverter();
        $this->assertSame($string, $mazovia->toUtf8($string));
    }

    /** @test */
    public function it_can_convert_string_in_mazovia_standard_back_to_utf8()
    {
        $string = 'ąęśćółńźżĄĘŚĆÓŁŃŹŻ';

        $mazovia = new MazoviaConverter();

        $mazovia_string = $mazovia->fromUtf8($string);
        $this->assertSame($string, $mazovia->toUtf8($mazovia_string));
    }
}
