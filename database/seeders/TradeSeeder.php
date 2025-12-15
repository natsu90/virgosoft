<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\Trade;
use App\Enums\TradeSymbol;
use App\Enums\OrderSide;
use App\Enums\OrderStatus;

class TradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run($userId = null): void
    {
        Order::unsetEventDispatcher();
        Trade::unsetEventDispatcher();

        for ($i = 0; $i < 10; $i++)
        {
            $buy = Order::factory()->create([
                'user_id' => $userId,
                'side' => OrderSide::BUY,
                'status' => OrderStatus::FILLED
            ]);

            $sell = Order::factory()->create([
                'user_id' => $userId,
                'symbol' => $buy->symbol,
                'side' => OrderSide::SELL,
                'amount' => $buy->amount,
                'status' => OrderStatus::FILLED
            ]);

            $trade = Trade::create([
                'buy_order_id' => $buy->getKey(),
                'sell_order_id' => $sell->getKey(),
                'symbol' => $buy->symbol,
                'price' => $buy->price,
                'amount' => $buy->amount,
                'commission' => 0.015 * $buy->amount * $buy->price
            ]);
        }
    }
}
