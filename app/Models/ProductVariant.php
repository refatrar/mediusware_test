<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'variant', 'variant_id', 'product_id'
    ];

    protected $with = ['variants'];

    public function variants()
    {
        return $this->belongsTo(\App\Models\Variant::class, 'variant_id');
    }

    public function images()
    {
        return $this->hasMany(\App\Models\ProductImage::class, 'product_id', 'product_id');
    }
}
