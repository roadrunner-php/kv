<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue\Exception;

use Psr\SimpleCache\InvalidArgumentException as InvalidArgumentExceptionInterface;

class InvalidArgumentException extends KeyValueException implements InvalidArgumentExceptionInterface
{
}
