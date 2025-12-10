<?php

namespace App\Contracts;

use App\Models\Trade;

interface TradeRepositoryInterface
{
    /**
     * Create a Trade
     */
    public function create(array $params): Trade;
}