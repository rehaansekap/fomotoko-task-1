<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    // Kolom yang boleh diisi secara massal
    protected $fillable = ['order_id', 'product_id', 'quantity', 'price'];

    // Relasi detail item ke pesanan induk
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Relasi detail item ke data produk
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
