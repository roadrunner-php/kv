package kv

import (
	"net"
	"net/rpc"
	"testing"
	"time"

	flatbuffers "github.com/google/flatbuffers/go"
	"github.com/spiral/goridge/v2"
	"github.com/spiral/kv/payload/generated"
	"github.com/stretchr/testify/assert"
)

func makePayload(b *flatbuffers.Builder, storage string, items []Item) []byte {
	b.Reset()

	storageOffset := b.CreateString(storage)

	////////////////////// ITEMS VECTOR ////////////////////////////
	offset := make([]flatbuffers.UOffsetT, len(items))
	for i := len(items) - 1; i >= 0; i-- {
		offset[i] = serializeItems(b, items[i])
	}

	generated.PayloadStartItemsVector(b, len(offset))

	for i := len(offset) - 1; i >= 0; i-- {
		b.PrependUOffsetT(offset[i])
	}

	itemsOffset := b.EndVector(len(offset))
	///////////////////////////////////////////////////////////////////

	generated.PayloadStart(b)
	generated.PayloadAddItems(b, itemsOffset)
	generated.PayloadAddStorage(b, storageOffset)

	finalOffset := generated.PayloadEnd(b)

	b.Finish(finalOffset)

	return b.Bytes[b.Head():]
}

func serializeItems(b *flatbuffers.Builder, item Item) flatbuffers.UOffsetT {
	key := b.CreateString(item.Key)
	val := b.CreateString(item.Value)
	ttl := b.CreateString(item.TTL)

	generated.ItemStart(b)

	generated.ItemAddKey(b, key)
	generated.ItemAddValue(b, val)
	generated.ItemAddTimeout(b, ttl)

	return generated.ItemEnd(b)
}

func TestSimple(t *testing.T) {
	conn, err := net.Dial("tcp", "127.0.0.1:6001")
	if err != nil {
		panic(err)
	}

	client := rpc.NewClientWithCodec(goridge.NewClientCodec(conn))

	b := flatbuffers.NewBuilder(100)
	res := make(map[string]bool)
	d := makePayload(b, "redis", []Item{{
		Key:   "key",
		Value: "",
		TTL:   "",
	}})

	err = client.Call("kv.Has", d, &res)
	if err != nil {
		panic(err)
	}
}

func TestRpcServer_Get(t *testing.T) {
	conn, err := net.Dial("tcp", "127.0.0.1:6001")
	if err != nil {
		panic(err)
	}

	client := rpc.NewClientWithCodec(goridge.NewClientCodec(conn))

	b := flatbuffers.NewBuilder(100)
	res := make(map[string]bool)
	d := makePayload(b, "redis", []Item{{
		Key: "key",
	}})

	err = client.Call("kv.Get", d, &res)
	if err != nil {
		panic(err)
	}
}

func TestRpcServer_Delete(t *testing.T) {
	conn, err := net.Dial("tcp", "127.0.0.1:6001")
	if err != nil {
		panic(err)
	}

	client := rpc.NewClientWithCodec(goridge.NewClientCodec(conn))

	b := flatbuffers.NewBuilder(100)
	res := make(map[string]bool)
	d := makePayload(b, "redis", []Item{{
		Key: "key",
	}})

	err = client.Call("kv.Delete", d, &res)
	if err != nil {
		panic(err)
	}
}

func TestRpcServer_MExpire(t *testing.T) {
	conn, err := net.Dial("tcp", "127.0.0.1:6001")
	if err != nil {
		panic(err)
	}

	client := rpc.NewClientWithCodec(goridge.NewClientCodec(conn))

	b := flatbuffers.NewBuilder(100)
	res := false
	d := makePayload(b, "redis", []Item{{
		Key:   "key",
		Value: "value",
		TTL:   time.Now().Add(time.Second * 5).Format(time.RFC3339),
	}})

	err = client.Call("kv.MExpire", d, &res)
	assert.NoError(t, err)
	assert.True(t, res)
}

func TestRpcServer_TTL(t *testing.T) {
	conn, err := net.Dial("tcp", "127.0.0.1:6001")
	if err != nil {
		panic(err)
	}

	client := rpc.NewClientWithCodec(goridge.NewClientCodec(conn))

	b := flatbuffers.NewBuilder(100)
	res := make(map[string]interface{})
	d := makePayload(b, "redis", []Item{{
		Key: "key",
		TTL: time.Now().Add(time.Second * 5).Format(time.RFC3339),
	}})

	err = client.Call("kv.TTL", d, &res)
	assert.NoError(t, err)
}

func TestRpcServer_Set(t *testing.T) {
	conn, err := net.Dial("tcp", "127.0.0.1:6001")
	if err != nil {
		panic(err)
	}

	client := rpc.NewClientWithCodec(goridge.NewClientCodec(conn))

	b := flatbuffers.NewBuilder(100)
	res := false
	d := makePayload(b, "redis", []Item{{
		Key:   "key",
		Value: "value",
		TTL:   time.Now().Add(time.Second * 5).Format(time.RFC3339),
	}})

	err = client.Call("kv.Set", d, &res)
	assert.NoError(t, err)
	assert.True(t, res)
}

func TestRpcServer_Close(t *testing.T) {
	conn, err := net.Dial("tcp", "127.0.0.1:6001")
	if err != nil {
		panic(err)
	}

	client := rpc.NewClientWithCodec(goridge.NewClientCodec(conn))

	res := false

	err = client.Call("kv.Close", "redis", &res)
	if err != nil {
		panic(err)
	}

	assert.True(t, res)
}
