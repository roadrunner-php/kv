package kv

import (
	"context"
)

type RpcServer struct {
	svc *Service
}

type Data struct {
	Storage string   `json:"storage"`
	Keys    []string `json:"keys"`
	Timeout int      `json:"timeout"`
}

// TODO skip false values, discuss with JD, Val
func (r *RpcServer) Has(data Data, res *map[string]bool) error {
	ctx := context.Background()
	ret, err := r.svc.Storages[data.Storage].Has(ctx, data.Keys...)
	if err != nil {
		return err
	}
	// fill the map
	*res = ret

	return nil
}

type SetData struct {
	Items    []Item   `json:"items"`
	Storages []string `json:"storages"`
}

func (r *RpcServer) Set(data SetData, res *[]byte) error {
	println("has")
	return nil
}

func (r *RpcServer) Get(data Data, res *[]byte) error {
	println("has")
	return nil
}

func (r *RpcServer) MGet(data Data, res *map[string]bool) error {
	println("has")

	return nil
}

func (r *RpcServer) MExpire(data Data, ok *bool) error {
	println("has")

	return nil
}

func (r *RpcServer) TTL(data Data, ok *bool) error {
	println("has")

	return nil
}

func (r *RpcServer) Delete(data Data, ok *bool) error {
	println("has")

	return nil
}

func (r *RpcServer) Close(storage string, ok *bool) error {
	println("has")

	return nil
}
