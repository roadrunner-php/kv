package kv

import "errors"

var EmptyItem = Item{}

var (
	ErrEmptyKey          = errors.New("key can't be empty string")
	ErrEmptyItem         = errors.New("empty Item")
	ErrNoKeys            = errors.New("should provide at least 1 key")
	ErrNoSuchBucket      = errors.New("no such bucket")
	ErrBucketShouldBeSet = errors.New("bucket should be set")
	ErrNoConfig          = errors.New("no config provided")
)
