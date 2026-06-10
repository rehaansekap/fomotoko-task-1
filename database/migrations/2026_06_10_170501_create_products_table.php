<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Menjalankan migrasi tabel products
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 15, 2);
            $table->integer('stock')->default(0); // Stok produk
            $table->timestamps();
        });
    }

    // Membatalkan migrasi tabel products
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
