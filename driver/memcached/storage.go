package memcached

import (
	"context"
	"errors"
	"github.com/bradfitz/gomemcache/memcache"
	"github.com/spiral/kv"
	"strings"
	"sync"
)

type Storage struct {
	// config for RR integration
	cfg    *Config
	client *memcache.Client

	// wait group is used to prevent Serve for early exit
	// is used together with stop
	// BUT NOT USED IN GENERAL API, ONLY FOR RoadRunner
	wg *sync.WaitGroup
}

// NewMemcachedClient returns a memcache client using the provided server(s)
// with equal weight. If a server is listed multiple times,
// it gets a proportional amount of weight.
func NewMemcachedClient(url string) kv.Storage {
	m := memcache.New(url)
	return &Storage{
		client: m,
	}
}

// Init is used to copy config from driver via reflection
func (s *Storage) Init(config *Config) (bool, error) {
	if config == nil {
		return false, kv.ErrNoConfig
	}
	s.cfg = config
	return true, nil
}

func (s Storage) Serve() error {
	// init the wait group to prevent Serve to exit early, before RR called Stop
	wg := &sync.WaitGroup{}
	wg.Add(1)
	s.wg = wg
	s.client = memcache.New(s.cfg.Addr...)

	// Wait here
	s.wg.Wait()
	return nil
}

func (s Storage) Stop() {
	defer s.wg.Done()
	err := s.Close()
	if err != nil {
		s.cfg.log.Error("error during the stopping memcached", err)
	}
}

// Has checks the key for existence
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
		exist, err := s.client.Get(key)
		// ErrCacheMiss means that a Get failed because the item wasn't present.
		if err != nil && err != memcache.ErrCacheMiss {
			return nil, err
		}
		if exist != nil {
			m[key] = true
		} else {
			m[key] = false
		}
	}
	return m, nil
}

// Get gets the item for the given key. ErrCacheMiss is returned for a
// memcache cache miss. The key must be at most 250 bytes in length.
func (s Storage) Get(ctx context.Context, key string) ([]byte, error) {
	// to get cases like "  "
	keyTrimmed := strings.TrimSpace(key)
	if keyTrimmed == "" {
		return nil, kv.ErrEmptyKey
	}
	data, err := s.client.Get(key)
	// ErrCacheMiss means that a Get failed because the item wasn't present.
	if err != nil && err != memcache.ErrCacheMiss {
		return nil, err
	}
	if data != nil {
		// return the value by the key
		return data.Value, nil
	}
	// data is nil by some reason and error also nil
	return nil, nil
}

func (s Storage) MGet(ctx context.Context, keys ...string) ([]interface{}, error) {
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

	ret := make([]interface{}, 0, len(keys))
	for _, key := range keys {
		// Here also MultiGet
		data, err := s.client.Get(key)
		// ErrCacheMiss means that a Get failed because the item wasn't present.
		if err != nil && err != memcache.ErrCacheMiss {
			return nil, err
		}
		if data != nil {
			ret = append(ret, data.Value)
		}
	}

	return ret, nil
}

// Set sets the KV pairs. Keys should be 250 bytes maximum
// TTL:
// Expiration is the cache expiration time, in seconds: either a relative
// time from now (up to 1 month), or an absolute Unix epoch time.
// Zero means the Item has no expiration time.
func (s Storage) Set(ctx context.Context, items ...kv.Item) error {
	if items == nil {
		return kv.ErrNoKeys
	}

	for _, kvItem := range items {
		if kvItem == kv.EmptyItem {
			return kv.ErrEmptyItem
		}
		// create an item
		item := &memcache.Item{
			Key: kvItem.Key,
			// unsafe convert
			Value:      []byte(kvItem.Value),
			Flags:      0,
			Expiration: int32(kvItem.TTL),
		}

		// set the item
		err := s.client.Set(item)
		if err != nil {
			return err
		}
	}

	return nil
}

// Expiration is the cache expiration time, in seconds: either a relative
// time from now (up to 1 month), or an absolute Unix epoch time.
// Zero means the Item has no expiration time.
func (s Storage) MExpire(ctx context.Context, timeout int, keys ...string) error {
	if timeout == 0 || keys == nil {
		return kv.ErrEmptyKey
	}

	// Touch updates the expiry for the given key. The seconds parameter is either
	// a Unix timestamp or, if seconds is less than 1 month, the number of seconds
	// into the future at which time the item will expire. Zero means the item has
	// no expiration time. ErrCacheMiss is returned if the key is not in the cache.
	// The key must be at most 250 bytes in length.
	for _, key := range keys {
		err := s.client.Touch(key, int32(timeout))
		if err != nil {
			return err
		}
	}

	return nil
}

// return time in seconds (int32) for a given keys
func (s Storage) TTL(ctx context.Context, keys ...string) (map[string]interface{}, error) {
	return nil, errors.New("not valid request for memcached, see https://github.com/memcached/memcached/issues/239")
}

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

	for _, key := range keys {
		err := s.client.Delete(key)
		// ErrCacheMiss means that a Get failed because the item wasn't present.
		if err != nil && err != memcache.ErrCacheMiss {
			return err
		}
	}
	return nil
}

// Close, there is no close function for memcached
func (s Storage) Close() error {
	return nil
}
