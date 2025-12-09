<?php

namespace App\Models;

use App\Enums\TradeSymbol;

class Trade extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'trades';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'buy_order_id',
        'sell_order_id',
        'symbol',
        'price',
        'amount',
        'commission'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'symbol' => TradeSymbol::class
        ];
    }

    /**
    * The name of the "updated at" column.
    *
    * @var string
    */
    const UPDATED_AT = null;
}
