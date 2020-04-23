package kv

import (
	"github.com/spiral/roadrunner/service"
)

// StorageConfig declares driver specific configuration.
type StorageConfig struct {
	// Default config (in-memory is used as kv)
	Default map[string]interface{}
	// Redis configuration
	Redis     map[string]interface{}
	// Memcached configuration
	Memcached map[string]interface{}
	// BoltDB configuration
	BoltDB    map[string]interface{}

	parent  service.Config
}

func (c *StorageConfig) Hydrate(cfg service.Config) error {
	if err := cfg.Unmarshal(c); err != nil {
		return err
	}
	c.parent = cfg
	return nil
}

func (c StorageConfig) Get(service string) service.Config {
	if c.parent == nil {
		return nil
	}
	return c.parent.Get(service)
}

//TODO why do we need this?
func (c StorageConfig) Unmarshal(out interface{}) error {
	return nil
}

