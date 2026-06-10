<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    // Kolom yang boleh diisi secara massal
    protected $fillable = ['name', 'price', 'stock'];

    // Relasi satu produk ke banyak detail item pesanan
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
