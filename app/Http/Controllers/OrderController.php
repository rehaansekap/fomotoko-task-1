<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // Menangani pembuatan pesanan baru secara aman (anti race condition)
    public function store(Request $request): JsonResponse
    {
        // Validasi input: Minimal harus ada 1 item
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        // Urutkan item berdasarkan product_id untuk menghindari deadlock
        $items = collect($validated['items'])->sortBy('product_id')->values();

        try {
            // Jalankan transaksi database
            $order = DB::transaction(function () use ($items) {
                $orderItemsData = [];
                $totalPrice = 0;

                foreach ($items as $item) {
                    // Kunci data produk untuk update (Pessimistic Locking)
                    $product = Product::where('id', $item['product_id'])
                        ->lockForUpdate()
                        ->first();

                    // Cek jika stok tidak mencukupi
                    if ($product->stock < $item['quantity']) {
                        throw new \Exception("Stok tidak mencukupi untuk produk: {$product->name}");
                    }

                    // Kurangi stok produk
                    $product->decrement('stock', $item['quantity']);

                    $itemPrice = $product->price * $item['quantity'];
                    $totalPrice += $itemPrice;

                    $orderItemsData[] = [
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'price' => $product->price,
                    ];
                }

                // Simpan pesanan
                $order = Order::create(['total_price' => $totalPrice]);

                // Simpan setiap item pesanan
                foreach ($orderItemsData as $itemData) {
                    $order->orderItems()->create($itemData);
                }

                return $order->load('orderItems');
            });

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat.',
                'data' => $order
            ], 201); // HTTP 201 Created
        } catch (\Exception $e) {
            // Kembalikan error jika stok habis atau transaksi gagal
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422); // HTTP 422 Unprocessable Entity
        }
    }
}
