<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Enums\OrderStatus;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run($userId = null): void
    {
        Order::unsetEventDispatcher();

        Order::factory()->count(10)->create([
            'user_id' => $userId
        ]);
    }
}
