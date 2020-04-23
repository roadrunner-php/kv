package kv

import (
	"net"
	"net/rpc"
	"testing"

	flatbuffers "github.com/google/flatbuffers/go"
	"github.com/spiral/goridge/v2"
	"github.com/spiral/kv/buffer/data"
)


func makeData(b *flatbuffers.Builder, storage string, keys []string, timeout string) []byte {
	b.Reset()

	offset := make([]flatbuffers.UOffsetT, len(keys))

	for i := len(keys) - 1; i >= 0; i-- {
		offset[i] = b.CreateString(keys[i])
	}

	data.SetDataStartItemsVector(b, len(offset))

	for i := len(offset) - 1; i >= 0; i-- {
		b.PrependUOffsetT(offset[i])
	}

	x := b.EndVector(len(offset))

	storageOffset := b.CreateString(storage)
	timeoutOffset := b.CreateString(timeout)

	data.DataStart(b)

	data.DataAddStorage(b, storageOffset)
	data.DataAddTimeout(b, timeoutOffset)
	data.DataAddKeys(b, x)

	dataOffset := data.DataEnd(b)
	b.Finish(dataOffset)

	return b.Bytes[b.Head():]
}

func TestSimple(t *testing.T) {
	conn, err := net.Dial("tcp", "127.0.0.1:6001")
	if err != nil {
		panic(err)
	}

	client := rpc.NewClientWithCodec(goridge.NewClientCodec(conn))

	b := flatbuffers.NewBuilder(100)
	res := make(map[string]bool)
	d := makeData(b, "redis", []string{"1", "2"}, "")

	err = client.Call("kv.Has", d, &res)
	if err != nil {
		panic(err)
	}
}

func TestRpcServer_Get(t *testing.T) {

}