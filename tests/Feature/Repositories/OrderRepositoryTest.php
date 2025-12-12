<?php

namespace Tests\Feature\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Contracts\OrderRepositoryInterface;
use App\Models\Order;
use App\Models\User;
use App\Enums\TradeSymbol;
use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Events\OrderCreated;
use App\Events\OrderUpdated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use App\Listeners\FindOrderMatch;
use Illuminate\Events\CallQueuedListener;

class OrderRepositoryTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function setUp(): void
    {
        parent::setUp();

        $this->repo = $this->app->make(OrderRepositoryInterface::class);

    }

    public function testCreate()
    {
        Event::fake(OrderCreated::class);

        $user = User::factory()->create();
        $price = fake()->randomFloat(4, 10, 100);
        $amount = fake()->randomFloat(8, 0.0001, 10);
        $order = $this->repo->create([
            'user_id' => $user->getKey(),
            'symbol' => fake()->randomElement(TradeSymbol::cases()),
            'amount' => $amount,
            'side' => OrderSide::BUY,
            'price' => $price
        ]);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertDatabaseHas(Order::getTableName(), [
            'user_id' => $user->getKey(),
            'amount' => $amount,
            'price' => $price,
            'status' => OrderStatus::OPEN->value,
            'side' => OrderSide::BUY->value
        ]);

        Event::assertDispatched(OrderCreated::class, function($event) use ($order) {
            return $event->order->getKey() === $order->getKey();
        });
    }

    public function testEventListener()
    {
        Queue::fake();

        $order = Order::factory()->create();

        Queue::assertPushed(CallQueuedListener::class, function($job) {
            return $job->class == FindOrderMatch::class;
        });
    }

    public function testFind()
    {
        Event::fake(OrderCreated::class);

        $order = Order::factory()->create();

        $fetchedOrder = $this->repo->find($order->getKey());

        $this->assertInstanceOf(Order::class, $fetchedOrder);
        $this->assertEquals($fetchedOrder->getKey(), $order->getKey());
    }

    public function testUpdate()
    {
        Event::fake([
            OrderCreated::class,
            OrderUpdated::class
        ]);

        $order = Order::factory()->create();
        $amount = fake()->randomFloat(8, 0.0001, 10);
        $status = fake()->randomElement(OrderStatus::cases());

        $updatedOrder = $this->repo->update($order->getKey(), [
            'status' => $status,
            'amount' => $amount
        ]);

        $this->assertInstanceOf(Order::class, $updatedOrder);
        $this->assertEquals($updatedOrder->getKey(), $order->getKey());
        $this->assertDatabaseHas(Order::getTableName(), [
            'id' => $order->getKey(),
            'amount' => $amount,
            'status' => $status
        ]);

        Event::assertDispatched(OrderUpdated::class, function($event) use ($order) {
            return $event->order->getKey() === $order->getKey();
        });
    }

    public function testFindSellOrder()
    {
        Event::fake(OrderCreated::class);
        
        $buySymbol = TradeSymbol::BTC->value;
        $buyOrder = Order::factory()->create([
            'symbol' => $buySymbol,
            'side' => OrderSide::BUY,
            'status' => OrderStatus::OPEN,
            'price' => 1.5
        ]);

        $expectedSellOrder = Order::factory()->create([
            'symbol' => $buySymbol,
            'side' => OrderSide::SELL,
            'status' => OrderStatus::OPEN,
            'price' => 1
        ]);

        // Sell Order with diff symbol
        Order::factory()->create([
            'symbol' => TradeSymbol::ETH,
            'side' => OrderSide::SELL,
            'status' => OrderStatus::OPEN,
            'price' => 1
        ]);

        // Sell Order with diff status
        Order::factory()->create([
            'symbol' => $buySymbol,
            'side' => OrderSide::SELL,
            'status' => OrderStatus::FILLED,
            'price' => 1
        ]);

        // another Buy Order
        Order::factory()->create([
            'symbol' => $buySymbol,
            'side' => OrderSide::BUY,
            'status' => OrderStatus::OPEN,
            'price' => 1
        ]);

        // Sell Order with higher price
        Order::factory()->create([
            'symbol' => $buySymbol,
            'side' => OrderSide::SELL,
            'status' => OrderStatus::OPEN,
            'price' => 2
        ]);

        // Sell Order with later time
        Order::factory()->create([
            'symbol' => $buySymbol,
            'side' => OrderSide::SELL,
            'status' => OrderStatus::OPEN,
            'price' => 1
        ]);

        $sellOrder = $this->repo->findSellOrder($buySymbol, $buyOrder->price);

        $this->assertInstanceOf(Order::class, $sellOrder);
        $this->assertEquals($expectedSellOrder->getKey(), $sellOrder->getKey());
    }

    public function testFindBuyOrder()
    {
        Event::fake(OrderCreated::class);

        $sellSymbol = TradeSymbol::ETH->value;
        $sellOrder = Order::factory()->create([
            'symbol' => $sellSymbol,
            'side' => OrderSide::SELL,
            'status' => OrderStatus::OPEN,
            'price' => 1
        ]);

        $expectedBuyOrder = Order::factory()->create([
            'symbol' => $sellSymbol,
            'side' => OrderSide::BUY,
            'status' => OrderStatus::OPEN,
            'price' => 1.5
        ]);

        // Buy Order with diff symbol
        Order::factory()->create([
            'symbol' => TradeSymbol::ETH,
            'side' => OrderSide::BUY,
            'status' => OrderStatus::OPEN,
            'price' => 1.5
        ]);

        // Buy Order with diff status
        Order::factory()->create([
            'symbol' => $sellSymbol,
            'side' => OrderSide::BUY,
            'status' => OrderStatus::FILLED,
            'price' => 1.5
        ]);

        // another Sell Order
        Order::factory()->create([
            'symbol' => $sellSymbol,
            'side' => OrderSide::SELL,
            'status' => OrderStatus::OPEN,
            'price' => 1.5
        ]);

        // Buy Order with lower price
        Order::factory()->create([
            'symbol' => $sellSymbol,
            'side' => OrderSide::BUY,
            'status' => OrderStatus::OPEN,
            'price' => 0.5
        ]);

        // Buy Order with later time
        Order::factory()->create([
            'symbol' => $sellSymbol,
            'side' => OrderSide::BUY,
            'status' => OrderStatus::OPEN,
            'price' => 1.5
        ]);

        $buyOrder = $this->repo->findBuyOrder($sellSymbol, $sellOrder->price);

        $this->assertInstanceOf(Order::class, $buyOrder);
        $this->assertEquals($expectedBuyOrder->getKey(), $buyOrder->getKey());
    }
}
