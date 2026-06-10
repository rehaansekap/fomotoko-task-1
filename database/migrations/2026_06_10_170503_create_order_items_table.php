<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Menjalankan migrasi tabel order_items
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade'); // Relasi ke tabel orders
            $table->foreignId('product_id')->constrained()->onDelete('cascade'); // Relasi ke tabel products
            $table->integer('quantity'); // Jumlah dipesan
            $table->decimal('price', 15, 2); // Harga produk per unit
            $table->timestamps();
        });
    }

    // Membatalkan migrasi tabel order_items
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
