package main

import (
	"github.com/spiral/kv"
	"github.com/spiral/kv/driver/memory"
	rr "github.com/spiral/roadrunner/cmd/rr/cmd"
	"github.com/spiral/roadrunner/service/rpc"
)

func main() {
	rr.Container.Register(rpc.ID, &rpc.Service{})
	rr.Container.Register(kv.ID, &kv.Service{
		Drivers: map[string]kv.Driver{
			//"redis":     &redis.Storage{},
			"memory": &memory.Driver{},
			//"memcached": &memcached.Storage{},
			//"boltdb":    &boltdb.Storage{},
		},
	})
	rr.Execute()
}
