package redis

import (
	"context"
	"errors"
	"strings"
	"sync"
	"time"

	"github.com/go-redis/redis/v7"
	"github.com/sirupsen/logrus"
	"github.com/spiral/kv"
)

// Redis K/V storage.
type Storage struct {
	// config for RR integration
	cfg *Config
	// redis client
	client redis.UniversalClient

	// wait group is used to prevent Serve for early exit
	// is used together with stop
	// BUT NOT USED IN GENERAL API, ONLY FOR RoadRunner
	wg *sync.WaitGroup
}

func NewRedisClient(options *redis.UniversalOptions) kv.Storage {
	universalClient := redis.NewUniversalClient(options)
	s := &Storage{
		cfg:    &Config{log: logrus.StandardLogger()},
		client: universalClient,
	}
	return s
}

func (s *Storage) Init(config *Config) (bool, error) {
	if config == nil {
		return false, kv.ErrNoConfig
	}
	s.cfg = config
	return true, nil
}

func (s *Storage) Serve() error {
	// init the wait group to prevent Serve to exit early, before RR called Stop
	wg := &sync.WaitGroup{}
	wg.Add(1)

	options := &redis.UniversalOptions{
		Addrs:    s.cfg.Addr,
		DB:       s.cfg.Db,
		Password: s.cfg.Password,
		// The sentinel master name.
		// Only failover clients.
		MasterName: s.cfg.Master,
	}
	s.wg = wg
	s.client = redis.NewUniversalClient(options)

	// Wait here
	s.wg.Wait()
	return nil
}

func (s Storage) Stop() {
	defer s.wg.Done()
	err := s.Close()
	if err != nil {
		s.cfg.log.Error("error during redis stop: ", err)
	}
}

// Has checks if value exists.
func (s Storage) Has(ctx context.Context, keys ...string) (map[string]bool, error) {
	if keys == nil {
		return nil, kv.ErrNoKeys
	}
	m := make(map[string]bool, len(keys))
	for _, key := range keys {
		keyTrimmed := strings.TrimSpace(key)
		if keyTrimmed == "" {
			return nil, kv.ErrEmptyKey
		}

		exist, err := s.client.Exists(key).Result()
		if err != nil {
			return nil, err
		}
		switch exist {
		case 1:
			m[key] = true
		}
	}
	return m, nil
}

// Get loads key content into slice.
func (s Storage) Get(ctx context.Context, key string) ([]byte, error) {
	// to get cases like "  "
	keyTrimmed := strings.TrimSpace(key)
	if keyTrimmed == "" {
		return nil, kv.ErrEmptyKey
	}
	return s.client.Get(key).Bytes()
}

// MGet loads content of multiple values (some values might be skipped).
// https://redis.io/commands/mget
// Returns slice with the interfaces with values
func (s Storage) MGet(ctx context.Context, keys ...string) (map[string]interface{}, error) {
	if keys == nil {
		return nil, kv.ErrNoKeys
	}

	// should not be empty keys
	for _, key := range keys {
		keyTrimmed := strings.TrimSpace(key)
		if keyTrimmed == "" {
			return nil, kv.ErrEmptyKey
		}
	}

	m := make(map[string]interface{}, len(keys))

	for _, k := range keys {
		val, err := s.client.Get(k).Result()
		if err != nil {
			return nil, err
		}
		m[k] = val
	}

	return m, nil
}

// Set sets value with the TTL in seconds
// https://redis.io/commands/set
// Redis `SET key value [expiration]` command.
//
// Use expiration for `SETEX`-like behavior.
// Zero expiration means the key has no expiration time.
func (s Storage) Set(ctx context.Context, items ...kv.Item) error {
	if items == nil {
		return kv.ErrNoKeys
	}
	now := time.Now()
	for _, item := range items {
		if item == kv.EmptyItem {
			return kv.ErrEmptyItem
		}

		if item.TTL == "" {
			err := s.client.Set(item.Key, item.Value, 0).Err()
			if err != nil {
				return err
			}
		} else {
			t, err := time.Parse(time.RFC3339, item.TTL)
			if err != nil {
				return err
			}
			err = s.client.Set(item.Key, item.Value, t.Sub(now)).Err()
			if err != nil {
				return err
			}
		}
	}
	return nil
}

// Delete one or multiple keys.
func (s Storage) Delete(ctx context.Context, keys ...string) error {
	if keys == nil {
		return kv.ErrNoKeys
	}

	// should not be empty keys
	for _, key := range keys {
		keyTrimmed := strings.TrimSpace(key)
		if keyTrimmed == "" {
			return kv.ErrEmptyKey
		}
	}
	return s.client.Del(keys...).Err()
}

// https://redis.io/commands/expire
// timeout in RFC3339
func (s Storage) MExpire(ctx context.Context, items ...kv.Item) error {
	now := time.Now()
	for _, item := range items {
		if item.TTL == "" || strings.TrimSpace(item.Key) == "" {
			return errors.New("should set timeout and at least one key")
		}

		t, err := time.Parse(time.RFC3339, item.TTL)
		if err != nil {
			return err
		}

		// t guessed to be in future
		// for Redis we use t.Sub, it will result in seconds, like 4.2s
		s.client.Expire(item.Key, t.Sub(now))
	}

	return nil
}

// https://redis.io/commands/ttl
// return time in seconds (float64) for a given keys
func (s Storage) TTL(ctx context.Context, keys ...string) (map[string]interface{}, error) {
	if keys == nil {
		return nil, kv.ErrNoKeys
	}

	// should not be empty keys
	for _, key := range keys {
		keyTrimmed := strings.TrimSpace(key)
		if keyTrimmed == "" {
			return nil, kv.ErrEmptyKey
		}
	}

	m := make(map[string]interface{}, len(keys))

	for _, key := range keys {
		duration, err := s.client.TTL(key).Result()
		if err != nil {
			return nil, err
		}

		m[key] = duration.Seconds()
	}
	return m, nil
}

// Close closes the storage and underlying resources.
func (s Storage) Close() error {
	return s.client.Close()
}
