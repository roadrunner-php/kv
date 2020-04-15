package boltdb

import (
	"github.com/sirupsen/logrus"
	"github.com/spiral/roadrunner/service"
)

type Config struct {
	// Dir is a directory to store the DB files
	Dir string
	// File is boltDB file. No need to create it by your own,
	// boltdb driver is able to create the file, or read existing
	File string
	// Bucket to store data in boltDB
	Bucket string

	// logger
	log *logrus.Logger

	Permissions int
	Ttl         int
}

func (s *Config) InitDefaults() error {
	s.Dir = "."          // current dir
	s.Bucket = "rr"      // default bucket name
	s.File = "rr.db"     // default file name
	s.Permissions = 0777 // free for all
	s.Ttl = 60           // 60 seconds is default TTL
	s.log = logrus.StandardLogger() // init logger
	return nil
}

func (s *Config) Hydrate(cfg service.Config) error {
	err := cfg.Unmarshal(s)
	if err != nil {
		panic(err)
	}
	return nil
}
