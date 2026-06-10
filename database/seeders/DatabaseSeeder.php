<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Buat user tes bawaan
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Buat produk tes bawaan dengan ID 1 untuk pengetesan manual
        \App\Models\Product::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Sepatu Flash Sale',
                'price' => 100000.00,
                'stock' => 10,
            ]
        );
    }
}
