<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'email', 'password', 'role', 'account_status', 'email_verified_at', 'avatar_path'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    use HasApiTokens;

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

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path
            ? Storage::disk('r2_public')->url($this->avatar_path)
            : null;
    }

    public function logisticsProfile()
    {
        return $this->hasOne(LogisticsProfile::class);
    }

    public function sellerProfile()
    {
        return $this->hasOne(SellerProfile::class);
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
