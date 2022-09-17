<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'title', 'sku', 'description'
    ];

    protected $with = ['varientPrices'];

    public function varientPrices()
    {
        return $this->hasMany(\App\Models\ProductVariantPrice::class, 'product_id');
    }

    public function getCreatedAtAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('d-M-Y');
    }
}
