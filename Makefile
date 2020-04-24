test:
	go test -v -race -cover ./driver/boltdb
	go test -v -race -cover ./driver/memcached
	go test -v -race -cover ./driver/redis
	go test -v -race -cover ./driver/memory

build_test_server:
	go build cmd/main.go -o tests/test_server

server:
	docker-compose up -d
	./tests/test_server serve -d -v -c .rr.yaml