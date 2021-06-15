<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Spiral\RoadRunner\KeyValue\Tests\Stub\RPCConnectionStub;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param array<string, mixed> $mapping
     * @return RPCConnectionStub
     */
    protected function rpc(array $mapping = []): RPCConnectionStub
    {
        return new RPCConnectionStub($mapping);
    }

    public function valuesDataProvider(): array
    {
        return [
            'null' => [null],
            'string' => [\bin2hex(\random_bytes(64))],
            'empty string' => [''],
            'binary string' => [\random_bytes(64)],
            'int' => [0xDEAD_BEEF],
            'zero int' => [0],
            'int + 1' => [\PHP_INT_MAX + 1],
            'int - 1' => [\PHP_INT_MIN - 1],
            'float' => [.42],
            'zero float' => [.0],
            'float + 1' => [\PHP_FLOAT_MAX + 1],
            'float - 1' => [\PHP_FLOAT_MIN - 1],
            'INF' => [\INF],
            'NAN' => [\NAN],
            'object' => [(object)['property' => 42]],
            'empty object' => [(object)[]],
            'array' => [[1, 2, 3]],
            'empty array' => [[]],
            'hash-map array' => [['key' => 1, 'a' => 2, 'b' => 3]],
            'mixed array' => [['key' => 1, 2, 3, 'key2' => 42]],
            'resource' => [\fopen('php://memory', 'rb')],
        ];
    }
}
