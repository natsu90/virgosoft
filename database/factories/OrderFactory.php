<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Order;
use App\Enums\TradeSymbol;
use App\Enums\OrderSide;
use App\Enums\OrderStatus;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'symbol' => fake()->randomElement(TradeSymbol::cases()),
            'side' => fake()->randomElement(OrderSide::cases()),
            'price' => fake()->randomFloat(4, 10, 100),
            'amount' => fake()->randomFloat(8, 0.0001, 10),
            'status' => fake()->randomElement([OrderStatus::OPEN, OrderStatus::CANCELLED])
        ];
    }
}
