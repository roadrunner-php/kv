package main

import (
	"github.com/spiral/kv"
	"github.com/spiral/kv/driver/redis"
	rr "github.com/spiral/roadrunner/cmd/rr/cmd"
	"github.com/spiral/roadrunner/service/rpc"
)

func main() {
	rr.Container.Register(rpc.ID, &rpc.Service{})
	rr.Container.Register(kv.ID, &kv.Service{
		Storages: map[string]kv.Storage{
			"redis":     &redis.Storage{},
			//"memory":    &memory.Storage{},
			//"memcached": &memcached.Storage{},
			//"boltdb":    &boltdb.Storage{},
		},
	})
	rr.Execute()
}
