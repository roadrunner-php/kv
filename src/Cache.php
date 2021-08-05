<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\KeyValue;

use Spiral\Goridge\RPC\Codec\ProtobufCodec;
use Spiral\Goridge\RPC\Exception\ServiceException;
use Spiral\Goridge\RPC\RPCInterface;
use Spiral\RoadRunner\KeyValue\DTO\V1\Item;
use Spiral\RoadRunner\KeyValue\DTO\V1\Request;
use Spiral\RoadRunner\KeyValue\DTO\V1\Response;
use Spiral\RoadRunner\KeyValue\Exception\InvalidArgumentException;
use Spiral\RoadRunner\KeyValue\Exception\KeyValueException;
use Spiral\RoadRunner\KeyValue\Exception\NotImplementedException;
use Spiral\RoadRunner\KeyValue\Exception\SerializationException;
use Spiral\RoadRunner\KeyValue\Exception\StorageException;
use Spiral\RoadRunner\KeyValue\Serializer\SerializerAwareInterface;
use Spiral\RoadRunner\KeyValue\Serializer\SerializerAwareTrait;
use Spiral\RoadRunner\KeyValue\Serializer\SerializerInterface;
use Spiral\RoadRunner\KeyValue\Serializer\DefaultSerializer;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Cache implements StorageInterface
{
    use SerializerAwareTrait;

    /**
     * @var string
     */
    private const ERROR_INVALID_STORAGE =
        'Storage "%s" has not been defined. Please make sure your ' .
        'RoadRunner "kv" configuration contains a storage key named "%1$s"';

    /**
     * @var string
     */
    private const ERROR_TTL_NOT_AVAILABLE =
        'Storage "%s" does not support kv.TTL RPC method execution. Please ' .
        'use another driver for the storage if you require this functionality';


    /**
     * @var string
     */
    private const ERROR_CLEAR_NOT_AVAILABLE =
        'RoadRunner does not support kv.Clear RPC method. Please ' .
        'make sure you are using RoadRunner v2.3.1 or higher.';

    /**
     * @var string
     */
    private const ERROR_INVALID_KEY = 'Cache key must be a string, but %s passed';

    /**
     * @var string
     */
    private const ERROR_INVALID_KEYS = 'Cache keys must be an array<string>, but %s passed';

    /**
     * @var string
     */
    private const ERROR_INVALID_VALUES = 'Cache values must be an array<string, mixed>, but %s passed';

    /**
     * @var string
     */
    private const ERROR_INVALID_INTERVAL_ARGUMENT =
        'Cache item ttl (expiration) must be of type int or \DateInterval, but %s passed';

    /**
     * @var RPCInterface
     */
    protected RPCInterface $rpc;

    /**
     * @var string
     */
    private string $name;

    /**
     * @var \DateTimeZone
     */
    protected \DateTimeZone $zone;

    /**
     * @param RPCInterface $rpc
     * @param string $name
     * @param SerializerInterface|null $serializer
     */
    public function __construct(RPCInterface $rpc, string $name, SerializerInterface $serializer = null)
    {
        $this->name = $name;
        $this->rpc = $rpc->withCodec(new ProtobufCodec());
        $this->zone = new \DateTimeZone('UTC');

        $this->setSerializer($serializer ?? new DefaultSerializer());
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     * @throws KeyValueException
     */
    public function getTtl(string $key): ?\DateTimeInterface
    {
        foreach ($this->getMultipleTtl([$key]) as $ttl) {
            assert($ttl instanceof \DateTimeInterface || $ttl === null);

            return $ttl;
        }

        // @codeCoverageIgnoreStart
        // This stmt MUST NOT be executed and is present in the code
        // only for static analysis.
        return null;
        // @codeCoverageIgnoreEnd
    }

    /**
     * {@inheritDoc}
     * @throws KeyValueException
     */
    public function getMultipleTtl(iterable $keys = []): iterable
    {
        /** @psalm-suppress RedundantCondition */
        $this->assertValidKeys($keys);

        try {
            $response = $this->createIndex(
                $this->call('kv.TTL', $this->requestKeys($keys))
            );
        } catch (KeyValueException $e) {
            if (\str_contains($e->getMessage(), '_plugin_ttl')) {
                $message = \sprintf(self::ERROR_TTL_NOT_AVAILABLE, $this->name);
                throw new NotImplementedException($message, (int)$e->getCode(), $e);
            }

            throw $e;
        }

        foreach ($keys as $key) {
            yield $key => isset($response[$key]) && $response[$key]->getTimeout() !== ''
                ? $this->dateFromRfc3339String($response[$key]->getTimeout())
                : null;
        }
    }

    /**
     * @param mixed|array<string> $keys
     * @throws InvalidArgumentException
     */
    private function assertValidKeys($keys): void
    {
        if (! \is_iterable($keys)) {
            throw new InvalidArgumentException(\sprintf(self::ERROR_INVALID_KEYS, \get_debug_type($keys)));
        }
    }

    /**
     * @param Response $response
     * @return array<string, Item>
     */
    private function createIndex(Response $response): array
    {
        $result = [];

        /** @var Item $item */
        foreach ($response->getItems() as $item) {
            $result[$item->getKey()] = $item;
        }

        return $result;
    }

    /**
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     *
     * @param string $method
     * @param Request $request
     * @return Response
     * @throws KeyValueException
     */
    private function call(string $method, Request $request): Response
    {
        try {
            return $this->rpc->call($method, $request, Response::class);
        } catch (ServiceException $e) {
            $message = \str_replace(["\t", "\n"], ' ', $e->getMessage());

            if (\str_contains($message, 'no such storage')) {
                throw new StorageException(\sprintf(self::ERROR_INVALID_STORAGE, $this->name));
            }

            throw new KeyValueException($message, (int)$e->getCode(), $e);
        }
    }

    /**
     * @param iterable<string> $keys
     * @return Request
     * @throws InvalidArgumentException
     */
    private function requestKeys(iterable $keys): Request
    {
        $items = [];

        foreach ($keys as $key) {
            $this->assertValidKey($key);
            $items[] = new Item(['key' => $key]);
        }

        return $this->request($items);
    }

    /**
     * @param array<Item> $items
     * @return Request
     */
    private function request(array $items): Request
    {
        return new Request([
            'storage' => $this->name,
            'items'   => $items,
        ]);
    }

    /**
     * @param string $time
     * @return \DateTimeImmutable
     *
     * @psalm-suppress InvalidFalsableReturnType
     * @psalm-suppress FalsableReturnStatement
     */
    private function dateFromRfc3339String(string $time): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat(\DateTimeInterface::RFC3339, $time, $this->zone);
    }

    /**
     * {@inheritDoc}
     * @throws KeyValueException
     */
    public function get($key, $default = null)
    {
        if (!\is_string($key)) {
            throw new InvalidArgumentException(\sprintf(self::ERROR_INVALID_KEY, \get_debug_type($key)));
        }

        /** @psalm-suppress MixedAssignment */
        foreach ($this->getMultiple([$key], $default) as $value) {
            return $value;
        }

        // @codeCoverageIgnoreStart
        // This stmt MUST NOT be executed and is present in the code
        // only for static analysis.
        return $default;
        // @codeCoverageIgnoreEnd
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-param iterable<string> $keys
     * @psalm-param mixed $default
     * @psalm-suppress MoreSpecificImplementedParamType
     * @throws KeyValueException
     */
    public function getMultiple($keys, $default = null)
    {
        $this->assertValidKeys($keys);

        /** @psalm-suppress MixedArgumentTypeCoercion */
        $items = $this->createIndex(
            $this->call('kv.MGet', $this->requestKeys($keys))
        );

        $serializer = $this->getSerializer();

        foreach ($keys as $key) {
            if (isset($items[$key])) {
                yield $key => $serializer->unserialize($items[$key]->getValue());

                continue;
            }

            yield $key => $default;
        }
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-param string $key
     * @psalm-param mixed $value
     * @psalm-param positive-int|\DateInterval|null $ttl
     * @psalm-suppress MoreSpecificImplementedParamType
     * @throws KeyValueException
     */
    public function set($key, $value, $ttl = null): bool
    {
        $this->assertValidKey($key);

        return $this->setMultiple([$key => $value], $ttl);
    }

    /**
     * @param mixed|string $key
     * @throws InvalidArgumentException
     */
    private function assertValidKey($key): void
    {
        if (! \is_string($key)) {
            throw new InvalidArgumentException(\sprintf(self::ERROR_INVALID_KEY, \get_debug_type($key)));
        }
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-param iterable<string, mixed> $values
     * @psalm-param positive-int|\DateInterval|null $ttl
     * @psalm-suppress MoreSpecificImplementedParamType
     * @throws KeyValueException
     */
    public function setMultiple($values, $ttl = null): bool
    {
        $this->assertValidValues($values);

        $this->call('kv.Set', $this->requestValues($values, $this->ttlToRfc3339String($ttl)));

        return true;
    }

    /**
     * @param mixed|array<string, mixed> $values
     * @throws InvalidArgumentException
     */
    private function assertValidValues($values): void
    {
        if (! \is_iterable($values)) {
            throw new InvalidArgumentException(\sprintf(self::ERROR_INVALID_VALUES, \get_debug_type($values)));
        }
    }

    /**
     * @param iterable<string, mixed> $values
     * @param string $ttl
     * @return Request
     * @throws InvalidArgumentException
     * @throws SerializationException
     */
    private function requestValues(iterable $values, string $ttl): Request
    {
        $items = [];
        $serializer = $this->getSerializer();

        /** @psalm-suppress MixedAssignment */
        foreach ($values as $key => $value) {
            $this->assertValidKey($key);

            $items[] = new Item([
                'key'     => $key,
                'value'   => $serializer->serialize($value),
                'timeout' => $ttl
            ]);
        }

        return $this->request($items);
    }

    /**
     * @param null|int|\DateInterval $ttl
     * @return string
     * @throws InvalidArgumentException
     */
    private function ttlToRfc3339String($ttl): string
    {
        if ($ttl === null) {
            return '';
        }

        if ($ttl instanceof \DateInterval) {
            return $this->now()
                ->add($ttl)
                ->format(\DateTimeInterface::RFC3339)
            ;
        }

        if (\is_int($ttl)) {
            $now = $this->now();

            return $now->setTimestamp($ttl + $now->getTimestamp())
                ->format(\DateTimeInterface::RFC3339)
            ;
        }

        throw new InvalidArgumentException(
            \sprintf(self::ERROR_INVALID_INTERVAL_ARGUMENT, \get_debug_type($ttl))
        );
    }

    /**
     * Please note that this interface currently emulates the behavior of the
     * PSR-20 implementation and may be replaced by the `psr/clock`
     * implementation in future versions.
     *
     * Returns the current time as a DateTimeImmutable instance.
     *
     * @codeCoverageIgnore Ignore time-aware-mutable value.
     *                     Must be covered with a stub.
     * @return \DateTimeImmutable
     * @throws \Exception
     */
    protected function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('NOW', $this->zone);
    }

    /**
     * Note: The current PSR-16 implementation always returns true or
     * exception on error.
     *
     * {@inheritDoc}
     * @throws KeyValueException
     */
    public function delete($key): bool
    {
        $this->assertValidKey($key);

        return $this->deleteMultiple([$key]);
    }

    /**
     * Note: The current PSR-16 implementation always returns true or
     * exception on error.
     *
     * {@inheritDoc}
     *
     * @psalm-param iterable<string> $keys
     * @psalm-suppress MoreSpecificImplementedParamType
     * @throws KeyValueException
     */
    public function deleteMultiple($keys): bool
    {
        $this->assertValidKeys($keys);

        /** @psalm-suppress MixedArgumentTypeCoercion */
        $this->call('kv.Delete', $this->requestKeys($keys));

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        try {
            $this->call('kv.Clears', $this->request([]));
        } catch (KeyValueException $e) {
            if (\str_contains($e->getMessage(), 'can\'t find method kv.Clear')) {
                throw new KeyValueException(self::ERROR_CLEAR_NOT_AVAILABLE, (int)$e->getCode(), $e);
            }

            throw $e;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     * @throws KeyValueException
     */
    public function has($key): bool
    {
        $this->assertValidKey($key);

        /** @var array<Item> $items */
        $items = $this->call('kv.Has', $this->requestKeys([$key]))
            ->getItems();

        foreach ($items as $item) {
            if ($item->getKey() === $key) {
                return true;
            }
        }

        return false;
    }
}
