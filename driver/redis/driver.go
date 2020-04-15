package redis

import (
	"github.com/sirupsen/logrus"
	"github.com/spiral/roadrunner/service"
)

type Config struct {
	// Addr is address to use. If len > 1, cluster client will be used
	Addr []string
	// database number to use, 0 is used by default
	Db int
	// Master name for failover client, empty by default
	Master string
	// Redis password, empty by default
	Password string

	// logger
	log *logrus.Logger
}

// InitDefaults initializing fill config with default values
func (s *Config) InitDefaults() error {
	s.Addr = []string{"localhost:6379"} // default addr is pointing to local storage
	s.log = logrus.StandardLogger()     // init logger
	return nil
}

func (s *Config) Hydrate(cfg service.Config) error {
	err := cfg.Unmarshal(s)
	if err != nil {
		panic(err)
	}
	return nil
}
