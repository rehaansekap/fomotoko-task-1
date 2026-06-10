<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    // Kolom yang boleh diisi secara massal
    protected $fillable = ['total_price'];

    // Relasi satu pesanan ke banyak detail item pesanan
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
