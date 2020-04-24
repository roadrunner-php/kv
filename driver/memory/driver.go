package memory

import (
	"github.com/spiral/kv"
)

type Driver struct {
	//// Enabled or disabled default storage
	//Enabled bool
	//
	//// logger
	//log *logrus.Logger
}

func (s Driver) Init(config kv.Config) (kv.Storage, error) {
	return NewInMemoryStorage(), nil
}

//func (s *Config) InitDefaults() error {
//	s.Enabled = false // disabled by default
//	s.log = logrus.StandardLogger()
//	return nil
//}
//
//func (s *Config) Hydrate(cfg service.Config) error {
//	err := cfg.Unmarshal(s)
//	if err != nil {
//		panic(err)
//	}
//	return nil
//}
