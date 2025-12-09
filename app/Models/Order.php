<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\OrderFactory;
use App\Enums\OrderStatus;
use App\Enums\OrderSide;
use App\Enums\TradeSymbol;

class Order extends BaseModel
{
    use HasFactory;
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'orders';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'symbol',
        'side',
        'price',
        'amount',
        'status'
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => OrderStatus::OPEN
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'symbol' => TradeSymbol::class,
            'side' => OrderSide::class
            'status' => OrderStatus::class
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return OrderFactory::new();
    }
}
