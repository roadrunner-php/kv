package kv

import (
	"github.com/spiral/goridge/v2"
	"net"
	"net/rpc"
	"testing"
)

func TestSimple(t *testing.T) {
	conn, err := net.Dial("tcp", "127.0.0.1:6001")
	if err != nil {
		panic(err)
	}

	client := rpc.NewClientWithCodec(goridge.NewClientCodec(conn))

	res := make(map[string]bool)

	err = client.Call("kv.Has", Data{
		Storage: "redis",
		Keys:    []string{"1"},
		Timeout: 0,
	}, &res)
	if err != nil {
		panic(err)
	}

}
