<?php

namespace App\Contracts;

use App\Models\Trade;
use Illuminate\Support\Collection;

interface TradeRepositoryInterface
{
    /**
     * Create a Trade
     */
    public function create(array $params): Trade;

    /**
     * Get All Trades by User ID
     */
    public function getAll(array $params): Collection;
}