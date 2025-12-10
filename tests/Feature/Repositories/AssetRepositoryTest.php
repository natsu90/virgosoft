<?php

namespace Tests\Feature\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Contracts\AssetRepositoryInterface;
use App\Models\User;
use App\Models\Asset;
use App\Events\UserUpdated;
use Illuminate\Support\Facades\Event;
use App\Enums\TradeSymbol;

class AssetRepositoryTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * @var User
     */
    private $testUser;

    /**
     * @var int
     */
    private $testUserId;

    /**
     * @var string
     */
    private $testSymbol;

    public function setUp(): void
    {
        parent::setUp();

        $this->repo = $this->app->make(AssetRepositoryInterface::class);
        $this->testUser = User::factory()->create();
        $this->testUserId = $this->testUser->getKey();
        $this->testSymbol = TradeSymbol::BTC->value;

        Event::fake(UserUpdated::class);
    }

    public function testGetNew()
    {
        $asset = $this->repo->get($this->testUserId, $this->testSymbol);

        $this->assertInstanceOf(Asset::class, $asset);
        $this->assertDatabaseHas(Asset::getTableName(), [
            'user_id' => $this->testUserId,
            'symbol' => $this->testSymbol,
            'amount' => 0,
            'locked_amount' => 0
        ]);
    }

    public function testGetExisting()
    {
        $expectedAsset = Asset::factory()->create([
            'user_id' => $this->testUserId,
            'symbol' => $this->testSymbol
        ]);

         $asset = $this->repo->get($this->testUserId, $this->testSymbol);

         $this->assertInstanceOf(Asset::class, $asset);
         $this->assertEquals($asset->getKey(), $expectedAsset->getKey());
    }

    public function testBoughtNew()
    {
        $amount = 1.23;
        $asset = $this->repo->bought($this->testUserId, $this->testSymbol, $amount);

        $this->assertInstanceOf(Asset::class, $asset);
        $this->assertDatabaseHas(Asset::getTableName(), [
            'user_id' => $this->testUserId,
            'symbol' => $this->testSymbol,
            'amount' => $amount,
        ]);

        $user = $this->testUser;
        Event::assertDispatched(UserUpdated::class, function($event) use ($user, $asset) {
            return $event->user->getKey() === $user->getKey()
                && $event->user->assets->first()->getKey() === $asset->getKey();
        });
    }

    public function testBoughtExisting()
    {
        $amount = 1.23;
        $existingAmount = 100;
        $expectedNewAmount = 101.23;
        $existingAsset = Asset::factory()->create([
            'user_id' => $this->testUserId,
            'symbol' => $this->testSymbol,
            'amount' => $existingAmount,
        ]);

        $asset = $this->repo->bought($this->testUserId, $this->testSymbol, $amount);

        $this->assertInstanceOf(Asset::class, $asset);
        $this->assertDatabaseHas(Asset::getTableName(), [
            'user_id' => $this->testUserId,
            'symbol' => $this->testSymbol,
            'amount' => $expectedNewAmount,
        ]);

        $user = $this->testUser;
        Event::assertDispatched(UserUpdated::class, function($event) use ($user) {
            return $event->user->getKey() === $user->getKey();
        });
    }

    public function testLock()
    {
        $existingAmount = 100;
        $existingLockedAmount = 10;
        $lockingAmount = 10;
        $expectedNewAmount = 90;
        $expectedNewLockedAmount = 20;

        $asset = Asset::factory()->create([
            'user_id' => $this->testUserId,
            'symbol' => $this->testSymbol,
            'amount' => $existingAmount,
            'locked_amount' => $existingLockedAmount
        ]);

        $this->repo->lock($this->testUserId, $this->testSymbol, $lockingAmount);

        $this->assertDatabaseHas(Asset::getTableName(), [
            'user_id' => $this->testUserId,
            'symbol' => $this->testSymbol,
            'amount' => $expectedNewAmount,
            'locked_amount' => $expectedNewLockedAmount
        ]);
        
        $user = $this->testUser;
        Event::assertDispatched(UserUpdated::class, function($event) use ($user) {
            return $event->user->getKey() === $user->getKey();
        });
    }

    public function testUnlock()
    {
        $existingAmount = 100;
        $existingLockedAmount = 20;
        $unlockingAmount = 10;
        $expectedNewAmount = 110;
        $expectedNewLockedAmount = 10;

        $asset = Asset::factory()->create([
            'user_id' => $this->testUserId,
            'symbol' => $this->testSymbol,
            'amount' => $existingAmount,
            'locked_amount' => $existingLockedAmount
        ]);

        $this->repo->unLock($this->testUserId, $this->testSymbol, $unlockingAmount);

        $this->assertDatabaseHas(Asset::getTableName(), [
            'user_id' => $this->testUserId,
            'symbol' => $this->testSymbol,
            'amount' => $expectedNewAmount,
            'locked_amount' => $expectedNewLockedAmount
        ]);
        
        $user = $this->testUser;
        Event::assertDispatched(UserUpdated::class, function($event) use ($user) {
            return $event->user->getKey() === $user->getKey();
        });
    }

    public function testSold()
    {
        $existingAmount = 100;
        $existingLockedAmount = 20;
        $soldAmount = 10;
        $expectedNewLockedAmount = 10;

        $asset = Asset::factory()->create([
            'user_id' => $this->testUserId,
            'symbol' => $this->testSymbol,
            'amount' => $existingAmount,
            'locked_amount' => $existingLockedAmount
        ]);

        $this->repo->sold($this->testUserId, $this->testSymbol, $soldAmount);

        $this->assertDatabaseHas(Asset::getTableName(), [
            'user_id' => $this->testUserId,
            'symbol' => $this->testSymbol,
            'amount' => $existingAmount,
            'locked_amount' => $expectedNewLockedAmount
        ]);
        
        $user = $this->testUser;
        Event::assertDispatched(UserUpdated::class, function($event) use ($user) {
            return $event->user->getKey() === $user->getKey();
        });
    }
}
