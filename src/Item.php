<?php

/**
 * Spiral Framework, SpiralScout LLC.
 *
 * @author Valentin Vintsukevich (vvval)
 */

declare(strict_types=1);

namespace Spiral\KV;

use DateTimeInterface;

class Item
{
    /** @var string */
    public $key = '';

    /** @var mixed */
    public $value;

    /** @var DateTimeInterface */
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
     * @param DateTimeInterface $ttl
     * @return static
     */
    public static function withTTL(string $key, $value, DateTimeInterface $ttl): self
    {
        $item = new self();
        $item->key = $key;
        $item->value = $value;
        $item->ttl = $ttl;

        return $item;
    }
}
