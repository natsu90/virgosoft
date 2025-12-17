<?php

namespace App\Repositories;

use App\Contracts\TradeRepositoryInterface;
use App\Models\Trade;
use Illuminate\Support\Collection;

class TradeRepository implements TradeRepositoryInterface
{
    public function create(array $params): Trade
    {
        return Trade::create($params);
    }

    public function getAll(array $params): Collection
    {
        $userId = $params['user_id'];

        return Trade::select('trades.*')
            ->selectRaw("(CASE WHEN buy.user_id='". $userId ."' THEN buy.side ELSE sell.side END) AS side")
            ->selectRaw('ROUND(trades.price * trades.amount + commission, 4) AS sales')
            ->leftJoin('orders as buy', 'buy.id', '=', 'trades.buy_order_id')
            ->leftJoin('orders as sell', 'sell.id', '=', 'trades.sell_order_id')
            ->where('buy.user_id', $userId)
            ->Orwhere('sell.user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}