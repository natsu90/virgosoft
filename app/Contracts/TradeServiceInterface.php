<?php

namespace App\Contracts;

use App\Models\Trade;
use App\Models\Order;

interface TradeServiceInterface
{
    const COMMISSION_PERCENTAGE = 0.015;

    /**
     * Find a BUY Order to match with a SELL Order
     */
    public function findSellOrderMatch(Order $sellOrder): void;

    /**
     * Find a SELL Order to match with a BUY Order
     */
    public function findBuyOrderMatch(Order $buyOrder): void;
    
    /**
     * Create a Trade record from a SELL Order & a BUY Order
     */
    public function create(Order $sellOrder, Order $buyOrder): Trade;
}