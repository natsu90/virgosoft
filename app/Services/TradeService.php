<?php

namespace App\Services;

use App\Contracts\TradeServiceInterface;
use App\Contracts\OrderServiceInterface;
use App\Contracts\OrderRepositoryInterface;
use App\Contracts\TradeRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Contracts\AssetRepositoryInterface;
use App\Models\Order;
use App\Models\Trade;
use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Events\OrderMatched;

class TradeService implements TradeServiceInterface
{
    public function __construct(
        protected OrderRepositoryInterface $orderRepository,
        protected OrderServiceInterface $orderService,
        protected TradeRepositoryInterface $tradeRepository,
        protected UserRepositoryInterface $userRepository,
        protected AssetRepositoryInterface $assetRepository
    ) {}

    public function findSellOrderMatch(Order $sellOrder): void
    {
        while ($sellOrder->status == OrderStatus::OPEN)
        {
            $matchedBuyOrder = $this->orderRepository->findBuyOrder($sellOrder->symbol->value, $sellOrder->price);

            if (empty($matchedBuyOrder)) break;

            // OrderMatched event
            OrderMatched::dispatch($sellOrder, $matchedBuyOrder);
            
            // fill Buy Order
            $buyResult = $this->orderService->fillBuyOrder($matchedBuyOrder->getKey(), $sellOrder->amount);
            $filledBuyOrder = $buyResult instanceof Order ? $buyResult : $buyResult->first();

            // fill Sell Order
            $sellResult = $this->orderService->fillSellOrder($sellOrder->getKey(), $matchedBuyOrder->amount);
            $filledSellOrder = $sellResult instanceof Order ? $sellResult : $sellResult->first();

            // create Trade
            $this->create($filledSellOrder, $filledBuyOrder);
            
            // re-assign $sellOrder
            $sellOrder = $sellResult instanceof Order ? $sellResult : $sellResult->last();
        }
    }

    public function findBuyOrderMatch(Order $buyOrder): void
    {
        while ($buyOrder->status == OrderStatus::OPEN)
        {
            $matchedSellOrder = $this->orderRepository->findSellOrder($buyOrder->symbol->value, $buyOrder->price);

            if (empty($matchedSellOrder)) break;

            // OrderMatched event
            OrderMatched::dispatch($matchedSellOrder, $buyOrder);
            
            // fill Sell Order
            $sellResult = $this->orderService->fillSellOrder($matchedSellOrder->getKey(), $buyOrder->amount);
            $filledSellOrder = $sellResult instanceof Order ? $sellResult : $sellResult->first();
            
            // fill Buy Order
            $buyResult = $this->orderService->fillBuyOrder($buyOrder->getKey(), $matchedSellOrder->amount);
            $filledBuyOrder = $buyResult instanceof Order ? $buyResult : $buyResult->first();

            // create Trade
            $this->create($filledSellOrder, $filledBuyOrder);

            // re-assign $buyOrder
            $buyOrder = $buyResult instanceof Order ? $buyResult : $buyResult->last();
        }
    }

    public function create(Order $sellOrder, Order $buyOrder): Trade
    {
        $salesTotal = $buyOrder->amount * $buyOrder->price;
        $commission = $salesTotal * self::COMMISSION_PERCENTAGE;
        $netSalesTotal = $salesTotal - $commission;

        // update balance of Seller
        $this->userRepository->topupBalance($sellOrder->user_id, $netSalesTotal);

        // deduct locked Asset from Seller
        $this->assetRepository->sold($sellOrder->user_id, $buyOrder->symbol->value, $buyOrder->amount);

        // add Asset to Buyer
        $this->assetRepository->bought($buyOrder->user_id, $buyOrder->symbol->value, $buyOrder->amount);

        return $this->tradeRepository->create([
            'sell_order_id' => $sellOrder->getKey(),
            'buy_order_id' => $buyOrder->getKey(),
            'symbol' => $buyOrder->symbol->value,
            'price' => $buyOrder->price,
            'amount' => $buyOrder->amount,
            'commission' => $commission
        ]);
    }
}