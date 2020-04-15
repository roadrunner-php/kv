test:
	go test -v -race -cover ./driver/boltdb
	go test -v -race -cover ./driver/memcached
	go test -v -race -cover ./driver/redis
	go test -v -race -cover ./driver/memory