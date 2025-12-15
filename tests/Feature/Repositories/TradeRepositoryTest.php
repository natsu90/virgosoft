<?php

namespace Tests\Feature\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Contracts\TradeRepositoryInterface;
use App\Models\Order;
use App\Models\User;
use App\Models\Trade;
use App\Enums\OrderSide;
use App\Enums\TradeSymbol;
use App\Events\TradeCreated;
use App\Events\OrderCreated;
use App\Events\UserCreated;
use Illuminate\Support\Facades\Event;

class TradeRepositoryTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function setUp(): void
    {
        parent::setUp();

        $this->repo = $this->app->make(TradeRepositoryInterface::class);

        Event::fake([
            TradeCreated::class,
            OrderCreated::class,
            UserCreated::class
        ]);
    }

    public function testCreate()
    {
        $symbol = TradeSymbol::ETH->value;
        $amount = 1.23456789;
        $price = 98765.4321;
        $commission = 12345.6789;

        $user = User::factory()->create();
        $sellOrder = Order::factory()->create([
            'side' => OrderSide::SELL
        ]);
        $buyOrder = Order::factory()->create([
            'side' => OrderSide::BUY
        ]);

        $trade = $this->repo->create([
            'buy_order_id' => $buyOrder->getKey(),
            'sell_order_id' => $sellOrder->getKey(),
            'symbol' => $symbol,
            'price' => $price,
            'amount' =>$amount,
            'commission' => $commission
        ]);

        $this->assertInstanceOf(Trade::class, $trade);
        $this->assertDatabaseHas(Trade::getTableName(), [
            'buy_order_id' => $buyOrder->getKey(),
            'sell_order_id' => $sellOrder->getKey(),
            'symbol' => $symbol,
            'price' => $price,
            'amount' =>$amount,
            'commission' => $commission
        ]);

        Event::assertDispatched(TradeCreated::class, function($event) use ($trade) {
            return $event->trade->getKey() === $trade->getKey();
        });
    }
}