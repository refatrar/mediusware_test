<?php

namespace App\Interfaces;

interface ProductInterface
{
    public function listData($request);
    public function listVariant();
    public function processingStore($data);
    public function processingUpdate($data, $id);
    public function handleProductData($data);
}
