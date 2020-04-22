package boltdb

import (
	"bytes"
	"context"
	"encoding/gob"
	"errors"
	"os"
	"path"
	"strings"
	"sync"
	"time"

	"github.com/sirupsen/logrus"
	"github.com/spiral/kv"
	bolt "go.etcd.io/bbolt"
)

// BoltDB K/V storage.
type Storage struct {
	// db instance
	DB *bolt.DB
	// name should be UTF-8
	bucket []byte

	// config for RR integration
	cfg *Config

	// gc contains key which are contain timeouts
	gc *sync.Map //map[string]int
	// default timeout for cache cleanup is 1 minute
	timeout time.Duration

	// stop is used to stop keys GC and close boltdb connection
	stop chan struct{}
	// wait group is used to prevent Serve for early exit
	// is used together with stop
	// BUT NOT USED IN GENERAL API, ONLY FOR RoadRunner
	wg *sync.WaitGroup
}

// NewBoltClient instantiate new BOLTDB client
// The parameters are:
// path string 			 -- path to database file (can be placed anywhere), if file is not exist, it will be created
// perm os.FileMode 	 -- file permissions, for example 0777
// options *bolt.Options -- boltDB options, such as timeouts, noGrows options and other
// bucket string 		 -- name of the bucket to use, should be UTF-8
func NewBoltClient(path string, perm os.FileMode, options *bolt.Options, bucket string, TTL time.Duration) (kv.Storage, error) {
	db, err := bolt.Open(path, perm, options)
	if err != nil {
		return nil, err
	}

	// bucket should be SET
	if bucket == "" {
		return nil, kv.ErrBucketShouldBeSet
	}

	// create bucket if it does not exist
	// tx.Commit invokes via the db.Update
	err = db.Update(func(tx *bolt.Tx) error {
		_, err = tx.CreateBucketIfNotExists([]byte(bucket))
		if err != nil {
			return err
		}
		return nil
	})
	if err != nil {
		return nil, err
	}

	// if TTL is not set, make it default
	if TTL == 0 {
		TTL = time.Minute
	}

	s := &Storage{
		DB:      db,
		bucket:  []byte(bucket),
		stop:    make(chan struct{}),
		timeout: TTL,
		gc:      &sync.Map{},
		// config here is needed only for the log
		cfg: &Config{
			log: logrus.StandardLogger(),
		},
	}

	// start the TTL gc
	go s.gcPhase()

	return s, nil
}

func (s *Storage) Init(config *Config) (bool, error) {
	if config == nil {
		return false, kv.ErrNoConfig
	}
	s.cfg = config
	return true, nil
}

// TODO support boltdb options
func (s *Storage) Serve() error {
	// init the wait group to prevent Serve to exit early, before RR called Stop
	wg := &sync.WaitGroup{}
	wg.Add(1)

	db, err := bolt.Open(path.Join(s.cfg.Dir, s.cfg.File), os.FileMode(s.cfg.Permissions), nil)

	// create bucket if it does not exist
	// tx.Commit invokes via the db.Update
	err = db.Update(func(tx *bolt.Tx) error {
		_, err = tx.CreateBucketIfNotExists([]byte(s.cfg.Bucket))
		if err != nil {
			return err
		}
		return nil
	})
	if err != nil {
		return err
	}

	s.wg = wg
	s.DB = db
	s.bucket = []byte(s.cfg.Bucket)
	s.stop = make(chan struct{})
	s.timeout = time.Duration(s.cfg.Ttl) * time.Second
	s.gc = &sync.Map{}

	// start the TTL gc
	go s.gcPhase()

	// Wait here
	s.wg.Wait()

	return nil
}

func (s Storage) Stop() {
	defer s.wg.Done()
	err := s.Close()
	if err != nil {
		s.cfg.log.Error("error during the stopping boltdb", err)
	}
}

func (s Storage) Has(ctx context.Context, keys ...string) (map[string]bool, error) {
	if keys == nil {
		return nil, kv.ErrNoKeys
	}

	m := make(map[string]bool, len(keys))

	// this is readable transaction
	err := s.DB.View(func(tx *bolt.Tx) error {
		// Get retrieves the value for a key in the bucket.
		// Returns a nil value if the key does not exist or if the key is a nested bucket.
		// The returned value is only valid for the life of the transaction.
		for _, key := range keys {
			keyTrimmed := strings.TrimSpace(key)
			if keyTrimmed == "" {
				return kv.ErrEmptyKey
			}
			b := tx.Bucket(s.bucket)
			if b == nil {
				return kv.ErrNoSuchBucket
			}
			exist := b.Get([]byte(key))
			if exist != nil {
				m[key] = true
			}
		}
		return nil
	})
	if err != nil {
		return nil, err
	}

	return m, nil
}

// Get retrieves the value for a key in the bucket.
// Returns a nil value if the key does not exist or if the key is a nested bucket.
// The returned value is only valid for the life of the transaction.
func (s Storage) Get(ctx context.Context, key string) ([]byte, error) {
	// to get cases like "  "
	keyTrimmed := strings.TrimSpace(key)
	if keyTrimmed == "" {
		return nil, kv.ErrEmptyKey
	}

	var val []byte
	err := s.DB.View(func(tx *bolt.Tx) error {
		b := tx.Bucket(s.bucket)
		if b == nil {
			return kv.ErrNoSuchBucket
		}
		val = b.Get([]byte(key))

		// try to decode values
		if val != nil {
			buf := bytes.NewReader(val)
			decoder := gob.NewDecoder(buf)

			i := kv.Item{}
			err := decoder.Decode(&i)
			if err != nil {
				// unsafe (w/o runes) convert
				return err
			}

			// set the value
			val = []byte(i.Value)
		}
		return nil
	})
	if err != nil {
		return nil, err
	}

	return val, nil
}

func (s Storage) MGet(ctx context.Context, keys ...string) (map[string]interface{}, error) {
	// defence
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

	err := s.DB.View(func(tx *bolt.Tx) error {
		b := tx.Bucket(s.bucket)
		if b == nil {
			return kv.ErrNoSuchBucket
		}

		for _, key := range keys {
			value := b.Get([]byte(key))
			if value != nil {
				m[key] = value
			}
		}

		return nil
	})
	if err != nil {
		return nil, err
	}

	return m, nil
}

// Set puts the K/V to the bolt
func (s Storage) Set(ctx context.Context, items ...kv.Item) error {
	if items == nil {
		return kv.ErrNoKeys
	}

	// start writable transaction
	tx, err := s.DB.Begin(true)
	if err != nil {
		return err
	}
	defer func() {
		err = tx.Commit()
		if err != nil {
			errRb := tx.Rollback()
			if errRb != nil {
				s.cfg.log.Errorf("during the commit, Rollback error occurred: commit error: %s, "+
					"rollback error: %s", err.Error(), errRb.Error())
			}
		}
	}()

	// TODO use flatbuffers here to fast encode and decode data
	b := tx.Bucket(s.bucket)
	for _, item := range items {
		// performance note: pass a prepared bytes slice with initial cap
		// we can't move buf and gob out of loop, because we need to clear both from data
		// but gob will contain (w/o re-init) the past data
		buf := bytes.Buffer{}
		encoder := gob.NewEncoder(&buf)
		if item == kv.EmptyItem {
			return kv.ErrEmptyItem
		}

		err = encoder.Encode(&item)
		if err != nil {
			return err
		}
		// buf.Bytes will copy the underlying slice. Take a look in case of performance problems
		err = b.Put([]byte(item.Key), buf.Bytes())
		if err != nil {
			return err
		}

		// if there are no errors, and TTL > 0,  we put the key with timeout to the hashmap, for future check
		// we do not need mutex here, since we use sync.Map
		if item.TTL != "" {
			// check correctness of provided TTL
			_, err := time.Parse(time.RFC3339, item.TTL)
			if err != nil {
				return err
			}
			s.gc.Store(item.Key, item.TTL)
		}

		buf.Reset()
	}

	return nil
}

// Delete all keys from DB
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

	// start writable transaction
	tx, err := s.DB.Begin(true)
	if err != nil {
		return err
	}

	defer func() {
		err = tx.Commit()
		if err != nil {
			errRb := tx.Rollback()
			if errRb != nil {
				s.cfg.log.Errorf("during the commit, Rollback error occurred: commit error: %s, "+
					"rollback error: %s", err.Error(), errRb.Error())
			}
		}
	}()

	b := tx.Bucket(s.bucket)
	if b == nil {
		return kv.ErrNoSuchBucket
	}

	for _, key := range keys {
		err = b.Delete([]byte(key))
		if err != nil {
			return err
		}
	}

	return nil
}

// MExpire sets the expiration time to the key
// If key already has the expiration time, it will be overwritten
func (s Storage) MExpire(ctx context.Context, items ...kv.Item) error {
	for _, item := range items {
		if item.TTL == "" || strings.TrimSpace(item.Key) == "" {
			return errors.New("should set timeout and at least one key")
		}

		// verify provided TTL
		_, err := time.Parse(time.RFC3339, item.TTL)
		if err != nil {
			return err
		}

		s.gc.Store(item.Key, item.TTL)

	}
	return nil
}

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
		if item, ok := s.gc.Load(key); ok {
			// a little bit dangerous operation, but user can't store value other that kv.Item.TTL --> int64
			m[key] = item.(string)
		}
	}
	return m, nil
}

// Close the DB connection
func (s Storage) Close() error {
	// stop the keys GC
	s.stop <- struct{}{}
	return s.DB.Close()
}

// ========================= PRIVATE =================================

func (s Storage) gcPhase() {
	t := time.NewTicker(s.timeout)
	for {
		select {
		case <-t.C:
			// calculate current time before loop started to be fair
			now := time.Now()
			s.gc.Range(func(key, value interface{}) bool {
				k := key.(string)
				v, err := time.Parse(time.RFC3339, value.(string))
				if err != nil {
					return false
				}

				if now.After(v) {
					// time expired
					s.gc.Delete(k)
					err := s.DB.Update(func(tx *bolt.Tx) error {
						b := tx.Bucket(s.bucket)
						if b == nil {
							return kv.ErrNoSuchBucket
						}
						err := b.Delete([]byte(k))
						if err != nil {
							return err
						}
						return nil
					})
					if err != nil {
						s.cfg.log.Error("error during the gc phase of update", err)
						// todo this error is ignored, it means, that timer still be active
						// to prevent this, we need to just invoke t.Stop()
						return false
					}

				}
				return true
			})
		case <-s.stop:
			t.Stop()
			return
		}
	}
}
