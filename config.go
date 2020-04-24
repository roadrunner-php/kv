package kv

import (
	"github.com/spiral/roadrunner/service"
)

// key storage name
// value storage
type Config map[string]interface{}


func (c *Config) Hydrate(cfg service.Config) error {
	if err := cfg.Unmarshal(c); err != nil {
		return err
	}
	//c.parent = cfg
	return nil
}

//TODO why do we need this?
func (c *Config) Unmarshal(out interface{}) error {
	return nil
}

