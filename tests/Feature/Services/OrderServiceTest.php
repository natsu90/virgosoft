<?php

namespace Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Contracts\OrderServiceInterface;
use App\Models\User;
use App\Models\Order;
use App\Models\Asset;
use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Enums\TradeSymbol;
use Illuminate\Support\Collection;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(OrderServiceInterface::class);

        Order::unsetEventDispatcher();
        User::unsetEventDispatcher();
    }

    public function testCreateBuyOrder()
    {
        $symbol = TradeSymbol::BTC;
        // Buyer
        $buyer = User::factory()->create([
            'balance' => 10
        ]);

        $order = $this->service->createBuyOrder([
            'user_id' => $buyer->getKey(),
            'symbol' => $symbol,
            'amount' => 2,
            'price' => 2.5
        ]);

        $this->assertDatabaseHas(Order::getTableName(), [
            'user_id' => $buyer->getKey(),
            'amount' => 2,
            'price' => 2.5,
            'status' => OrderStatus::OPEN,
            'side' => OrderSide::BUY
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $buyer->getKey(),
            'balance' => 5
        ]);
    }

    public function testCancelBuyOrder()
    {
        // Buyer
        $buyer = User::factory()->create([
            'balance' => 10
        ]);

        $order = Order::factory()->create([
            'user_id' => $buyer->getKey(),
            'side' => OrderSide::BUY,
            'status' => OrderStatus::OPEN,
            'price' => 2,
            'amount' => 2
        ]);

        $this->service->cancelBuyOrder($order);

        $this->assertDatabaseHas(Order::getTableName(), [
            'user_id' => $buyer->getKey(),
            'symbol' => $order->symbol,
            'side' => OrderSide::BUY,
            'status' => OrderStatus::CANCELLED
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $buyer->getKey(),
            'balance' => 14
        ]);
    }

    public function testFillBuyOrder()
    {
        // Buyer
        $buyer = User::factory()->create([
            'balance' => 10
        ]);

        $order = Order::factory()->create([
            'user_id' => $buyer->getKey(),
            'side' => OrderSide::BUY,
            'status' => OrderStatus::OPEN,
            'price' => 2,
            'amount' => 2
        ]);

        $result = $this->service->fillBuyOrder($order->getKey(), 3);
        
        $this->assertDatabaseHas(Order::getTableName(), [
            'id' => $order->getKey(),
            'status' => OrderStatus::FILLED
        ]);

        $this->assertInstanceOf(Order::class, $result);
        $this->assertEquals($result->getKey(), $order->getKey());
    }

    public function testFillBuyOrderPartial()
    {
        $symbol = TradeSymbol::BTC->value;
        // Buyer
        $buyer = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $buyer->getKey(),
            'side' => OrderSide::BUY,
            'status' => OrderStatus::OPEN,
            'symbol' => $symbol,
            'price' => 2,
            'amount' => 4
        ]);

        $result = $this->service->fillBuyOrder($order->getKey(), 3);

        $this->assertDatabaseHas(Order::getTableName(), [
            'user_id' => $buyer->getKey(),
            'side' => OrderSide::BUY,
            'status' => OrderStatus::FILLED,
            'symbol' => $symbol,
            'price' => 2,
            'amount' => 3
        ]);
        
        $this->assertDatabaseHas(Order::getTableName(), [
            'id' => $order->getKey(),
            'status' => OrderStatus::OPEN,
            'amount' => 1
        ]);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals($result[1]->getKey(), $order->getKey());
    }

    public function testCreateSellOrder()
    {
        $symbol = TradeSymbol::BTC->value;
        // Seller
        $seller = User::factory()->create();

        $asset = Asset::factory()->create([
            'user_id' => $seller->getKey(),
            'symbol' => $symbol,
            'amount' => 10,
            'locked_amount' => 2
        ]);

        $result = $this->service->createSellOrder([
            'user_id' => $seller->getKey(),
            'symbol' => $symbol,
            'amount' => 2,
            'price' => 2
        ]);

        $this->assertInstanceOf(Order::class, $result);
        $this->assertDatabaseHas(Order::getTableName(), [
            'user_id' => $seller->getKey(),
            'symbol' => $symbol,
            'amount' => 2,
            'price' => 2,
            'status' => OrderStatus::OPEN,
            'side' => OrderSide::SELL
        ]);

        $this->assertDatabaseHas(Asset::getTableName(), [
            'user_id' => $seller->getKey(),
            'symbol' => $symbol,
            'amount' => 8,
            'locked_amount' => 4
        ]);
    }

    public function testCancelSellOrder()
    {
        $symbol = TradeSymbol::BTC->value;
        // Seller
        $seller = User::factory()->create([
            'balance' => 10
        ]);

        $asset = Asset::factory()->create([
            'user_id' => $seller->getKey(),
            'symbol' => $symbol,
            'amount' => 10,
            'locked_amount' => 2
        ]);

        $order = Order::factory()->create([
            'user_id' => $seller->getKey(),
            'symbol' => $symbol,
            'side' => OrderSide::SELL,
            'status' => OrderStatus::OPEN,
            'amount' => 2
        ]);

        $result = $this->service->cancelSellOrder($order);
        
        $this->assertDatabaseHas(Order::getTableName(), [
            'user_id' => $seller->getKey(),
            'symbol' => $order->symbol,
            'side' => OrderSide::SELL,
            'status' => OrderStatus::CANCELLED
        ]);

        $this->assertDatabaseHas(Asset::getTableName(), [
            'user_id' => $seller->getKey(),
            'symbol' => $symbol,
            'amount' => 12,
            'locked_amount' => 0
        ]);
    }

    public function testFillSellOrder()
    {
        // Seller
        $seller = User::factory()->create([
            'balance' => 10
        ]);

        $order = Order::factory()->create([
            'user_id' => $seller->getKey(),
            'side' => OrderSide::SELL,
            'status' => OrderStatus::OPEN,
            'price' => 2,
            'amount' => 2
        ]);

        $result = $this->service->fillSellOrder($order->getKey(), 3);
        
        $this->assertDatabaseHas(Order::getTableName(), [
            'id' => $order->getKey(),
            'status' => OrderStatus::FILLED
        ]);

        $this->assertInstanceOf(Order::class, $result);
        $this->assertEquals($result->getKey(), $order->getKey());
    }

    public function testFillSellOrderPartial()
    {
        // Seller
        $seller = User::factory()->create([
            'balance' => 10
        ]);

        $order = Order::factory()->create([
            'user_id' => $seller->getKey(),
            'side' => OrderSide::SELL,
            'status' => OrderStatus::OPEN,
            'price' => 2,
            'amount' => 4
        ]);

        $result = $this->service->fillSellOrder($order->getKey(), 3);

        $this->assertDatabaseHas(Order::getTableName(), [
            'user_id' => $seller->getKey(),
            'symbol' => $order->symbol,
            'status' => OrderStatus::FILLED,
            'price' => 2,
            'amount' => 3
        ]);
        
        $this->assertDatabaseHas(Order::getTableName(), [
            'id' => $order->getKey(),
            'status' => OrderStatus::OPEN,
            'amount' => 1
        ]);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals($result[1]->getKey(), $order->getKey());
    }
}