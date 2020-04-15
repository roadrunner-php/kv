<?php

/**
 * Spiral Framework, SpiralScout LLC.
 *
 * @author Valentin Vintsukevich (vvval)
 */

declare(strict_types=1);

namespace Spiral\KV {
    if (!function_exists('arrayFetchKeys')) {
        /**
         * Fetch array array by given keys.
         * @param array $array
         * @param array $keys
         * @return array
         */
        function arrayFetchKeys(array $array, array $keys)
        {
            return array_intersect_key($array, array_flip($keys));
        }
    }
}
