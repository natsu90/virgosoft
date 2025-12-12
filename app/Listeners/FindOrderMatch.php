<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Contracts\TradeServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use App\Enums\OrderStatus;
use App\Enums\OrderSide;

class FindOrderMatch implements ShouldQueueAfterCommit
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected TradeServiceInterface $tradeService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        if ($event->order->status != OrderStatus::OPEN) return;

        if ($event->order->side == OrderSide::BUY) {
            $this->tradeService->findBuyOrderMatch($event->order);
        } else {
            $this->tradeService->findSellOrderMatch($event->order);
        }
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping(1)];
    }
}
