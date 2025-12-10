<?php

namespace App\Repositories;

use App\Contracts\TradeRepositoryInterface;
use App\Models\Trade;

class TradeRepository implements TradeRepositoryInterface
{
    public function create(array $params): Trade
    {
        return Trade::create($params);
    }
}