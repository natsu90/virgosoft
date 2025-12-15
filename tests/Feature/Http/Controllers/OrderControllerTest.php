<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Asset;
use Illuminate\Support\Facades\Event;
use App\Events\OrderCreated;
use App\Enums\TradeSymbol;
use App\Enums\OrderSide;
use App\Enums\OrderStatus;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function setUp(): void
    {
        parent::setUp();

        Event::fake(OrderCreated::class);
    }

    public function testGetAll()
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        Order::factory()->count(10)->create([
            'user_id' => $user->getKey()
        ]);

        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('/api/orders', [
                'symbol' => TradeSymbol::BTC->value
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'symbol',
                        'price',
                        'amount',
                        'side',
                        'status'
                    ]
                ]
            ]);
    }

    public function testCreateBuy()
    {
        $user = User::factory()->create([
            'balance' => 10
        ]);
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->post('/api/orders',
            [
                'user_id' => $user->getKey(),
                'symbol' => TradeSymbol::BTC->value,
                'side' => OrderSide::BUY->value,
                'price' => 2,
                'amount' => 2
            ],
            ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'symbol',
                    'price',
                    'amount',
                    'side',
                    'status'
                ]
            ])
            ->assertJsonPath('data.status', OrderStatus::OPEN->value);
    }

    public function testCreateSell()
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        Asset::where([
             'user_id' => $user->getKey(),
            'symbol' => TradeSymbol::BTC->value
        ])->update([
            'amount' => 10,
            'locked_amount' => 0
        ]);

        $response = $this->post('/api/orders',
            [
                'user_id' => $user->getKey(),
                'symbol' => TradeSymbol::BTC->value,
                'side' => OrderSide::SELL->value,
                'price' => 2,
                'amount' => 2
            ],
            ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'symbol',
                    'price',
                    'amount',
                    'side',
                    'status'
                ]
            ])
            ->assertJsonPath('data.status', OrderStatus::OPEN->value);
    }

    public function testCancelBuy()
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $order = Order::factory()->create([
            'side' => OrderSide::BUY,
            'status' => OrderStatus::OPEN
        ]);

        $response = $this->post('/api/orders/'. $order->getKey() .'/cancel',
            [],
            ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'symbol',
                    'price',
                    'amount',
                    'side',
                    'status'
                ]
            ])
            ->assertJsonPath('data.status', OrderStatus::CANCELLED->value);
    }

    public function testCancelSell()
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;
        $symbol = TradeSymbol::BTC->value;

        Asset::where([
            'user_id' => $user->getKey(),
            'symbol' => $symbol
        ])->update([
            'locked_amount' => 2
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->getKey(),
            'side' => OrderSide::SELL,
            'status' => OrderStatus::OPEN,
            'symbol' => $symbol,
            'amount' => 2
        ]);

        $this->assertDatabaseHas(Asset::getTableName(), [
            'user_id' => $user->getKey(),
            'symbol' => $symbol
        ]);

        $response = $this->post('/api/orders/'. $order->getKey() .'/cancel',
            [],
            ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'symbol',
                    'price',
                    'amount',
                    'side',
                    'status'
                ]
            ])
            ->assertJsonPath('data.status', OrderStatus::CANCELLED->value);
    }
}