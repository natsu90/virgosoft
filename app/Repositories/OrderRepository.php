<?php

namespace App\Repositories;

use App\Contracts\OrderRepositoryInterface;
use App\Models\Order;
use App\Enums\OrderSide;
use App\Enums\OrderStatus;

class OrderRepository implements OrderRepositoryInterface
{
    public function create(array $params): Order
    {
        return Order::create($params);
    }

    public function update(int $id, array $params): Order
    {
        $order = $this->find($id);
        $order->update($params);
        $order->refresh();

        return $order;
    }

    public function find(int $id): Order
    {
        return Order::findOrFail($id);
    }

    public function findSellOrder(string $symbol, float $buyPrice): Order|null
    {
        return Order::where('symbol', $symbol)
            ->where('side', OrderSide::SELL)
            ->where('status', OrderStatus::OPEN)
            ->where('price', '<=', $buyPrice)
            ->orderBy('id', 'asc')
            ->first();
    }

    public function findBuyOrder(string $symbol, float $sellPrice): Order|null
    {
        return Order::where('symbol', $symbol)
            ->where('side', OrderSide::BUY)
            ->where('status', OrderStatus::OPEN)
            ->where('price', '>=', $sellPrice)
            ->orderBy('id', 'asc')
            ->first();
    }
}