package kv

import (
	"context"
)

// Driver provides the ability to init one or multiple storage partitions.
type Driver interface {
	// Init initialize storage based on provided arguments.
	Init(StorageConfig) (Storage, error)
}

// Item represents general storage item
type Item struct {
	// Key of item
	Key string
	// Value of item
	Value string
	// live until time provided by TTL in RFC 3339 format
	TTL string
}
// Storage represents single abstract storage.
type Storage interface {
	// Has checks if value exists.
	Has(ctx context.Context, args ...string) (map[string]bool, error)

	// Get loads value content into a byte slice.
	Get(ctx context.Context, key string) ([]byte, error)

	// MGet loads content of multiple values
	// If there are no values for keys, key will no be in the map
	MGet(ctx context.Context, args ...string) (map[string]interface{}, error)

	// Set used to upload item to KV with TTL
	// 0 value in TTL means no TTL
	Set(ctx context.Context, items ...Item) error

	// MExpire sets the TTL for multiply keys
	MExpire(ctx context.Context, items ...Item) error

	// TTL return the rest time to live for provided keys
	// Not supported for the memcached and boltdb
	TTL(ctx context.Context, keys ...string) (map[string]interface{}, error)

	// Delete one or multiple keys.
	Delete(ctx context.Context, args ...string) error

	// Close closes the storage and underlying resources.
	Close() error
}
