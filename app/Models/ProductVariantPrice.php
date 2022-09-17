<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantPrice extends Model
{
    protected $fillable = [
        'product_variant_one', 'product_variant_two', 'product_variant_three', 'price', 'stock', 'product_id'
    ];

    protected $with = ['varientOne', 'varientTwo', 'varientThree'];

    public function varientOne()
    {
        return $this->belongsTo(\App\Models\ProductVariant::class, 'product_variant_one');
    }

    public function varientTwo()
    {
        return $this->belongsTo(\App\Models\ProductVariant::class, 'product_variant_two');
    }

    public function varientThree()
    {
        return $this->belongsTo(\App\Models\ProductVariant::class, 'product_variant_three');
    }
}
