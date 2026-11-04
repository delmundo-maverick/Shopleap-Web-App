<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'account_status',
    'email_verified_at',
    'avatar_path',
    'terms_version',
    'terms_accepted_at',
    'privacy_version',
    'privacy_accepted_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'privacy_accepted_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('r2_public');

        return $disk->url($this->avatar_path);
    }

    public function logisticsProfile()
    {
        return $this->hasOne(LogisticsProfile::class);
    }

    public function sellerProfile()
    {
        return $this->hasOne(SellerProfile::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(BuyerAddress::class);
    }

    public function defaultAddress(): HasOne
    {
        return $this->hasOne(BuyerAddress::class)->where('is_default', true);
    }

    public function buyerProfile()
    {
        return $this->hasOne(BuyerProfile::class);
    }

    public function courierProfile()
    {
        return $this->hasOne(CourierProfile::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function wishlistItems()
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    /** Sellers this buyer follows. */
    public function followedSellers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'seller_followers', 'buyer_id', 'seller_id')
            ->withTimestamps();
    }

    /** Buyers following this seller. */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'seller_followers', 'seller_id', 'buyer_id')
            ->withTimestamps();
    }

    public function isFollowing(User $seller): bool
    {
        return $this->followedSellers()->where('users.id', $seller->id)->exists();
    }

    public function isEligibleSeller(): bool
    {
        return $this->role === 'seller'
            && $this->account_status === 'active'
            && $this->sellerProfile?->status === 'approved';
    }

    /** Sellers buyers may visit or follow: active accounts with approved profiles. */
    public function scopeEligibleSellers(Builder $query): Builder
    {
        return $query->where('role', 'seller')
            ->where('account_status', 'active')
            ->whereHas('sellerProfile', fn (Builder $profileQuery) => $profileQuery->where('status', 'approved'));
    }

    public function shopVouchers()
    {
        return $this->hasMany(ShopVoucher::class, 'seller_id');
    }

    public function voucherRedemptions()
    {
        return $this->hasMany(ShopVoucherRedemption::class, 'buyer_id');
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot('last_read_at', 'hidden_at')
            ->withTimestamps();
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function getMessagingNameAttribute(): string
    {
        return match ($this->role) {
            'seller' => $this->sellerProfile?->business_name ?? $this->name,
            'logistics' => $this->logisticsProfile?->business_name ?? $this->name,
            'courier' => $this->courierProfile?->full_name ?? $this->name,
            'super_admin' => 'Shopleap Admin',
            default => $this->name,
        };
    }
}
