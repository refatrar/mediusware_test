<?php

namespace App\Repositories;

use App\Interfaces\ProductInterface;
use App\Models\Product;

class ProductRepository implements ProductInterface
{
    protected $product;

    public function __construct(Product $product)
    {
        $this->product   = $product;
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
                        $query->where('product_variant_one', $request->variant)
                            ->orWhere('product_variant_two', $request->variant)
                            ->orWhere('product_variant_three', $request->variant);
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

    private function arrangeVarientPrices($data)
    {
        return $data->transform(function($item, $key){
            return collect([
                'varient' => strtoupper(data_get($item, 'varientOne.variant') . (!is_null(data_get($item, 'varientTwo.variant')) ? ' / ' . data_get($item, 'varientTwo.variant') : '') . (!is_null(data_get($item, 'varientThree.variant')) ? ' / ' . data_get($item, 'varientThree.variant') : '')),
                'price' => $item->price,
                'stock' => $item->stock,
            ]);
        });
    }
}
