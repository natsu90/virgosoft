<?php

namespace App\Services;

use App\Contracts\OrderServiceInterface;
use App\Contracts\OrderRepositoryInterface;
use App\Contracts\AssetRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InsufficientAssetException;
use App\Models\Order;
use Illuminate\Support\Collection;

class OrderService implements OrderServiceInterface
{
    public function __construct(
        protected OrderRepositoryInterface $orderRepository,
        protected UserRepositoryInterface $userRepository,
        protected AssetRepositoryInterface $assetRepository
    ) {}

    public function createBuyOrder(array $params): Order
    {
        $userId = $params['user_id'];
        $cost = $params['amount'] * $params['price'];
        $user = $this->userRepository->find($userId);

        if ($user->balance < $cost) {
            throw new InsufficientBalanceException;
        }

        // deduct balance
        $this->userRepository->deductBalance($userId, $cost);
        
        // create Order
        $params['side'] = OrderSide::BUY->value;
        return $this->orderRepository->create($params);
    }

    public function cancelBuyOrder(int $orderId): Order
    {
        $order = $this->orderRepository->find($orderId);
        $userId = $order->user_id;
        $cost = $order->amount * $order->price;

        // restore Balance
        $this->userRepository->topupBalance($userId, $cost);

        // update Order status
        return $this->orderRepository->update($orderId, [
            'status' => OrderStatus::CANCELLED->value
        ]);
    }

    public function fillBuyOrder(int $orderId, float $sellAmount): Order|Collection
    {
        $order = $this->orderRepository->find($orderId);

        if ($order->amount <= $sellAmount) {

            // update OrderStatus
            return $this->orderRepository->update($orderId, [
                'status' => OrderStatus::FILLED->value
            ]);

        // partial fill
        } else {

            // create a filled Order
            $filledOrder = $this->orderRepository->create([
                'user_id' => $order->user_id,
                'symbol' => $order->symbol,
                'price' => $order->price,
                'amount' => $sellAmount,
                'status' => OrderStatus::FILLED->value
            ]);

            // update unfullfilled Order amount
            $incompleteOrder = $this->orderRepository->update($orderId, [
                'amount' => $order->amount - $sellAmount
            ]);

            return collect([$filledOrder, $incompleteOrder]);
        }
    }

    public function createSellOrder(array $params): Order
    {
        $userId = $params['user_id'];
        $tradeSymbol = $params['symbol'];
        $amount = $params['amount'];
        $asset = $this->assetRepository->get($userId, $tradeSymbol);

        if ($asset->amount < $amount) {
            throw new InsufficientAssetException;
        }

        // lock Asset amount
        $this->assetRepository->lock($userId, $tradeSymbol, $amount);

        // create Order
        return $this->orderRepository->create([
            'side' => OrderSide::SELL->value,
            'user_id' => $userId,
            'symbol' => $tradeSymbol,
            'price' => $params['price'],
            'amount' => $amount
        ]);
    }

    public function cancelSellOrder(int $orderId): Order
    {
        $order = $this->orderRepository->find($orderId);
        $userId = $order->user_id;
        $tradeSymbol = $order->symbol->value;
        $amount = $order->amount;

        // unlock locked Asset
        $this->assetRepository->unlock($userId, $tradeSymbol, $amount);

        // update Order status
        return $this->orderRepository->update($orderId, [
            'status' => OrderStatus::CANCELLED->value
        ]);
    }

    public function fillSellOrder(int $orderId, float $buyAmount): Order|Collection
    {
        $order = $this->orderRepository->find($orderId);
        $userId = $order->user_id;
        $tradeSymbol = $order->symbol->value;

        if ($order->amount <= $buyAmount) {

            // update OrderStatus
            return $this->orderRepository->update($orderId, [
                'status' => OrderStatus::FILLED->value
            ]);

        // partial fill
        } else {

            // create a filled Order
            $filledOrder = $this->orderRepository->create([
                'user_id' => $userId,
                'symbol' => $order->symbol,
                'price' => $order->price,
                'amount' => $buyAmount,
                'status' => OrderStatus::FILLED->value
            ]);

            // update unfullfilled Order amount
            $incompleteOrder = $this->orderRepository->update($orderId, [
                'amount' => $order->amount - $buyAmount
            ]);

            return collect([$filledOrder, $incompleteOrder]);
        }
    }
}