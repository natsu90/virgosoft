<?php

namespace App\Listeners;

use App\Events\UserCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Contracts\AssetRepositoryInterface;
use App\Enums\TradeSymbol;

class CreateAssets
{
    /**
     * Create the event listener.
     */
    public function __construct(
        public AssetRepositoryInterface $assetRepository
    ) {}

    /**
     * Handle the event.
     */
    public function handle(UserCreated $event): void
    {
        $userId = $event->user->getKey();
        foreach (TradeSymbol::cases() as $symbol) 
        {
            $this->assetRepository->get($userId, $symbol->value);
        }
    }
}
