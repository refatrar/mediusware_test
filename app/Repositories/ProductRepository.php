<?php

namespace App\Repositories;

use App\Interfaces\ProductInterface;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;

class ProductRepository implements ProductInterface
{
    protected $product;
    protected $variant;
    protected $productVariant;
    protected $productVariantPrice;
    protected $productImage;

    public function __construct(Product $product, Variant $variant, ProductVariant $productVariant, ProductVariantPrice $productVariantPrice, ProductImage $productImage)
    {
        $this->product              = $product;
        $this->variant              = $variant;
        $this->productVariant       = $productVariant;
        $this->productVariantPrice  = $productVariantPrice;
        $this->productImage         = $productImage;
    }

    public function listData($request)
    {
        $results = $this->product
            ->when($request->title, function($query) use($request) {
                $query->where('title', 'LIKE', '%' . $request->title . '%');
            })
            ->when($request->variant, function($query) use($request) {
                $query->whereHas('varientPrices', function($query) use($request) {
                    $query->where(function($query)  use($request) {
                        $query->whereHas('varientOne', function($query) use($request) {
                            $query->where('variant', $request->variant);
                        })
                        ->orWhereHas('varientTwo', function($query) use($request) {
                            $query->where('variant', $request->variant);
                        })
                        ->orWhereHas('varientThree', function($query) use($request) {
                            $query->where('variant', $request->variant);
                        });
                    });
                });
            })
            ->when($request->price_from && $request->price_to, function($query) use($request) {
                $query->whereHas('varientPrices', function($query) use($request) {
                    $query->whereBetween('price', [$request->price_from, $request->price_to]);
                });
            })
            ->when($request->date, function($query) use($request) {
                $query->whereDate('created_at', $request->date);
            })
            ->paginate(5)
            ->appends($request->all());

        $results->getCollection()->transform(function($item, $key){
            $item->varient_prices = $this->arrangeVarientPrices($item->varientPrices);
            return $item;
        });

        return $results;
    }

    public function listVariant()
    {
        return $this->variant
            ->join('product_variants as pv', 'pv.variant_id', 'variants.id')
            ->select('variants.id', 'variants.title')
            ->selectRaw('(GROUP_CONCAT(
                DISTINCT CONCAT(pv.variant)
                ORDER BY pv.id ASC
            )) AS types')
            ->groupBy('variants.id')
            ->get()
            ->transform(function($item){
                $item->types = explode(',', $item->types);
                return $item;
            });
    }

    private function arrangeVarientPrices($data)
    {
        return $data->transform(function($item, $key){
            return [
                'varient' => strtoupper(data_get($item, 'varientOne.variant') . (!is_null(data_get($item, 'varientTwo.variant')) ? ' / ' . data_get($item, 'varientTwo.variant') : '') . (!is_null(data_get($item, 'varientThree.variant')) ? ' / ' . data_get($item, 'varientThree.variant') : '')),
                'price' => $item->price,
                'stock' => $item->stock,
            ];
        });
    }

    public function processingStore($data)
    {
        try {
            DB::beginTransaction();
                $productData = $data->only('title', 'sku', 'description');
                $productData = $this->product->create($productData);

                $this->processingImages($data->product_image, $productData->id);

                $variantData = $this->processingVariant($data->product_variant, $productData->id);

                $variantPriceData = $this->processingVariantPrices($data->product_variant_prices, $variantData, $productData->id);
            DB::commit();

            return $productData;
        }
        catch (\Exception $error) {
            DB::rollBack();
            throw $error;
        }
    }

    public function processingUpdate($data, $product)
    {
        try {

            DB::beginTransaction();
                $productData = $data->only('title', 'sku', 'description');
                $product->update($productData);

                $this->processingImages($data->product_image, $product->id);

                $variantData = $this->processingVariant($data->product_variant, $product->id);

                $this->processingVariantPrices($data->product_variant_prices, $variantData, $product->id);
            DB::commit();

            return $productData;
        }
        catch (\Exception $error) {
            DB::rollBack();
            throw $error;
        }
    }

    private function processingImages($data, $product_id){
        try {
            DB::beginTransaction();
                $this->productImage->delete(['product_id' => $product_id]);

                $array = array_map(function($item) use($product_id) {
                    return [
                        'product_id' => $product_id,
                        'file_path' => $item,
                    ];
                }, $data);

                if(count($array)):
                    $this->productImage->insert($array);
                endif;
            DB::commit();
        }
        catch (\Exception $error) {
            DB::rollBack();
            throw $error;
        }
    }

    private function processingVariant($data, $product_id)
    {
        try {
            $array = [];

            $this->productVariant->delete(['product_id' => $product_id]);

            DB::beginTransaction();
                foreach($data as $item):
                    foreach($item['tags'] as $tag):
                $result = $this->productVariant->create([
                            'variant' => $tag,
                            'variant_id' => $item['option'],
                            'product_id' => $product_id,
                        ]);

                        array_push($array, $result->toArray());
                    endforeach;
                endforeach;
            DB::commit();

            return $array;
        }
        catch (\Exception $error) {
            DB::rollBack();
            throw $error;
        }
    }

    private function processingVariantPrices($data, $variant, $product_id)
    {
        try {
            $this->productVariantPrice->delete(['product_id' => $product_id]);

            $array = [];

            DB::beginTransaction();
                foreach($data as $item):
                    $a = [
                        'product_id' => $product_id,
                        'product_variant_one' => null,
                        'product_variant_two' => null,
                        'product_variant_three' => null,
                        'price' => $item['price'],
                        'stock' => $item['stock'],
                    ];

                    foreach(explode('/', $item['title']) as $r => $row):
                        if(in_array($row, array_column($variant, 'variant'))):
                            if($r == 0):
                                $a['product_variant_one'] = $variant[array_search($row, array_column($variant, 'variant'))]['id'];
                            elseif($r == 1):
                                $a['product_variant_two'] = $variant[array_search($row, array_column($variant, 'variant'))]['id'];
                            else:
                                $a['product_variant_three'] = $variant[array_search($row, array_column($variant, 'variant'))]['id'];
                            endif;
                        endif;
                    endforeach;

                    array_push($array, $a);
                endforeach;

                $this->productVariantPrice->insert($array);
            DB::commit();

            return true;
        }
        catch (\Exception $error) {
            DB::rollBack();
            throw $error;
        }
    }

    public function handleProductData($data)
    {
        $varientPrices = $data['varient_prices'];
        unset($data['varient_prices']);
        $data['product_image'] = [];
        $data['product_variant'] = [];
        $data['product_variant_prices'] = [];

        foreach($varientPrices as $varientPrice):
            if($varientPrice['varient_one']):
                if(!array_key_exists(data_get($varientPrice, 'varient_one.variant_id'), $data['product_variant'])):
                    $data['product_variant'][data_get($varientPrice, 'varient_one.variant_id')] = [
                        'option' => data_get($varientPrice, 'varient_one.variant_id'),
                        'tags' => [data_get($varientPrice, 'varient_one.variant')]
                    ];
                else:
                    array_push($data['product_variant'][data_get($varientPrice, 'varient_one.variant_id')]['tags'], data_get($varientPrice, 'varient_one.variant'));
                endif;
            endif;

            if($varientPrice['varient_two']):
                if(!array_key_exists(data_get($varientPrice, 'varient_two.variant_id'), $data['product_variant'])):
                    $data['product_variant'][data_get($varientPrice, 'varient_two.variant_id')] = [
                        'option' => data_get($varientPrice, 'varient_two.variant_id'),
                        'tags' => [data_get($varientPrice, 'varient_two.variant')]
                    ];
                else:
                    array_push($data['product_variant'][data_get($varientPrice, 'varient_two.variant_id')]['tags'], data_get($varientPrice, 'varient_two.variant'));
                endif;
            endif;

            if($varientPrice['varient_three']):
                if(!array_key_exists(data_get($varientPrice, 'varient_three.variant_id'), $data['product_variant'])):
                    $data['product_variant'][data_get($varientPrice, 'varient_three.variant_id')] = [
                        'option' => data_get($varientPrice, 'varient_three.variant_id'),
                        'tags' => [data_get($varientPrice, 'varient_three.variant')]
                    ];
                else:
                    array_push($data['product_variant'][data_get($varientPrice, 'varient_three.variant_id')]['tags'], data_get($varientPrice, 'varient_three.variant'));
                endif;
            endif;

            array_push($data['product_variant_prices'], [
                'id' => $varientPrice['id'],
                'title' => (data_get($varientPrice, 'varient_one.variant') ? $varientPrice['varient_one']['variant'] . '/' : '') . (data_get($varientPrice, 'varient_two.variant') ? $varientPrice['varient_two']['variant'] . '/' : '') . (data_get($varientPrice, 'varient_three.variant') ? $varientPrice['varient_three']['variant'] : ''),
                'price' => $varientPrice['price'],
                'stock' => $varientPrice['stock']
            ]);
        endforeach;

        $data['product_variant'] = array_map(function($item){
            $item['tags'] = array_values(array_unique($item['tags']));
            return $item;
        }, array_values($data['product_variant']));

        return collect($data);
    }
}
