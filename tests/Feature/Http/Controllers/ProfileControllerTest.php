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
use Database\Seeders\TradeSeeder;
use App\Enums\OrderStatus;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function testRegister()
    {
        $email = fake()->unique()->safeEmail();
        $password = fake()->slug(2);

        $response = $this->post('/api/register', [
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'email',
                    'balance'
                ]
            ])
            ->assertJsonPath('data.email', $email)
            ->assertJsonPath('data.balance', 10);
    }

    public function testLogin()
    {
        $password = fake()->slug(2);
        $user = User::factory()->create([
            'password' => $password
        ]);

        $response = $this->post('/api/login', [
            'email' => $user->email,
            'password' => $password
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'token'
                ]
            ]);
    }

    public function testLogout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->post('/api/logout',
            [],
            ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message'
            ]);
    }

    public function testIndex()
    {
        Event::fake(OrderCreated::class);

        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        Order::factory()->count(10)->create([
            'user_id' => $user->getKey(),
            'status' => fake()->randomElement([OrderStatus::OPEN, OrderStatus::CANCELLED])
        ]);

        $seeder = new TradeSeeder;
        $seeder->run($user->getKey());

        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('/api/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'email',
                    'balance',
                    'assets' => [
                        '*' => [
                            'symbol',
                            'amount',
                            'locked_amount'
                        ]
                    ],
                    'orders' => [
                        '*' => [
                            'id',
                            'symbol',
                            'price',
                            'amount',
                            'side',
                            'status'
                        ]
                    ],
                    'trades' => [
                        '*' => [
                            'id',
                            'side',
                            'symbol',
                            'price',
                            'amount',
                            'sales',
                            'commission'
                        ]
                    ]
                ]
            ]);
    }
}