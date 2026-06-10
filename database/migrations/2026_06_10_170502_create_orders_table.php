<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Menjalankan migrasi tabel orders
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->decimal('total_price', 15, 2); // Total harga pesanan
            $table->timestamps();
        });
    }

    // Membatalkan migrasi tabel orders
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
