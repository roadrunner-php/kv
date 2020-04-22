package kv

import (
	"context"

	"github.com/spiral/kv/buffer/data"
	"golang.org/x/sync/errgroup"
)

type RpcServer struct {
	svc *Service
}

type Data struct {
	Storage string   `json:"storage"`
	Keys    []string `json:"keys"`
	Timeout string   `json:"timeout"`
}

// data Data
func (r *RpcServer) Has(in []byte, res *map[string]bool) error {
	ctx := context.Background()
	dataRoot := data.GetRootAsData(in, 0)
	l := dataRoot.KeysLength()
	keys := make([]string, 0, l)

	for i := 0; i < l; i++ {
		// TODO make unsafe fast convert
		keys = append(keys, string(dataRoot.Keys(i)))
	}

	storage := string(dataRoot.Storage())

	ret, err := r.svc.Storages[storage].Has(ctx, keys...)
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

// in SetData
func (r *RpcServer) Set(in []byte, ok *bool) error {
	ctx := context.Background()
	dataRoot := data.GetRootAsSetData(in, 0)

	items := make([]Item, 0, dataRoot.ItemsLength())
	it := &data.Item{}
	for i := 0; i < dataRoot.ItemsLength(); i++ {
		if !dataRoot.Items(it, i) {
			continue
		}

		itc := Item{
			Key:   string(it.Key()),
			Value: string(it.Value()),
			TTL:   string(it.Timeout()),
		}

		items[i] = itc
	}

	errg := &errgroup.Group{}
	for i := 0; i < dataRoot.StoragesLength(); i++ {
		errg.Go(func() error {
			err := r.svc.Storages[string(dataRoot.Storages(i))].Set(ctx, items...)
			if err != nil {
				return err
			}
			return nil
		})
	}

	return errg.Wait()
}

func (r *RpcServer) Get(in Data, res *[]byte) error {
	println("has")
	return nil
}

func (r *RpcServer) MGet(in Data, res *map[string]interface{}) error {
	println("has")

	return nil
}

func (r *RpcServer) MExpire(in Data, ok *bool) error {
	println("has")

	return nil
}

func (r *RpcServer) TTL(in Data, ok *bool) error {
	println("has")

	return nil
}

func (r *RpcServer) Delete(in Data, ok *bool) error {
	println("has")

	return nil
}

func (r *RpcServer) Close(in string, ok *bool) error {
	println("has")

	return nil
}
