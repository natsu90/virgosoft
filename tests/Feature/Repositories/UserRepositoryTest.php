<?php

namespace Tests\Feature\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Contracts\UserRepositoryInterface;
use App\Models\User;
use App\Events\UserUpdated;
use App\Events\UserCreated;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Event;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function setUp(): void
    {
        parent::setUp();

        $this->repo = $this->app->make(UserRepositoryInterface::class);

        Event::fake([
            UserUpdated::class,
            UserCreated::class
        ]);
    }

    public function testCreate()
    {
        $name = fake()->name();
        $email = fake()->unique()->safeEmail();
        $password = fake()->md5();
        $user = $this->repo->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password)
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas((new User)->getTable(), [
            'name' => $name,
            'email' => $email
        ]);
    }

    public function testFind()
    {
        $user = User::factory()->create();

        $fetchedUser = $this->repo->find($user->getKey());

        $this->assertInstanceOf(User::class, $fetchedUser);
        $this->assertEquals($fetchedUser->getKey(), $user->getKey());
    }

    public function testDeductBalance()
    {
        $user = User::factory()->create([
            'balance' => 100
        ]);
        $cost = 10;
        $expectedNewBalance = 90;

        $updatedUser = $this->repo->deductBalance($user->getKey(), $cost);

        $this->assertInstanceOf(User::class, $updatedUser);
        $this->assertEquals($updatedUser->getKey(), $user->getKey());
        $this->assertDatabaseHas((new User)->getTable(), [
            'email' => $user->email,
            'balance' => $expectedNewBalance
        ]);

        Event::assertDispatched(UserUpdated::class, function($event) use ($updatedUser, $expectedNewBalance) {
            return $event->user->getKey() === $updatedUser->getKey()
                && $event->user->balance == $expectedNewBalance;
        });
    }

    public function testTopupBalance()
    {
        $user = User::factory()->create([
            'balance' => 100
        ]);
        $amount = 20;
        $expectedNewBalance = 120;

        $updatedUser = $this->repo->topupBalance($user->getKey(), $amount);

        $this->assertInstanceOf(User::class, $updatedUser);
        $this->assertEquals($updatedUser->getKey(), $user->getKey());
        $this->assertDatabaseHas((new User)->getTable(), [
            'email' => $user->email,
            'balance' => $expectedNewBalance
        ]);

        Event::assertDispatched(UserUpdated::class, function($event) use ($updatedUser, $expectedNewBalance) {
            return $event->user->getKey() === $updatedUser->getKey()
                && $event->user->balance == $expectedNewBalance;
        });
    }
}