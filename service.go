package kv

import (
	"sync/atomic"

	"github.com/sirupsen/logrus"
	"github.com/spiral/roadrunner/service"
	"github.com/spiral/roadrunner/service/rpc"
)

// ID defines public service name.
const ID = "kv"

// Driver provides the ability to init one or multiple storage partitions.
type Driver interface {
	// Init initialize storage based on provided arguments.
	Init(Config) (Storage, error)
}

type Service struct {
	Drivers  map[string]Driver
	// key - storage
	// value - implementation
	Storages map[string]Storage
	cfg      *Config

	// server
	serving   int32
	container service.Container

	log *logrus.Logger
}

func (svc *Service) Init(cfg service.Config, rpc *rpc.Service) (bool, error) {
	svc.cfg = &Config{}
	if err := svc.cfg.Hydrate(cfg); err != nil {
		return false, err
	}

	if rpc != nil {
		if err := rpc.Register(ID, &RpcServer{svc}); err != nil {
			return false, err
		}
	}

	return true, nil
}

func (svc *Service) Serve() error {
	for k, v := range *svc.cfg {
		switch v.(map[string]interface{})["driver"] {
		case "default":
			str, err := svc.Drivers[k].Init(v.(map[string]interface{}))
			if err != nil {
				return err
			}

			svc.Storages[k] = str

			println("default")
		}





	}

	atomic.StoreInt32(&svc.serving, 1)
	defer atomic.StoreInt32(&svc.serving, 0)

	return nil
}

func (svc *Service) Stop() {
	if atomic.LoadInt32(&svc.serving) == 0 {
		return
	}

	//for storages invoke stop
}
