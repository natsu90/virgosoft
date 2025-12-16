<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'balance',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'balance' => 0
    ];

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class)->orderBy('updated_at', 'desc');
    }

    public function getTradesAttribute()
    {
        return Trade::select('trades.*')
            ->selectRaw("(CASE WHEN buy.user_id='". $this->id ."' THEN buy.side ELSE sell.side END) AS side")
            ->selectRaw('ROUND(trades.price * trades.amount + commission, 4) AS sales')
            ->leftJoin('orders as buy', 'buy.id', '=', 'trades.buy_order_id')
            ->leftJoin('orders as sell', 'sell.id', '=', 'trades.sell_order_id')
            ->where('buy.user_id', $this->id)
            ->Orwhere('sell.user_id', $this->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
