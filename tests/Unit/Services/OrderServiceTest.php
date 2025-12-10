<?php

namespace Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Contracts\OrderServiceInterface;
use App\Contracts\OrderRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Contracts\AssetRepositoryInterface;
use Mockery;
use App\Models\User;
use App\Models\Order;
use App\Models\Asset;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InsufficientAssetException;
use App\Enums\TradeSymbol;
use App\Enums\OrderSide;
use App\Enums\OrderStatus;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * @var OrderServiceInterface
     */
    protected $service;

    /**
     * @var Mockery|OrderRepositoryInterface
     */
    protected $orderRepoMock;

    /**
     * @var Mockery|UserRepositoryInterface
     */
    protected $userRepoMock;

    /**
     * @var Mockery|AssetRepositoryInterface
     */
    protected $assetRepoMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->orderRepoMock = Mockery::mock(OrderRepositoryInterface::class);
        $this->app->instance(OrderRepositoryInterface::class, $this->orderRepoMock);

        $this->userRepoMock = Mockery::mock(UserRepositoryInterface::class);
        $this->app->instance(UserRepositoryInterface::class, $this->userRepoMock);

        $this->assetRepoMock = Mockery::mock(AssetRepositoryInterface::class);
        $this->app->instance(AssetRepositoryInterface::class, $this->assetRepoMock);

        $this->service = $this->app->make(OrderServiceInterface::class);
    }

    public function testCreateBuyOrder()
    {
        $symbol = fake()->randomElement(TradeSymbol::cases())->value;
        $userId = 1;
        $orderId = 2;
        $params = [
            'user_id' => $userId,
            'symbol' => $symbol,
            'amount' => 5,
            'price' => 2
        ];
        $repoParams = $params;
        $repoParams['side'] = OrderSide::BUY->value;

        $userMock = User::factory()->make([
            'id' => $userId,
            'balance' => 10
        ]);
        $orderMock = Order::factory()->make([
            'id' => $orderId
        ]);

        $this->userRepoMock->shouldReceive('find')
            ->with($userId)
            ->once()->andReturn($userMock);

        $this->userRepoMock->shouldReceive('deductBalance')
            ->with($userId, 10)
            ->once();

        $this->orderRepoMock->shouldReceive('create')
            ->with($repoParams)
            ->once()->andReturn($orderMock);

        $order = $this->service->createBuyOrder($params);

        $this->assertInstanceOf(Order::class, $order);
    }

    public function testInsufficientBalanceException()
    {
        $symbol = fake()->randomElement(TradeSymbol::cases())->value;
        $userId = 1;
        $params = [
            'user_id' => $userId,
            'symbol' => $symbol,
            'amount' => 5,
            'price' => 2
        ];

        $userMock = User::factory()->make([
            'id' => $userId,
            'balance' => 1
        ]);

        $this->userRepoMock->shouldReceive('find')
            ->with($userId)
            ->once()->andReturn($userMock);

        $this->expectException(InsufficientBalanceException::class);

        $order = $this->service->createBuyOrder($params);
    }

    public function testCancelBuyOrder()
    {
        $userId = 1;
        $orderId = 2;

        $orderMock = Order::factory()->make([
            'id' => $orderId,
            'user_id' => $userId,
            'amount' => 5,
            'price' => 2
        ]);

        $this->orderRepoMock->shouldReceive('find')
            ->with($orderId)
            ->once()->andReturn($orderMock);

        $this->userRepoMock->shouldReceive('topupBalance')
            ->with($userId, 10)
            ->once();

        $this->orderRepoMock->shouldReceive('update')
            ->with($orderId, ['status' => OrderStatus::CANCELLED->value])
            ->once()->andReturn($orderMock);

        $order = $this->service->cancelBuyOrder($orderId);

        $this->assertInstanceOf(Order::class, $order);
    }

    public function testFillBuyOrderFull()
    {
        $sellAmount = 10;
        $buyOrderAmount = 5;
        $userId = 1;
        $orderId = 2;

        $orderMock = Order::factory()->make([
            'id' => $orderId,
            'user_id' => $userId,
            'amount' => $buyOrderAmount,
            'price' => 3
        ]);

        $this->orderRepoMock->shouldReceive('find')
            ->with($orderId)
            ->once()->andReturn($orderMock);

        $this->assetRepoMock->shouldReceive('bought')
            ->with($userId, $orderMock->symbol->value, $buyOrderAmount)
            ->once();

        $this->orderRepoMock->shouldReceive('update')
            ->with($orderId, ['status' => OrderStatus::FILLED->value])
            ->once()->andReturn($orderMock);

        $order = $this->service->fillBuyOrder($orderId, $sellAmount);

        $this->assertInstanceOf(Order::class, $order);
    }

    public function testFillBuyOrderPartial()
    {
        $sellAmount = 4;
        $buyOrderAmount = 5;
        $userId = 1;
        $orderId = 2;

        $orderMock = Order::factory()->make([
            'id' => $orderId,
            'user_id' => $userId,
            'amount' => $buyOrderAmount,
            'price' => 3
        ]);

        $this->orderRepoMock->shouldReceive('find')
            ->with($orderId)
            ->once()->andReturn($orderMock);

        $this->assetRepoMock->shouldReceive('bought')
            ->with($userId, $orderMock->symbol->value, $sellAmount)
            ->once();

        $this->orderRepoMock->shouldReceive('create')
            ->with([
                'user_id' => $userId,
                'symbol' => $orderMock->symbol,
                'price' => $orderMock->price,
                'amount' => $sellAmount,
                'status' => OrderStatus::FILLED->value
            ])
            ->once()->andReturn(Mockery::mock(Order::class));

        $this->orderRepoMock->shouldReceive('update')
            ->with($orderId, ['amount' => 1])
            ->once()->andReturn($orderMock);

        $order = $this->service->fillBuyOrder($orderId, $sellAmount);

        $this->assertInstanceOf(Order::class, $order);
    }

    public function testCreateSellOrder()
    {
        $symbol = fake()->randomElement(TradeSymbol::cases())->value;
        $userId = 1;
        $orderId = 2;
        $sellAmount = 5;
        $params = [
            'user_id' => $userId,
            'symbol' => $symbol,
            'amount' => $sellAmount,
            'price' => 2
        ];
        $repoParams = $params;
        $repoParams['side'] = OrderSide::SELL->value;

        $assetMock = Asset::factory()->make([
            'user_id' => $userId,
            'symbol' => $symbol,
            'amount' => 10
        ]);

        $orderMock = Order::factory()->make([
            'id' => $orderId
        ]);

        $this->assetRepoMock->shouldReceive('get')
            ->with($userId, $symbol)
            ->once()->andReturn($assetMock);

        $this->assetRepoMock->shouldReceive('lock')
            ->with($userId, $symbol, $sellAmount)
            ->once();

        $this->orderRepoMock->shouldReceive('create')
            ->with($repoParams)
            ->once()->andReturn($orderMock);

        $order = $this->service->createSellOrder($params);

        $this->assertInstanceOf(Order::class, $order);
    }

    public function testInsufficientAssetException()
    {
        $symbol = fake()->randomElement(TradeSymbol::cases())->value;
        $userId = 1;
        $orderId = 2;
        $sellAmount = 15;
        $params = [
            'user_id' => $userId,
            'symbol' => $symbol,
            'amount' => $sellAmount,
            'price' => 2
        ];

        $assetMock = Asset::factory()->make([
            'user_id' => $userId,
            'symbol' => $symbol,
            'amount' => 10
        ]);

        $this->assetRepoMock->shouldReceive('get')
            ->with($userId, $symbol)
            ->once()->andReturn($assetMock);

        $this->expectException(InsufficientAssetException::class);

        $order = $this->service->createSellOrder($params);
    }

    public function testCancelSellOrder()
    {
        $userId = 1;
        $orderId = 2;

        $orderMock = Order::factory()->make([
            'id' => $orderId,
            'user_id' => $userId,
            'amount' => 5,
            'price' => 2
        ]);

        $this->orderRepoMock->shouldReceive('find')
            ->with($orderId)
            ->once()->andReturn($orderMock);

        $this->assetRepoMock->shouldReceive('unlock')
            ->with($userId, $orderMock->symbol->value, $orderMock->amount)
            ->once();

        $this->orderRepoMock->shouldReceive('update')
            ->with($orderId, ['status' => OrderStatus::CANCELLED->value])
            ->once()->andReturn($orderMock);

        $order = $this->service->cancelSellOrder($orderId);

        $this->assertInstanceOf(Order::class, $order);
    }

    public function testFillSellOrderFull()
    {
        $buyAmount = 10;
        $sellOrderAmount = 5;
        $userId = 1;
        $orderId = 2;

        $orderMock = Order::factory()->make([
            'id' => $orderId,
            'user_id' => $userId,
            'amount' => $sellOrderAmount,
            'price' => 3
        ]);

        $this->orderRepoMock->shouldReceive('find')
            ->with($orderId)
            ->once()->andReturn($orderMock);

        $this->assetRepoMock->shouldReceive('sold')
            ->with($userId, $orderMock->symbol->value, $sellOrderAmount)
            ->once();

        $this->userRepoMock->shouldReceive('topupBalance')
            ->with($userId, 15)
            ->once();

        $this->orderRepoMock->shouldReceive('update')
            ->with($orderId, ['status' => OrderStatus::FILLED->value])
            ->once()->andReturn($orderMock);

        $order = $this->service->fillSellOrder($orderId, $buyAmount);

        $this->assertInstanceOf(Order::class, $order);
    }

    public function testFillSellOrderPartial()
    {
        $buyAmount = 10;
        $sellOrderAmount = 15;
        $userId = 1;
        $orderId = 2;

        $orderMock = Order::factory()->make([
            'id' => $orderId,
            'user_id' => $userId,
            'amount' => $sellOrderAmount,
            'price' => 3
        ]);

        $this->orderRepoMock->shouldReceive('find')
            ->with($orderId)
            ->once()->andReturn($orderMock);

        $this->assetRepoMock->shouldReceive('sold')
            ->with($userId, $orderMock->symbol->value, $buyAmount)
            ->once();

        $this->userRepoMock->shouldReceive('topupBalance')
            ->with($userId, 30)
            ->once();

        $this->orderRepoMock->shouldReceive('create')
            ->with([
                'user_id' => $userId,
                'symbol' => $orderMock->symbol,
                'price' => $orderMock->price,
                'amount' => $buyAmount,
                'status' => OrderStatus::FILLED->value
            ])
            ->once();

        $this->orderRepoMock->shouldReceive('update')
            ->with($orderId, ['amount' => 5])
            ->once()->andReturn($orderMock);

        $order = $this->service->fillSellOrder($orderId, $buyAmount);

        $this->assertInstanceOf(Order::class, $order);
    }
}