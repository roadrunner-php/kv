package kv

import (
	"context"

	"github.com/spiral/kv/payload/generated"
)

type RpcServer struct {
	svc *Service
}

type Payload struct {
	Storage string
	Items   []Item
}

// data Data
func (r *RpcServer) Has(in []byte, res *map[string]bool) error {
	ctx := context.Background()
	dataRoot := generated.GetRootAsPayload(in, 0)
	l := dataRoot.ItemsLength()
	keys := make([]string, 0, l)

	tmpItem := &generated.Item{}

	for i := 0; i < l; i++ {
		if !dataRoot.Items(tmpItem, i) {
			continue
		}
		keys = append(keys, string(tmpItem.Key()))
	}

	ret, err := r.svc.Storages[string(dataRoot.Storage())].Has(ctx, keys...)
	if err != nil {
		return err
	}
	// fill the map
	*res = ret

	return nil
}

// in SetData
func (r *RpcServer) Set(in []byte, ok *bool) error {
	ctx := context.Background()
	dataRoot := generated.GetRootAsPayload(in, 0)

	l := dataRoot.ItemsLength()
	items := make([]Item, 0, dataRoot.ItemsLength())
	tmpItem := &generated.Item{}
	for i := 0; i < l; i++ {
		if !dataRoot.Items(tmpItem, i) {
			continue
		}

		itc := Item{
			Key:   string(tmpItem.Key()),
			Value: string(tmpItem.Value()),
			TTL:   string(tmpItem.Timeout()),
		}

		items = append(items, itc)
	}

	err := r.svc.Storages[string(dataRoot.Storage())].Set(ctx, items...)
	if err != nil {
		return err
	}
	return nil

}

// in Data
func (r *RpcServer) MGet(in []byte, res *map[string]interface{}) error {
	ctx := context.Background()
	dataRoot := generated.GetRootAsPayload(in, 0)
	l := dataRoot.ItemsLength()
	keys := make([]string, 0, l)
	tmpItem := &generated.Item{}

	for i := 0; i < l; i++ {
		if !dataRoot.Items(tmpItem, i) {
			continue
		}
		keys = append(keys, string(tmpItem.Key()))
	}

	ret, err := r.svc.Storages[string(dataRoot.Storage())].MGet(ctx, keys...)
	if err != nil {
		return err
	}
	// return the map
	*res = ret

	return nil
}

// in Data
func (r *RpcServer) MExpire(in []byte, ok *bool) error {
	ctx := context.Background()
	dataRoot := generated.GetRootAsPayload(in, 0)
	l := dataRoot.ItemsLength()

	// when unmarshalling the keys, simultaneously, fill up the slice with items
	items := make([]Item, 0, l)
	tmpItem := &generated.Item{}
	for i := 0; i < l; i++ {
		if !dataRoot.Items(tmpItem, i) {
			continue
		}

		itc := Item{
			Key: string(tmpItem.Key()),
			// we set up timeout on the keys, so, value here is redundant
			Value: "",
			TTL:   string(tmpItem.Timeout()),
		}

		items = append(items, itc)
	}

	err := r.svc.Storages[string(dataRoot.Storage())].MExpire(ctx, items...)
	if err != nil {
		return err
	}
	// return the result
	*ok = true

	return nil
}

// in Data
func (r *RpcServer) TTL(in []byte, res *map[string]interface{}) error {
	ctx := context.Background()
	dataRoot := generated.GetRootAsPayload(in, 0)
	l := dataRoot.ItemsLength()
	keys := make([]string, 0, l)
	tmpItem := &generated.Item{}

	for i := 0; i < l; i++ {
		if !dataRoot.Items(tmpItem, i) {
			continue
		}
		keys = append(keys, string(tmpItem.Key()))
	}

	ret, err := r.svc.Storages[string(dataRoot.Storage())].TTL(ctx, keys...)
	if err != nil {
		return err
	}
	// return the map
	*res = ret

	return nil
}

// in Data
func (r *RpcServer) Delete(in []byte, ok *bool) error {
	ctx := context.Background()
	dataRoot := generated.GetRootAsPayload(in, 0)
	l := dataRoot.ItemsLength()
	keys := make([]string, 0, l)
	tmpItem := &generated.Item{}

	for i := 0; i < l; i++ {
		if !dataRoot.Items(tmpItem, i) {
			continue
		}
		keys = append(keys, string(tmpItem.Key()))
	}

	err := r.svc.Storages[string(dataRoot.Storage())].Delete(ctx, keys...)
	if err != nil {
		return err
	}
	// return true
	*ok = true

	return nil
}

// in string, storages
func (r *RpcServer) Close(storage string, ok *bool) error {
	err := r.svc.Storages[storage].Close()
	if err != nil {
		return err
	}
	// return true
	*ok = true

	return nil
}
