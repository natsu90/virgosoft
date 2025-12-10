<?php

namespace App\Contracts;

use App\Models\User;

interface UserRepositoryInterface
{
    /**
     * Create a User
     */
    public function create(array $params): User;

    /**
     * Find a User
     */
    public function find(int $id): User;

    /**
     * Deduct balance
     */
    public function deductBalance(int $userId, float $amount): User;

    /**
     * Topup balance
     */
    public function topupBalance(int $userId, float $amount): User;
}