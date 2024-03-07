<?php

namespace Tests\Helpers;

/**
 * Class Transformer.
 *
 * This trait is used for format the expected data to compare to response from endpoint given in 
 * json.
 */
trait Transformer
{
    protected function mapArrayToExpectedStructure(array $data, array $map)
    {
        foreach ($data as $k => $v) {
            if (! in_array($k, $map)) {
                unset($data[ $k ]);
            }
        }

        return $data;
    }
}
