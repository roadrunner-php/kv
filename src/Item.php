<?php

/**
 * Spiral Framework, SpiralScout LLC.
 *
 * @author Valentin Vintsukevich (vvval)
 */

declare(strict_types=1);

namespace Spiral\KV;

use DateTimeImmutable;
use DateTimeInterface;

class Item
{
    /** @var string */
    private $key;

    /** @var string */
    private $value = '';

    /** @var DateTimeImmutable */
    private $ttl;

    /**
     * Create item with value and ttl.
     * @param string                 $key
     * @param string                 $value
     * @param DateTimeImmutable|null $ttl
     * @return static
     */
    public static function create(string $key, string $value, ?DateTimeImmutable $ttl = null): self
    {
        $item = new self();
        $item->key = $key;
        $item->value = $value;
        $item->ttl = $ttl;

        return $item;
    }

    /**
     * Create item without value.
     * @param string                 $key
     * @param DateTimeImmutable|null $ttl
     * @return static
     */
    public static function ttl(string $key, ?DateTimeImmutable $ttl = null): self
    {
        $item = new self();
        $item->key = $key;
        $item->ttl = $ttl;

        return $item;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getTTL(): string
    {
        if ($this->ttl instanceof DateTimeInterface) {
            return $this->ttl->format(DATE_RFC3339);
        }

        return '';
    }
}
