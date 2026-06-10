<?php

namespace Tests\Feature;

use App\Http\Controllers\OrderController;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FlashSaleRaceConditionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 1. Muat konfigurasi .env asli agar tidak tertimpa SQLite bawaan pengujian
        $envPath = realpath(__DIR__ . '/../../.env');
        $env = [];
        if ($envPath && file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) continue;
                if (str_contains($line, '=')) {
                    [$key, $val] = explode('=', $line, 2);
                    $env[trim($key)] = trim($val, " \t\n\r\0\x0B\"'");
                }
            }
        }

        // 2. Hubungkan test runner ke database PostgreSQL
        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.host' => $env['DB_HOST'] ?? '127.0.0.1',
            'database.connections.pgsql.port' => $env['DB_PORT'] ?? '5432',
            'database.connections.pgsql.database' => $env['DB_DATABASE'] ?? 'fomotoko_task_1',
            'database.connections.pgsql.username' => $env['DB_USERNAME'] ?? 'postgres',
            'database.connections.pgsql.password' => $env['DB_PASSWORD'] ?? '',
        ]);

        DB::purge();

        // 3. Bersihkan data lama di database
        DB::table('order_items')->delete();
        DB::table('orders')->delete();
        DB::table('products')->delete();
    }

    public function test_race_condition_during_flash_sale(): void
    {
        // 1. Buat produk dengan stok awal 10 unit
        $product = Product::create([
            'name' => 'Sepatu Flash Sale',
            'price' => 100000.00,
            'stock' => 10,
        ]);

        // 2. Fork 15 proses anak untuk melakukan pembelian secara bersamaan
        $pids = [];
        for ($i = 0; $i < 15; $i++) {
            $pid = pcntl_fork();

            if ($pid === -1) {
                $this->fail("Gagal membuat proses cabang (fork)");
            } elseif ($pid === 0) {
                // DI DALAM PROSES CABANG (CHILD PROCESS)
                // Putuskan koneksi lama agar tidak bertabrakan antar proses
                DB::purge();

                try {
                    // Buat request HTTP tiruan ke API orders
                    $request = Request::create('/api/orders', 'POST', [
                        'items' => [
                            [
                                'product_id' => $product->id,
                                'quantity' => 1,
                            ]
                        ]
                    ]);

                    // Panggil controller langsung untuk memproses pesanan
                    $response = app(OrderController::class)->store($request);
                    $statusCode = $response->getStatusCode();

                    // Kirim sinyal ke diri sendiri untuk keluar paksa tanpa memicu hook PHPUnit
                    // Sinyal 10 (SIGUSR1) jika sukses (201), atau sinyal 12 (SIGUSR2) jika gagal (422)
                    \posix_kill(\getmypid(), $statusCode === 201 ? 10 : 12);
                    exit(0);
                } catch (\Throwable $e) {
                    \posix_kill(\getmypid(), 9); // Sinyal 9 (SIGKILL) jika terjadi error sistem
                    exit(2);
                }
            } else {
                // Di dalam proses utama (parent process), catat ID proses cabang
                $pids[] = $pid;
            }
        }

        // 3. Tunggu semua proses cabang selesai dan hitung hasilnya
        $successCount = 0;
        $failedCount = 0;

        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);

            // Baca sinyal keluar dari proses anak
            if (pcntl_wifsignaled($status)) {
                $signal = pcntl_wtermsig($status);
                if ($signal === 10) {
                    $successCount++;
                } elseif ($signal === 12) {
                    $failedCount++;
                }
            }
        }

        // 4. Verifikasi hasil akhir
        $this->assertEquals(10, $successCount, "Harus ada tepat 10 pembelian sukses");
        $this->assertEquals(5, $failedCount, "Harus ada tepat 5 pembelian gagal karena kehabisan stok");

        // Pastikan stok akhir di database bernilai 0 dan jumlah order_items tepat 10
        $product->refresh();
        $this->assertEquals(0, $product->stock, "Stok produk akhir harus tepat 0");
        $this->assertEquals(10, OrderItem::count(), "Jumlah item pesanan di database harus tepat 10");
    }
}
