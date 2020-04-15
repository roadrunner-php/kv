<?php

/**
 * Spiral Framework, SpiralScout LLC.
 *
 * @author Valentin Vintsukevich (vvval)
 */

declare(strict_types=1);

namespace Spiral\KV;

use DateTimeInterface;
use Spiral\Goridge\RelayInterface as Relay;
use Spiral\Goridge\RPC;
use Throwable;

class SharedCache implements SharedCacheInterface
{
    protected const PACK_FORMAT = 'P';
    protected const RPC_METHOD  = 'kv';

    /** @var RPC */
    private $rpc;

    /** @var string */
    private $driver;

    /**
     * @param RPC    $rpc
     * @param string $driver
     */
    public function __construct(RPC $rpc, string $driver)
    {
        $this->rpc = $rpc;
        $this->driver = $driver;
    }

    /**
     * @inheritDoc
     */
    public function has(string ...$keys): array
    {
        $response = $this->call('Has', [
            'driver' => $this->driver,
            'keys'   => $keys
        ]);

        if (!is_array($response)) {
            throw new SharedCacheException(sprintf(
                'Response expected to be an array, got %s.',
                gettype($response)
            ));
        }

        return array_merge(array_fill_keys($keys, false), arrayFetchKeys($response, $keys));
    }

    /**
     * @inheritDoc
     */
    public function get(string $key)
    {
        return $this->call('Get', [
            'driver' => $this->driver,
            'key'    => $key
        ]);
    }

    /**
     * @inheritDoc
     */
    public function mGet(string ...$keys): array
    {
        $response = $this->call('MGet', [
            'driver' => $this->driver,
            'keys'   => $keys
        ]);

        if (!is_array($response)) {
            throw new SharedCacheException(sprintf(
                'Response expected to be an array, got %s.',
                gettype($response)
            ));
        }

        return array_merge(array_fill_keys($keys, null), arrayFetchKeys($response, $keys));
    }

    /**
     * @inheritDoc
     */
    public function set(Item ...$items): void
    {
        $this->call('Set', [
            'driver' => $this->driver,
            'items'  => array_map(static function (Item $item): array {
                return [
                    'key'   => $item->key,
                    'value' => $item->value,
                    'ttl'   => $item->ttl ? $item->ttl->format(DATE_RFC3339) : null,
                ];
            }, $items)
        ]);
    }

    /**
     * @inheritDoc
     */
    public function mExpire(DateTimeInterface $ttl, string ...$keys): void
    {
        $this->call('MExpire', [
            'driver' => $this->driver,
            'keys'   => $keys,
            'ttl'    => $ttl->format(DATE_RFC3339)
        ]);
    }

    /**
     * @inheritDoc
     */
    public function ttl(string ...$keys): array
    {
        $response = $this->call('TTL', [
            'driver' => $this->driver,
            'keys'   => $keys
        ]);

        if (!is_array($response)) {
            throw new SharedCacheException(sprintf(
                'Response expected to be an array, got %s.',
                gettype($response)
            ));
        }

        return array_merge(array_fill_keys($keys, null), arrayFetchKeys($response, $keys));
    }

    /**
     * @inheritDoc
     */
    public function delete(string ...$keys): void
    {
        $this->call('Delete', [
            'driver' => $this->driver,
            'keys'   => $keys
        ]);
    }

    /**
     * @inheritDoc
     */
    public function close(string $driver): void
    {
        $this->call('Close', [
            'driver' => $this->driver
        ]);
    }

    /**
     * @param string $method
     * @param array  $payload
     * @return mixed
     * @throws SharedCacheException
     */
    private function call(string $method, array $payload)
    {
        try {
            return $this->rpc->call(
                static::RPC_METHOD . '.' . $method,
                pack(static::PACK_FORMAT, $payload),
                Relay::PAYLOAD_RAW
            );
        } catch (Throwable $e) {
            throw new SharedCacheException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
