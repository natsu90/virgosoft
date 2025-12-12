<?php

namespace Tests\Listeners;

use Tests\TestCase;
use App\Listeners\FindOrderMatch;
use App\Contracts\TradeServiceInterface;
use Mockery;
use App\Models\Order;
use App\Events\OrderCreated;
use App\Enums\OrderStatus;
use App\Enums\OrderSide;

class FindOrderMatchTest extends TestCase
{
    /**
     * @var Mockery|TradeServiceInterface
     */
    protected $tradeServiceMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->tradeServiceMock = Mockery::mock(TradeServiceInterface::class);
        $this->app->instance(TradeServiceInterface::class, $this->tradeServiceMock);
    }

    public function testHandleFilledOrder()
    {
        $order = Order::factory()->make([
            'status' => OrderStatus::FILLED->value
        ]);

        $this->tradeServiceMock->shouldReceive('findBuyOrderMatch')
            ->never();

        $this->tradeServiceMock->shouldReceive('findSellOrderMatch')
            ->never();

        $listener = new FindOrderMatch($this->tradeServiceMock);
        $listener->handle(new OrderCreated($order));
    }

    public function testHandleBuyOrder()
    {
        $order = Order::factory()->make([
            'side' => OrderSide::BUY->value,
            'status' => OrderStatus::OPEN->value
        ]);

        $this->tradeServiceMock->shouldReceive('findBuyOrderMatch')
            ->once();

        $this->tradeServiceMock->shouldReceive('findSellOrderMatch')
            ->never();

        $listener = new FindOrderMatch($this->tradeServiceMock);
        $listener->handle(new OrderCreated($order));
    }

    public function testHandleSellOrder()
    {
        $order = Order::factory()->make([
            'side' => OrderSide::SELL->value,
            'status' => OrderStatus::OPEN->value
        ]);

        $this->tradeServiceMock->shouldReceive('findBuyOrderMatch')
            ->never();

        $this->tradeServiceMock->shouldReceive('findSellOrderMatch')
            ->once();

        $listener = new FindOrderMatch($this->tradeServiceMock);
        $listener->handle(new OrderCreated($order));
    }
}