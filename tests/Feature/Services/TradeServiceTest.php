<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Contracts\TradeServiceInterface;
use App\Events\OrderMatched;
use App\Events\OrderCreated;
use App\Models\User;
use App\Models\Order;
use App\Models\Asset;
use App\Models\Trade;
use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Enums\TradeSymbol;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class TradeServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(TradeServiceInterface::class);

        Event::fake([
            OrderMatched::class,
            OrderCreated::class
        ]);
    }

    public function testCreate()
    {
        $symbol = TradeSymbol::BTC;

        // Seller
        $seller = User::factory()->create([
            'balance' => 1
        ]);
        // Asset Seller
        Asset::factory()->create([
            'user_id' => $seller->getKey(),
            'symbol' => $symbol,
            'amount' => 10,
            'locked_amount' => 2
        ]);
        // Buyer
        $buyer = User::factory()->create([
            'balance' => 10
        ]);
        // Asset Buyer
        Asset::factory()->create([
            'user_id' => $buyer->getKey(),
            'symbol' => $symbol,
            'amount' => 0,
            'locked_amount' => 0
        ]);

        // BUY Order
        $buyOrder = Order::factory()->create([
            'user_id' => $buyer->getKey(),
            'symbol' => $symbol,
            'price' => 2,
            'amount' => 2,
            'status' => OrderStatus::FILLED
        ]);
        // SELL Order
        $sellOrder = Order::factory()->create([
            'user_id' => $seller->getKey(),
            'symbol' => $symbol,
            'price' => 1.5,
            'amount' => 2,
            'status' => OrderStatus::FILLED
        ]);

        $this->service->create($sellOrder, $buyOrder);

        $sales = $buyOrder->amount * $buyOrder->price;
        $commission = $sales * 0.015;
        $netSales = $sales - $commission;

        // Assert Seller Balance
        $this->assertDatabaseHas('users', [
            'id' => $seller->getKey(),
            'balance' => 1 + $netSales
        ]);

        // Assert Seller Asset
        $this->assertDatabaseHas(Asset::getTableName(), [
            'user_id' => $seller->getKey(),
            'symbol' => $sellOrder->symbol,
            'amount' => 10,
            'locked_amount' => 0
        ]);

        // Assert Buyer Asset
        $this->assertDatabaseHas(Asset::getTableName(), [
            'user_id' => $buyer->getKey(),
            'symbol' => $buyOrder->symbol,
            'amount' => 2,
            'locked_amount' => 0
        ]);

        // Assert new Trade
        $this->assertDatabaseHas(Trade::getTableName(), [
            'sell_order_id' => $sellOrder->getKey(),
            'buy_order_id' => $buyOrder->getKey(),
            'symbol' => $buyOrder->symbol,
            'amount' => 2,
            'price' => 2,
            'commission' => $commission
        ]);
    }

    public function testFindSellOrderNoMatch()
    {
         $symbol = TradeSymbol::BTC;

        // Seller
        $seller = User::factory()->create([
            'balance' => 1
        ]);

        // SELL Order
        $sellOrder = Order::factory()->create([
            'user_id' => $seller->getKey(),
            'symbol' => $symbol,
            'price' => 1.5,
            'amount' => 2,
            'status' => OrderStatus::OPEN,
            'side' => OrderSide::SELL
        ]);

        $this->service->findSellOrderMatch($sellOrder);

        Event::assertNotDispatched(OrderMatched::class);
    }

    public function testFindSellOrderMatch()
    {
        $symbol = TradeSymbol::BTC;

        // Seller
        $seller = User::factory()->create([
            'balance' => 1
        ]);
        // Asset Seller
        Asset::factory()->create([
            'user_id' => $seller->getKey(),
            'symbol' => $symbol,
            'amount' => 10,
            'locked_amount' => 2
        ]);
        // Buyer
        $buyer = User::factory()->create([
            'balance' => 10
        ]);
        // Asset Buyer
        Asset::factory()->create([
            'user_id' => $buyer->getKey(),
            'symbol' => $symbol,
            'amount' => 0,
            'locked_amount' => 0
        ]);

        // BUY Order has higher amount than SELL
        $buyOrder = Order::factory()->create([
            'user_id' => $buyer->getKey(),
            'symbol' => $symbol,
            'price' => 2,
            'amount' => 3,
            'status' => OrderStatus::OPEN,
            'side' => OrderSide::BUY
        ]);
        // SELL Order
        $sellOrder = Order::factory()->create([
            'user_id' => $seller->getKey(),
            'symbol' => $symbol,
            'price' => 1.5,
            'amount' => 2,
            'status' => OrderStatus::OPEN,
            'side' => OrderSide::SELL
        ]);

        $this->service->findSellOrderMatch($sellOrder);

        // Assert OrderMatched event is dispatched
        Event::assertDispatched(OrderMatched::class, function($event) use ($sellOrder, $buyOrder) {
            return $event->sellOrder->getKey() === $sellOrder->getKey()
                && $event->buyOrder->getKey() === $buyOrder->getKey();
        });

        // Assert Filled SELL Order
        $this->assertDatabaseHas(Order::getTableName(), [
            'id' => $sellOrder->getKey(),
            'symbol' => $symbol,
            'price' => $sellOrder->price,
            'amount' => $sellOrder->amount,
            'status' => OrderStatus::FILLED,
            'side' => OrderSide::SELL
        ]);

        // Assert Open BUY Order with a new Amount
        $this->assertDatabaseHas(Order::getTableName(), [
            'id' => $buyOrder->getKey(),
            'amount' => 1,
            'symbol' => $buyOrder->symbol,
            'price' => $buyOrder->price,
            'status' => OrderStatus::OPEN,
            'side' => OrderSide::BUY
        ]);
        // Assert a new Filled BUY Order
        $filledAmount = 2;
        $this->assertDatabaseHas(Order::getTableName(), [
            'user_id' => $buyer->getKey(),
            'amount' => $filledAmount,
            'symbol' => $buyOrder->symbol,
            'price' => $buyOrder->price,
            'status' => OrderStatus::FILLED,
            'side' => OrderSide::BUY
        ]);
        // Fetch the Filled BUY Order
        $filledBuyOrder = Order::where([
            'user_id' => $buyer->getKey(),
            'amount' => $filledAmount,
            'symbol' => $buyOrder->symbol,
            'price' => $buyOrder->price,
            'status' => OrderStatus::FILLED,
            'side' => OrderSide::BUY
        ])->first();

        $sales = $filledAmount * $buyOrder->price;
        $commission = $sales * 0.015;
        $netSales = $sales - $commission;

        // Assert Seller Balance
        $this->assertDatabaseHas('users', [
            'id' => $seller->getKey(),
            'balance' => 1 + $netSales
        ]);

        // Assert Seller Asset
        $this->assertDatabaseHas(Asset::getTableName(), [
            'user_id' => $seller->getKey(),
            'symbol' => $sellOrder->symbol,
            'amount' => 10,
            'locked_amount' => 0
        ]);

        // Assert Buyer Asset
        $this->assertDatabaseHas(Asset::getTableName(), [
            'user_id' => $buyer->getKey(),
            'symbol' => $buyOrder->symbol,
            'amount' => 2,
            'locked_amount' => 0
        ]);

        // Assert new Trade
        $this->assertDatabaseHas(Trade::getTableName(), [
            'sell_order_id' => $sellOrder->getKey(),
            'buy_order_id' => $filledBuyOrder->getKey(),
            'symbol' => $buyOrder->symbol,
            'amount' => $filledAmount,
            'price' => 2,
            'commission' => $commission
        ]);
    }

    public function testFindSellOrderMatchPartial()
    {
        $symbol = TradeSymbol::BTC;

        // Seller
        $seller = User::factory()->create([
            'balance' => 1
        ]);
        // Asset Seller
        Asset::factory()->create([
            'user_id' => $seller->getKey(),
            'symbol' => $symbol,
            'amount' => 10,
            'locked_amount' => 4
        ]);
        // Buyer
        $buyer = User::factory()->create([
            'balance' => 10
        ]);
        // Asset Buyer
        Asset::factory()->create([
            'user_id' => $buyer->getKey(),
            'symbol' => $symbol,
            'amount' => 0,
            'locked_amount' => 0
        ]);

        // BUY Order
        $buyOrder = Order::factory()->create([
            'user_id' => $buyer->getKey(),
            'symbol' => $symbol,
            'price' => 2,
            'amount' => 3,
            'status' => OrderStatus::OPEN,
            'side' => OrderSide::BUY
        ]);
        // SELL Order has higher Amount than BUY
        $sellOrder = Order::factory()->create([
            'user_id' => $seller->getKey(),
            'symbol' => $symbol,
            'price' => 1.5,
            'amount' => 4,
            'status' => OrderStatus::OPEN,
            'side' => OrderSide::SELL
        ]);

        $this->service->findSellOrderMatch($sellOrder);

        // Assert OrderMatched event is dispatched
        Event::assertDispatched(OrderMatched::class, function($event) use ($sellOrder, $buyOrder) {
            return $event->sellOrder->getKey() === $sellOrder->getKey()
                && $event->buyOrder->getKey() === $buyOrder->getKey();
        });

        // Assert Open SELL Order with a new Amount
        $this->assertDatabaseHas(Order::getTableName(), [
            'id' => $sellOrder->getKey(),
            'symbol' => $symbol,
            'price' => $sellOrder->price,
            'amount' => 1,
            'status' => OrderStatus::OPEN,
            'side' => OrderSide::SELL
        ]);
        // Assert a new Filled SELL Order
        $filledAmount = 3;
        $this->assertDatabaseHas(Order::getTableName(), [
            'user_id' => $seller->getKey(),
            'amount' => $filledAmount,
            'symbol' => $sellOrder->symbol,
            'price' => $sellOrder->price,
            'status' => OrderStatus::FILLED,
            'side' => OrderSide::SELL
        ]);

        // Assert Filled BUY Order
        $this->assertDatabaseHas(Order::getTableName(), [
            'user_id' => $buyer->getKey(),
            'amount' => $buyOrder->amount,
            'symbol' => $buyOrder->symbol,
            'price' => $buyOrder->price,
            'status' => OrderStatus::FILLED,
            'side' => OrderSide::BUY
        ]);

        // Fetch a new Filled SELL Order
        $filledSellOrder = Order::where([
            'user_id' => $seller->getKey(),
            'amount' => $filledAmount,
            'symbol' => $sellOrder->symbol,
            'price' => $sellOrder->price,
            'status' => OrderStatus::FILLED,
            'side' => OrderSide::SELL
        ])->first();

        $sales = $filledAmount * $buyOrder->price;
        $commission = $sales * 0.015;
        $netSales = $sales - $commission;

        // Assert Seller Balance
        $this->assertDatabaseHas('users', [
            'id' => $seller->getKey(),
            'balance' => 1 + $netSales
        ]);

        // Assert Seller Asset
        $this->assertDatabaseHas(Asset::getTableName(), [
            'user_id' => $seller->getKey(),
            'symbol' => $sellOrder->symbol,
            'amount' => 10,
            'locked_amount' => 1
        ]);

        // Assert Buyer Asset
        $this->assertDatabaseHas(Asset::getTableName(), [
            'user_id' => $buyer->getKey(),
            'symbol' => $buyOrder->symbol,
            'amount' => 3,
            'locked_amount' => 0
        ]);

        // Assert new Trade
        $this->assertDatabaseHas(Trade::getTableName(), [
            'sell_order_id' => $filledSellOrder->getKey(),
            'buy_order_id' => $buyOrder->getKey(),
            'symbol' => $buyOrder->symbol,
            'amount' => $filledAmount,
            'price' => 2,
            'commission' => $commission
        ]);
    }

    public function testFindBuyOrderNoMatch()
    {
         $symbol = TradeSymbol::BTC;

        // Buyer
        $buyer = User::factory()->create([
            'balance' => 10
        ]);

        // BUY Order
        $buyOrder = Order::factory()->create([
            'user_id' => $buyer->getKey(),
            'symbol' => $symbol,
            'price' => 2,
            'amount' => 3,
            'status' => OrderStatus::OPEN,
            'side' => OrderSide::BUY
        ]);

        $this->service->findBuyOrderMatch($buyOrder);

        Event::assertNotDispatched(OrderMatched::class);
    }

    public function testFindBuyOrderMatch()
    {
        $symbol = TradeSymbol::BTC;

        // Seller
        $seller = User::factory()->create([
            'balance' => 1
        ]);
        // Asset Seller
        Asset::factory()->create([
            'user_id' => $seller->getKey(),
            'symbol' => $symbol,
            'amount' => 10,
            'locked_amount' => 4
        ]);
        // Buyer
        $buyer = User::factory()->create([
            'balance' => 10
        ]);
        // Asset Buyer
        Asset::factory()->create([
            'user_id' => $buyer->getKey(),
            'symbol' => $symbol,
            'amount' => 0,
            'locked_amount' => 0
        ]);

        // BUY Order
        $buyOrder = Order::factory()->create([
            'user_id' => $buyer->getKey(),
            'symbol' => $symbol,
            'price' => 2,
            'amount' => 3,
            'status' => OrderStatus::OPEN,
            'side' => OrderSide::BUY
        ]);
        // SELL Order has higher Amount than BUY
        $sellOrder = Order::factory()->create([
            'user_id' => $seller->getKey(),
            'symbol' => $symbol,
            'price' => 1.5,
            'amount' => 4,
            'status' => OrderStatus::OPEN,
            'side' => OrderSide::SELL
        ]);

        $this->service->findBuyOrderMatch($buyOrder);

        // Assert OrderMatched event is dispatched
        Event::assertDispatched(OrderMatched::class, function($event) use ($sellOrder, $buyOrder) {
            return $event->sellOrder->getKey() === $sellOrder->getKey()
                && $event->buyOrder->getKey() === $buyOrder->getKey();
        });

        // Assert Filled BUY Order
        $this->assertDatabaseHas(Order::getTableName(), [
            'id' => $buyOrder->getKey(),
            'symbol' => $symbol,
            'price' => $buyOrder->price,
            'amount' => $buyOrder->amount,
            'status' => OrderStatus::FILLED,
            'side' => OrderSide::BUY
        ]);

        // Assert Open SELL Order with a new Amount
        $this->assertDatabaseHas(Order::getTableName(), [
            'id' => $sellOrder->getKey(),
            'symbol' => $symbol,
            'price' => $sellOrder->price,
            'amount' => 1,
            'status' => OrderStatus::OPEN,
            'side' => OrderSide::SELL
        ]);

        // Assert a new Filled SELL Order
        $filledAmount = 3;
        $this->assertDatabaseHas(Order::getTableName(), [
            'user_id' => $seller->getKey(),
            'symbol' => $symbol,
            'price' => $sellOrder->price,
            'amount' => $filledAmount,
            'status' => OrderStatus::FILLED,
            'side' => OrderSide::SELL
        ]);
        
        // Fetch the Filled SELL Order
        $filledSellOrder = Order::where([
            'user_id' => $seller->getKey(),
            'amount' => $filledAmount,
            'symbol' => $sellOrder->symbol,
            'price' => $sellOrder->price,
            'status' => OrderStatus::FILLED,
            'side' => OrderSide::SELL
        ])->first();

        $sales = $filledAmount * $buyOrder->price;
        $commission = $sales * 0.015;
        $netSales = $sales - $commission;

        // Assert Seller Balance
        $this->assertDatabaseHas('users', [
            'id' => $seller->getKey(),
            'balance' => 1 + $netSales
        ]);

        // Assert Seller Asset
        $this->assertDatabaseHas(Asset::getTableName(), [
            'user_id' => $seller->getKey(),
            'symbol' => $sellOrder->symbol,
            'amount' => 10,
            'locked_amount' => 1
        ]);

        // Assert Buyer Asset
        $this->assertDatabaseHas(Asset::getTableName(), [
            'user_id' => $buyer->getKey(),
            'symbol' => $buyOrder->symbol,
            'amount' => 3,
            'locked_amount' => 0
        ]);

        // Assert new Trade
        $this->assertDatabaseHas(Trade::getTableName(), [
            'sell_order_id' => $filledSellOrder->getKey(),
            'buy_order_id' => $buyOrder->getKey(),
            'symbol' => $buyOrder->symbol,
            'amount' => $filledAmount,
            'price' => 2,
            'commission' => $commission
        ]);
    }

    public function testFindBuyOrderMatchPartial()
    {
        $symbol = TradeSymbol::BTC;

        // Seller
        $seller = User::factory()->create([
            'balance' => 1
        ]);
        // Asset Seller
        Asset::factory()->create([
            'user_id' => $seller->getKey(),
            'symbol' => $symbol,
            'amount' => 10,
            'locked_amount' => 2
        ]);
        // Buyer
        $buyer = User::factory()->create([
            'balance' => 10
        ]);
        // Asset Buyer
        Asset::factory()->create([
            'user_id' => $buyer->getKey(),
            'symbol' => $symbol,
            'amount' => 0,
            'locked_amount' => 0
        ]);

        // BUY Order has higher Amount than SELL
        $buyOrder = Order::factory()->create([
            'user_id' => $buyer->getKey(),
            'symbol' => $symbol,
            'price' => 2,
            'amount' => 3,
            'status' => OrderStatus::OPEN,
            'side' => OrderSide::BUY
        ]);
        // SELL Order
        $sellOrder = Order::factory()->create([
            'user_id' => $seller->getKey(),
            'symbol' => $symbol,
            'price' => 1.5,
            'amount' => 2,
            'status' => OrderStatus::OPEN,
            'side' => OrderSide::SELL
        ]);

        $this->service->findBuyOrderMatch($buyOrder);

        // Assert OrderMatched event is dispatched
        Event::assertDispatched(OrderMatched::class, function($event) use ($sellOrder, $buyOrder) {
            return $event->sellOrder->getKey() === $sellOrder->getKey()
                && $event->buyOrder->getKey() === $buyOrder->getKey();
        });

        // Assert Open BUY Order with a new Amount
        $this->assertDatabaseHas(Order::getTableName(), [
            'id' => $buyOrder->getKey(),
            'symbol' => $symbol,
            'price' => $buyOrder->price,
            'amount' => 1,
            'status' => OrderStatus::OPEN,
            'side' => OrderSide::BUY
        ]);

        // Assert a new Filled BUY Order
        $filledAmount = 2;
        $this->assertDatabaseHas(Order::getTableName(), [
            'user_id' => $buyer->getKey(),
            'symbol' => $symbol,
            'price' => $buyOrder->price,
            'amount' => $filledAmount,
            'status' => OrderStatus::FILLED,
            'side' => OrderSide::BUY
        ]);

        // Assert Filled SELL Order
        $this->assertDatabaseHas(Order::getTableName(), [
            'id' => $sellOrder->getKey(),
            'symbol' => $symbol,
            'price' => $sellOrder->price,
            'amount' => $sellOrder->amount,
            'status' => OrderStatus::FILLED,
            'side' => OrderSide::SELL
        ]);

        // Fetch the Filled BUY Order
        $filledBuyOrder = Order::where([
            'user_id' => $buyer->getKey(),
            'amount' => $filledAmount,
            'symbol' => $buyOrder->symbol,
            'price' => $buyOrder->price,
            'status' => OrderStatus::FILLED,
            'side' => OrderSide::BUY
        ])->first();

        $sales = $filledAmount * $buyOrder->price;
        $commission = $sales * 0.015;
        $netSales = $sales - $commission;

        // Assert Seller Balance
        $this->assertDatabaseHas('users', [
            'id' => $seller->getKey(),
            'balance' => 1 + $netSales
        ]);

        // Assert Seller Asset
        $this->assertDatabaseHas(Asset::getTableName(), [
            'user_id' => $seller->getKey(),
            'symbol' => $sellOrder->symbol,
            'amount' => 10,
            'locked_amount' => 0
        ]);

        // Assert Buyer Asset
        $this->assertDatabaseHas(Asset::getTableName(), [
            'user_id' => $buyer->getKey(),
            'symbol' => $buyOrder->symbol,
            'amount' => 2,
            'locked_amount' => 0
        ]);

        // Assert new Trade
        $this->assertDatabaseHas(Trade::getTableName(), [
            'sell_order_id' => $sellOrder->getKey(),
            'buy_order_id' => $filledBuyOrder->getKey(),
            'symbol' => $buyOrder->symbol,
            'amount' => $filledAmount,
            'price' => 2,
            'commission' => $commission
        ]);
    }
}