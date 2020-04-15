package kv

import (
	"github.com/sirupsen/logrus"
	"github.com/spiral/roadrunner"
	"github.com/spiral/roadrunner/service"
	"github.com/spiral/roadrunner/service/env"
	"github.com/spiral/roadrunner/service/rpc"
	"sync/atomic"
)

// ID defines public service name.
const ID = "kv"

type Service struct {
	Storages map[string]Storage
	cfg      *StorageConfig

	// server and server controller
	rr        *roadrunner.Server
	cr        roadrunner.Controller
	serving   int32
	container service.Container

	// environment, logger and listeners
	env env.Environment
	log *logrus.Logger
}

func (svc *Service) Init(cfg service.Config, log *logrus.Logger, env env.Environment, rpc *rpc.Service) (bool, error) {
	svc.cfg = &StorageConfig{}
	if err := svc.cfg.Hydrate(cfg); err != nil {
		return false, err
	}

	svc.env = env
	svc.log = log

	if rpc != nil {
		if err := rpc.Register(ID, &RpcServer{svc}); err != nil {
			return false, err
		}
	}

	svc.rr = roadrunner.NewServer(svc.cfg.Workers)

	svc.container = service.NewContainer(log)
	for name, s := range svc.Storages {
		svc.container.Register(name, s)
	}
	err := svc.container.Init(svc.cfg)
	if err != nil {
		panic(err)
	}

	return true, nil
}

func (svc *Service) Serve() error {
	//if svc.rr != nil {
	//	if svc.env != nil {
	//		if err := svc.env.Copy(svc.cfg.Workers); err != nil {
	//			return err
	//		}
	//	}
	//
	//	// ensure that workers aware of running within jobs
	//	svc.cfg.Workers.SetEnv("kv", "true")
	//
	//	if svc.cr != nil {
	//		svc.rr.Attach(svc.cr)
	//	}
	//
	//	if err := svc.rr.Start(); err != nil {
	//		return err
	//	}
	//	defer svc.rr.Stop()
	//
	//}
	//
	atomic.StoreInt32(&svc.serving, 1)
	defer atomic.StoreInt32(&svc.serving, 0)

	return svc.container.Serve()
}

func (svc Service) Stop() {
	println("stop")
}
