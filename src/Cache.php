<?php

/**
 * Spiral Framework, SpiralScout LLC.
 *
 * @author Valentin Vintsukevich (vvval)
 */

declare(strict_types=1);

namespace Spiral\KV;

use Spiral\Goridge\RPC\RPC;
use Spiral\Goridge\RPC\RPCInterface;
use Spiral\KV\Exception\StorageException;
use Spiral\KV\Internal\Packer;

final class Cache implements CacheInterface
{
    /**
     * @var RPC
     */
    private RPCInterface $rpc;

    /**
     * @var Packer
     */
    private Packer $packer;

    /**
     * @var string
     */
    private string $storage;

    /**
     * @param RPCInterface $rpc
     * @param Packer $packer
     * @param string $storage
     */
    public function __construct(RPCInterface $rpc, Packer $packer, string $storage)
    {
        $this->rpc = $rpc;
        $this->packer = $packer;
        $this->storage = $storage;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string ...$keys): array
    {
        $response = $this->call('Has', $this->packer->packKeys($this->storage, ...$keys));

        if (! is_array($response)) {
            throw new StorageException(sprintf(
                'Response expected to be an array, got %s',
                gettype($response)
            ));
        }

        $result = array_fill_keys($keys, false);
        foreach ($this->fetchKeys($response, $keys) as $key => $value) {
            if ((bool)$value) {
                $result[$key] = (bool)$value;
            }
        }

        return $result;
    }

    /**
     * Fetch array array by given keys.
     *
     * @param array $array
     * @param array $keys
     * @return array
     */
    private function fetchKeys(array $array, array $keys): array
    {
        return \array_intersect_key($array, \array_flip($keys));
    }

    /**
     * @param string $method
     * @param string $payload
     * @return mixed
     * @throws StorageException
     */
    private function call(string $method, string $payload)
    {
        try {
            return $this->rpc->call("kv.$method", $payload);
        } catch (\Throwable $e) {
            throw new StorageException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function get(string ...$keys): array
    {
        $response = $this->call('Get', $this->packer->packKeys($this->storage, ...$keys));

        if (! is_array($response)) {
            throw new StorageException(sprintf(
                'Response expected to be an array, got %s',
                gettype($response)
            ));
        }

        return array_merge(array_fill_keys($keys, null), $this->fetchKeys($response, $keys));
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

        if (! is_array($response)) {
            throw new StorageException(sprintf(
                'Response expected to be an array, got %s',
                gettype($response)
            ));
        }

        $result = array_fill_keys($keys, null);
        foreach ($this->fetchKeys($response, $keys) as $key => $value) {
            if (is_string($value)) {
                $value = \DateTimeImmutable::createFromFormat(\DateTimeInterface::RFC3339, $value);
            } elseif (is_numeric($value)) {
                try {
                    $value = new \DateTimeImmutable("@$value");
                } catch (\Throwable $e) {
                    $value = null;
                }
            }

            if ($value instanceof \DateTimeInterface) {
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
}
