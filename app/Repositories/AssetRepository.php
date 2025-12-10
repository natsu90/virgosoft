<?php

namespace App\Repositories;

use App\Contracts\AssetRepositoryInterface;
use App\Models\Asset;

class AssetRepository implements AssetRepositoryInterface
{
    public function get(int $userId, string $symbol): Asset
    {
        $asset = Asset::firstOrCreate(
            [
                'user_id' => $userId,
                'symbol' => $symbol
            ],
            [
                'amount' => 0,
                'locked_amount' => 0
            ]
        );

        return $asset;
    }

    public function bought(int $userId, string $symbol, float $amount): Asset
    {
        $asset = $this->get($userId, $symbol);

        $asset->increment('amount', $amount);
        $asset->refresh();

        return $asset;
    }

    public function lock(int $userId, string $symbol, float $amount): Asset
    {
        $asset = Asset::where([
            'user_id' => $userId,
            'symbol' => $symbol
        ])->firstOrFail();

        $asset->decrement('amount', $amount);
        $asset->increment('locked_amount', $amount);
        $asset->refresh();

        return $asset;
    }

    public function unlock(int $userId, string $symbol, float $amount): Asset
    {
        $asset = Asset::where([
            'user_id' => $userId,
            'symbol' => $symbol
        ])->firstOrFail();

        $asset->increment('amount', $amount);
        $asset->decrement('locked_amount', $amount);
        $asset->refresh();

        return $asset;
    }

    public function sold(int $userId, string $symbol, float $amount): Asset
    {
        $asset = Asset::where([
            'user_id' => $userId,
            'symbol' => $symbol
        ])->firstOrFail();
        
        $asset->decrement('locked_amount', $amount);
        $asset->refresh();

        return $asset;
    }
}