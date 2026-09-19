<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_admin',
        'google_id',
        'avatar',
        'referral_code',
        'wallet_balance',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_admin'          => 'boolean',
            'wallet_balance'    => 'decimal:2',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function addedGiftCardCodes(): HasMany
    {
        return $this->hasMany(GiftCardCode::class, 'added_by_admin_id');
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->latest();
    }

    /** Products this shopper saved for later. */
    public function favourites(): HasMany
    {
        return $this->hasMany(Favourite::class);
    }

    public function hasFavourited(int $giftCardCategoryId): bool
    {
        return $this->favourites()->where('gift_card_category_id', $giftCardCategoryId)->exists();
    }

    public function referralUsages(): HasMany
    {
        return $this->hasMany(ReferralUsage::class, 'referrer_id')->latest();
    }
}
