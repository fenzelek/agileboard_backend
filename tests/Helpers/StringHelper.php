<?php

namespace Tests\Helpers;

trait StringHelper
{
    /**
     * Verify whether given texts are in exact order as given. Subject must contain all texts
     * to pass.
     *
     * @param array $texts
     * @param string $subject
     * @param bool $ignoreCase
     */
    protected function assertContainsOrdered(array $texts, $subject, $ignoreCase = false)
    {
        $function = $ignoreCase ? 'mb_stripos' : 'mb_strpos';

        $this->assertStringContainsString($texts[0], $subject);
        $offset = 0;
        for ($i = 1, $c = count($texts); $i < $c; ++$i) {
            $posBefore = $function($subject, $texts[$i - 1], $offset);
            $this->assertTrue(
                $posBefore !== false,
                $texts[$i - 1] . ' found in ' . mb_substr($subject, $offset)
            );
            $pos = $function($subject, $texts[$i], $offset + mb_strlen($texts[$i - 1]));
            $this->assertTrue(
                $pos !== false,
                $texts[$i] . ' found in ' . mb_substr($subject, $offset)
            );
            $this->assertGreaterThan(
                $posBefore,
                $pos,
                $texts[$i] . ' is after ' . $texts[$i - 1] . ' in ' . $subject
            );
            $offset = $pos;
        }
    }
}
