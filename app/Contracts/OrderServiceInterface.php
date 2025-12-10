<?php

namespace App\Contracts;

use App\Models\Order;

interface OrderServiceInterface
{
    /**
     * Create a BUY Order
     */
    public function createBuyOrder(array $params): Order;

    /**
     * Cancel a BUY Order
     */
    public function cancelBuyOrder(int $orderId): Order;

    /**
     * Fill a BUY Order
     */
    public function fillBuyOrder(int $orderId, float $sellAmount): Order;

    /**
     * Create a SELL Order
     */
    public function createSellOrder(array $params): Order;

    /**
     * Cancel a SELL Order
     */
    public function cancelSellOrder(int $orderId): Order;

    /**
     * Fill a SELL Order
     */
    public function fillSellOrder(int $orderId, float $buyAmount): Order;
}