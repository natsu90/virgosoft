<?php

namespace App\Repositories;

use App\Contracts\UserRepositoryInterface;
use App\Models\User;

class UserRepository implements UserRepositoryInterface
{
    public function create(array $params): User
    {
        return User::create($params);
    }

    public function find(int $id): User
    {
        return User::findOrFail($id);
    }

    public function deductBalance(int $userId, float $amount): User
    {
        $user = $this->find($userId);
        $user->decrement('balance', $amount);
        $user->refresh();

        return $user;
    }

    public function topupBalance(int $userId, float $amount): User
    {
        $user = $this->find($userId);
        $user->increment('balance', $amount);
        $user->refresh();

        return $user;
    }
}