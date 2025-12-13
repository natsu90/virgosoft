<?php

namespace App\Contracts;

use App\Models\Order;
use Illuminate\Support\Collection;

interface OrderRepositoryInterface
{
    /**
     * Create a new Order record
     * 
     * @param array $params
     * @return Order
     */
    public function create(array $params): Order;

    /**
     * Update the Order record
     * 
     * @param int $id
     * @param array $params
     * @return Order
     */
    public function update(int $id, array $params): Order;

    /**
     * Get the Order record
     * 
     * @param int $id
     * @return Order
     */
    public function find(int $id): Order;

    /**
     * Find a Sell Order with given Buy Price
     * 
     * @param string $symbol
     * @param float $buyPrice
     * @return Order
     */
    public function findSellOrder(string $symbol, float $buyPrice): Order|null;

    /**
     * Find a Buy Order with given Sell Price
     * 
     * @param string $symbol
     * @param float $sellPrice
     * @return Order
     */
    public function findBuyOrder(string $symbol, float $sellPrice): Order|null;

    /**
     * Get All Orders filtered by given parameters
     * 
     * @param array $params
     * @return Collection
     */
    public function getAll(array $params): Collection;
}