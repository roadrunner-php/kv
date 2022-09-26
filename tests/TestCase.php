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
            'int' => [0xDEAD_BEEF],
            'zero int' => [0],
        ];
    }
}
