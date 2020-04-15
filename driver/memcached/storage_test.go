package memcached

import (
	"context"
	"github.com/spiral/kv"
	"github.com/stretchr/testify/assert"
	"strconv"
	"sync"
	"testing"
	"time"
)

func initStorage() kv.Storage {
	return NewMemcachedClient("localhost:11211")
}

func cleanup(t *testing.T, s kv.Storage, keys ...string) {
	err := s.Delete(context.Background(), keys...)
	if err != nil {
		t.Fatalf("error during cleanup: %s", err.Error())
	}
}

func TestStorage_Has(t *testing.T) {
	s := initStorage()

	ctx := context.Background()

	v, err := s.Has(ctx, "key")
	assert.NoError(t, err)
	assert.False(t, v["key"])
}

func TestStorage_Has_Set_Has(t *testing.T) {
	s := initStorage()
	defer func() {
		cleanup(t, s, "key", "key2")
		if err := s.Close(); err != nil {
			panic(err)
		}
	}()

	ctx := context.Background()
	v, err := s.Has(ctx, "key")
	assert.NoError(t, err)
	// no such key
	assert.False(t, v["key"])

	assert.NoError(t, s.Set(ctx, kv.Item{
		Key:   "key",
		Value: "hello world",
		TTL:   0,
	}, kv.Item{
		Key:   "key2",
		Value: "hello world",
		TTL:   0,
	}))

	v, err = s.Has(ctx, "key", "key2")
	assert.NoError(t, err)
	// no such key
	assert.True(t, v["key"])
	assert.True(t, v["key2"])
}

func TestStorage_Has_Set_MGet(t *testing.T) {
	s := initStorage()
	defer func() {
		cleanup(t, s, "key", "key2")
		if err := s.Close(); err != nil {
			panic(err)
		}
	}()

	ctx := context.Background()
	v, err := s.Has(ctx, "key")
	assert.NoError(t, err)
	// no such key
	assert.False(t, v["key"])

	assert.NoError(t, s.Set(ctx, kv.Item{
		Key:   "key",
		Value: "hello world",
		TTL:   0,
	}, kv.Item{
		Key:   "key2",
		Value: "hello world",
		TTL:   0,
	}))

	v, err = s.Has(ctx, "key", "key2")
	assert.NoError(t, err)
	// no such key
	assert.True(t, v["key"])
	assert.True(t, v["key2"])

	res, err := s.MGet(ctx, "key", "key2")
	assert.NoError(t, err)
	assert.Len(t, res, 2)
}

func TestStorage_Has_Set_Get(t *testing.T) {
	s := initStorage()
	defer func() {
		cleanup(t, s, "key", "key2")
		if err := s.Close(); err != nil {
			panic(err)
		}
	}()

	ctx := context.Background()
	v, err := s.Has(ctx, "key")
	assert.NoError(t, err)
	// no such key
	assert.False(t, v["key"])

	assert.NoError(t, s.Set(ctx, kv.Item{
		Key:   "key",
		Value: "hello world",
		TTL:   0,
	}, kv.Item{
		Key:   "key2",
		Value: "hello world",
		TTL:   0,
	}))

	v, err = s.Has(ctx, "key", "key2")
	assert.NoError(t, err)
	// no such key
	assert.True(t, v["key"])
	assert.True(t, v["key2"])

	res, err := s.Get(ctx, "key")
	assert.NoError(t, err)

	if string(res) != "hello world" {
		t.Fatal("wrong value by key")
	}
}

func TestStorage_Set_Del_Get(t *testing.T) {
	s := initStorage()
	defer func() {
		cleanup(t, s, "key", "key2")
		if err := s.Close(); err != nil {
			panic(err)
		}
	}()

	ctx := context.Background()
	v, err := s.Has(ctx, "key")
	assert.NoError(t, err)
	// no such key
	assert.False(t, v["key"])

	assert.NoError(t, s.Set(ctx, kv.Item{
		Key:   "key",
		Value: "hello world",
		TTL:   0,
	}, kv.Item{
		Key:   "key2",
		Value: "hello world",
		TTL:   0,
	}))

	v, err = s.Has(ctx, "key", "key2")
	assert.NoError(t, err)
	// no such key
	assert.True(t, v["key"])
	assert.True(t, v["key2"])

	// check that keys are present
	res, err := s.MGet(ctx, "key", "key2")
	assert.NoError(t, err)
	assert.Len(t, res, 2)

	assert.NoError(t, s.Delete(ctx, "key", "key2"))
	// check that keys are not present
	res, err = s.MGet(ctx, "key", "key2")
	assert.NoError(t, err)
	assert.Len(t, res, 0)
}

func TestStorage_Set_GetM(t *testing.T) {
	s := initStorage()
	ctx := context.Background()

	defer func() {
		cleanup(t, s, "key", "key2")

		if err := s.Close(); err != nil {
			t.Fatal(err)
		}
	}()

	v, err := s.Has(ctx, "key")
	assert.NoError(t, err)
	assert.False(t, v["key"])

	assert.NoError(t, s.Set(ctx, kv.Item{
		Key:   "key",
		Value: "hello world",
		TTL:   0,
	}, kv.Item{
		Key:   "key2",
		Value: "hello world",
		TTL:   0,
	}))

	res, err := s.MGet(ctx, "key", "key2")
	assert.NoError(t, err)
	assert.Len(t, res, 2)
}

func TestStorage_MExpire_TTL(t *testing.T) {
	s := initStorage()
	ctx := context.Background()
	defer func() {
		cleanup(t, s, "key", "key2")
		if err := s.Close(); err != nil {
			t.Fatal(err)
		}
	}()

	// ensure that storage is clean
	v, err := s.Has(ctx, "key", "key2")
	assert.NoError(t, err)
	assert.False(t, v["key"])
	assert.False(t, v["key2"])

	assert.NoError(t, s.Set(ctx, kv.Item{
		Key:   "key",
		Value: "hello world",
		TTL:   0,
	}, kv.Item{
		Key:   "key2",
		Value: "hello world",
		TTL:   0,
	}))
	// set timeout to 5 sec
	assert.NoError(t, s.MExpire(ctx, 2, "key", "key2"))

	time.Sleep(time.Second * 3)
	_, err = s.TTL(ctx, "key", "key2")
	// not implemented
	assert.Error(t, err)

	// ensure that keys are expired
	v, err = s.Has(ctx, "key", "key2")
	assert.NoError(t, err)
	assert.False(t, v["key"])
	assert.False(t, v["key2"])
}

func TestNilAndWrongArgs(t *testing.T) {
	s := initStorage()
	ctx := context.Background()
	defer func() {
		cleanup(t, s, "key")
		if err := s.Close(); err != nil {
			panic(err)
		}
	}()

	// check
	v, err := s.Has(ctx, "key")
	assert.NoError(t, err)
	assert.False(t, v["key"])

	_, err = s.Has(ctx, "")
	assert.Error(t, err)

	_, err = s.Get(ctx, "")
	assert.Error(t, err)

	_, err = s.Get(ctx, " ")
	assert.Error(t, err)

	_, err = s.Get(ctx, "                 ")
	assert.Error(t, err)

	_, err = s.MGet(ctx, "key", "key2", "")
	assert.Error(t, err)

	_, err = s.MGet(ctx, "key", "key2", "   ")
	assert.Error(t, err)

	assert.Error(t, s.Set(ctx, kv.Item{}))

	err = s.Delete(ctx, "")
	assert.Error(t, err)

	err = s.Delete(ctx, "key", "")
	assert.Error(t, err)

	err = s.Delete(ctx, "key", "     ")
	assert.Error(t, err)

	err = s.Delete(ctx, "key")
	assert.NoError(t, err)
}

func TestStorage_SetExpire_TTL(t *testing.T) {
	s := initStorage()
	ctx := context.Background()
	defer func() {
		cleanup(t, s, "key", "key2")
		if err := s.Close(); err != nil {
			t.Fatal(err)
		}
	}()

	// ensure that storage is clean
	v, err := s.Has(ctx, "key", "key2")
	assert.NoError(t, err)
	assert.False(t, v["key"])
	assert.False(t, v["key2"])

	// set timeout to 5 sec
	assert.NoError(t, s.Set(ctx, kv.Item{
		Key:   "key",
		Value: "value",
		TTL:   5,
	},
		kv.Item{
			Key:   "key2",
			Value: "value",
			TTL:   5,
		}))

	time.Sleep(time.Second * 2)
	_, err = s.TTL(ctx, "key", "key2")
	// not implemented
	assert.Error(t, err)

	time.Sleep(time.Second * 4)
	// ensure that keys are expired
	v, err = s.Has(ctx, "key", "key2")
	assert.NoError(t, err)
	assert.False(t, v["key"])
	assert.False(t, v["key2"])
}

func TestConcurrentReadWriteTransactions(t *testing.T) {
	s := initStorage()
	defer func() {
		cleanup(t, s, "key", "key2")
		if err := s.Close(); err != nil {
			t.Fatal(err)
		}
	}()

	ctx := context.Background()
	v, err := s.Has(ctx, "key")
	assert.NoError(t, err)
	// no such key
	assert.False(t, v["key"])

	assert.NoError(t, s.Set(ctx, kv.Item{
		Key:   "key",
		Value: "hello world",
		TTL:   0,
	}, kv.Item{
		Key:   "key2",
		Value: "hello world",
		TTL:   0,
	}))

	v, err = s.Has(ctx, "key", "key2")
	assert.NoError(t, err)
	// no such key
	assert.True(t, v["key"])
	assert.True(t, v["key2"])

	wg := &sync.WaitGroup{}
	wg.Add(3)

	m := &sync.RWMutex{}
	// concurrently set the keys
	go func(s kv.Storage) {
		defer wg.Done()
		for i := 0; i <= 1000; i++ {
			m.Lock()
			// set is writable transaction
			// it should stop readable
			assert.NoError(t, s.Set(ctx, kv.Item{
				Key:   "key" + strconv.Itoa(i),
				Value: "hello world" + strconv.Itoa(i),
				TTL:   0,
			}, kv.Item{
				Key:   "key2" + strconv.Itoa(i),
				Value: "hello world" + strconv.Itoa(i),
				TTL:   0,
			}))
			m.Unlock()
		}
	}(s)

	// should be no errors
	go func(s kv.Storage) {
		defer wg.Done()
		for i := 0; i <= 1000; i++ {
			m.RLock()
			v, err = s.Has(ctx, "key")
			assert.NoError(t, err)
			// no such key
			assert.True(t, v["key"])
			m.RUnlock()
		}
	}(s)

	// should be no errors
	go func(s kv.Storage) {
		defer wg.Done()
		for i := 0; i <= 1000; i++ {
			m.Lock()
			err = s.Delete(ctx, "key" + strconv.Itoa(i))
			assert.NoError(t, err)
			m.Unlock()
		}
	}(s)

	wg.Wait()
}