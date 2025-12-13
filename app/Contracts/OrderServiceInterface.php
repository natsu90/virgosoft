<?php

namespace App\Contracts;

use App\Models\Order;
use Illuminate\Support\Collection;

interface OrderServiceInterface
{
    /**
     * Create a BUY Order
     */
    public function createBuyOrder(array $params): Order;

    /**
     * Cancel a BUY Order
     */
    public function cancelBuyOrder(Order $buyOrder): Order;

    /**
     * Fill a BUY Order, would return one or two Orders
     */
    public function fillBuyOrder(int $orderId, float $sellAmount): Order|Collection;

    /**
     * Create a SELL Order
     */
    public function createSellOrder(array $params): Order;

    /**
     * Cancel a SELL Order
     */
    public function cancelSellOrder(Order $sellOrder): Order;

    /**
     * Fill a SELL Order, would return one or two Orders
     */
    public function fillSellOrder(int $orderId, float $buyAmount): Order|Collection;
}