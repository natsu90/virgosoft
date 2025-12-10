<?php

namespace App\Observers;

use App\Models\Trade;
use App\Events\TradeCreated;

class TradeObserver
{
    /**
     * Handle the Trade "created" event.
     */
    public function created(Trade $trade): void
    {
        TradeCreated::dispatch($trade);
    }

    /**
     * Handle the Trade "updated" event.
     */
    public function updated(Trade $trade): void
    {
        //
    }

    /**
     * Handle the Trade "deleted" event.
     */
    public function deleted(Trade $trade): void
    {
        //
    }

    /**
     * Handle the Trade "restored" event.
     */
    public function restored(Trade $trade): void
    {
        //
    }

    /**
     * Handle the Trade "force deleted" event.
     */
    public function forceDeleted(Trade $trade): void
    {
        //
    }
}
