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
use Spiral\Goridge\RelayInterface as Relay;
use Spiral\Goridge\RPC;
use Throwable;

final class SharedCache implements SharedCacheInterface
{
    /** @var RPC */
    private $rpc;

    /** @var Packer */
    private $packer;

    /** @var string */
    private $storage;

    /**
     * @param RPC    $rpc
     * @param Packer $packer
     * @param string $storage
     */
    public function __construct(RPC $rpc, Packer $packer, string $storage)
    {
        $this->rpc = $rpc;
        $this->packer = $packer;
        $this->storage = $storage;
    }

    /**
     * @inheritDoc
     */
    public function has(string ...$keys): array
    {
        $response = $this->call('Has', $this->packer->packKeys($this->storage, ...$keys));

        if (!is_array($response)) {
            throw new SharedCacheException(sprintf(
                'Response expected to be an array, got %s',
                gettype($response)
            ));
        }

        $result = array_fill_keys($keys, false);
        foreach (arrayFetchKeys($response, $keys) as $key => $value) {
            if ((bool)$value) {
                $result[$key] = (bool)$value;
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function get(string ...$keys): array
    {
        $response = $this->call('Get', $this->packer->packKeys($this->storage, ...$keys));

        if (!is_array($response)) {
            throw new SharedCacheException(sprintf(
                'Response expected to be an array, got %s',
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
        $this->call('Set', $this->packer->packItems($this->storage, ...$items));
    }

    /**
     * @inheritDoc
     */
    public function expire(Item ...$items): void
    {
        $this->call('Expire', $this->packer->packItemsTTL($this->storage, ...$items));
    }

    /**
     * @inheritDoc
     */
    public function ttl(string ...$keys): array
    {
        $response = $this->call('TTL', $this->packer->packKeys($this->storage, ...$keys));

        if (!is_array($response)) {
            throw new SharedCacheException(sprintf(
                'Response expected to be an array, got %s',
                gettype($response)
            ));
        }

        $result = array_fill_keys($keys, null);
        foreach (arrayFetchKeys($response, $keys) as $key => $value) {
            if (is_string($value)) {
                $value = DateTimeImmutable::createFromFormat(DateTimeInterface::RFC3339, $value);
            } elseif (is_numeric($value)) {
                try {
                    $value = new DateTimeImmutable("@$value");
                } catch (Throwable $e) {
                    $value = null;
                }
            }

            if ($value instanceof DateTimeInterface) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function delete(string ...$keys): void
    {
        $this->call('Delete', $this->packer->packKeys($this->storage, ...$keys));
    }

    /**
     * @param string $method
     * @param string $payload
     * @return mixed
     * @throws SharedCacheException
     */
    private function call(string $method, string $payload)
    {
        try {
            return $this->rpc->call("kv.$method", $payload, Relay::PAYLOAD_RAW);
        } catch (Throwable $e) {
            throw new SharedCacheException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
