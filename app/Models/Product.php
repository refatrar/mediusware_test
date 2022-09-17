<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'title', 'sku', 'description'
    ];

    protected $with = ['varientPrices'];

    public static function boot()
    {
        parent::boot();
        static::created(function ($model) {
            $model->created_at = now();
        });
        static::updated(function ($model) {
            $model->updated_at = now();
        });

    }

    public function varientPrices()
    {
        return $this->hasMany(\App\Models\ProductVariantPrice::class, 'product_id');
    }

    public function getCreatedAtAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('d-M-Y');
    }
}
