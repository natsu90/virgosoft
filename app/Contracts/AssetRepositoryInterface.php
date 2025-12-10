<?php

namespace App\Contracts;

use App\Models\Asset;

interface AssetRepositoryInterface
{
    /**
     * Create or Update amount of Asset after fullfilled Buy Order
     */
    public function bought(int $userId, string $symbol, float $amount): Asset;

    /**
     * Lock given amount of Asset when creating a Sell Order
     */
    public function lock(int $userId, string $symbol, float $amount): Asset;

    /**
     * Unlock given amount of Asset after cancelled Buy Order
     */
    public function unlock(int $userId, string $symbol, float $amount): Asset;

    /**
     * Deduct given amount of locked Asset after fullfilled a Sell Order
     */
    public function sold(int $userId, string $symbol, float $amount): Asset;

    /**
     * Get Asset record by User ID and symbol
     */
    public function get(int $userId, string $symbol): Asset;
}