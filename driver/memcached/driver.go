package memcached

import (
	"github.com/sirupsen/logrus"
	"github.com/spiral/roadrunner/service"
)

type Config struct {
	// Addr is url for memcached, 11211 port is used by default
	Addr []string
	// logrus logger
	log *logrus.Logger
}

func (s *Config) InitDefaults() error {
	s.Addr = []string{"localhost:11211"} // default url for memcached
	s.log = logrus.StandardLogger()      // init logger
	return nil
}

// Hydrate is config unmarshaller
func (s *Config) Hydrate(cfg service.Config) error {
	err := cfg.Unmarshal(s)
	if err != nil {
		panic(err)
	}
	return nil
}
