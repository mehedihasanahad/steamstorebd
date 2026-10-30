<?php

namespace App\Models;

use App\Jobs\SendEmailVerification;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
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

    /**
     * Send the confirm-your-address message through the store's own template.
     *
     * Laravel's default builds a MailMessage from its markdown components and
     * sends it inline. This one is a queued job on the house template, so the
     * shopper is not left waiting on an SMTP handshake at the one moment they
     * are deciding whether this shop works. SendEmailVerification builds the
     * signed link, so `verification.verify` is unchanged.
     */
    public function sendEmailVerificationNotification(): void
    {
        SendEmailVerification::dispatch($this);
    }

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
