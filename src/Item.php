<?php

/**
 * Spiral Framework, SpiralScout LLC.
 *
 * @author Valentin Vintsukevich (vvval)
 */

declare(strict_types=1);

namespace Spiral\KV;

use DateTimeImmutable;

class Item
{
    /** @var string */
    public $key = '';

    /** @var mixed */
    public $value;

    /** @var DateTimeImmutable */
    public $ttl;

    /**
     * @param string $key
     * @param mixed  $value
     * @return static
     */
    public static function create(string $key, $value): self
    {
        $item = new self();
        $item->key = $key;
        $item->value = $value;

        return $item;
    }

    /**
     * @param string            $key
     * @param mixed             $value
     * @param DateTimeImmutable $ttl
     * @return static
     */
    public static function withTTL(string $key, $value, DateTimeImmutable $ttl): self
    {
        $item = new self();
        $item->key = $key;
        $item->value = $value;
        $item->ttl = $ttl;

        return $item;
    }
}
