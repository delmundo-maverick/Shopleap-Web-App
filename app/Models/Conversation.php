<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['subject', 'product_id', 'order_id', 'created_by', 'last_message_at', 'status', 'assigned_to'];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    public function assignedAgent()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot('last_read_at', 'hidden_at')
            ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->whereHas('participants', fn($q) => $q->where('users.id', $userId));
    }

    public function otherParticipantFor(int $userId): ?User
    {
        return $this->participants->firstWhere('id', '!=', $userId);
    }

    public function unreadCountFor(int $userId): int
    {
        $participant = $this->participants->firstWhere('id', $userId);
        $lastRead = $participant?->pivot?->last_read_at;

        return $this->messages
            ->where('sender_id', '!=', $userId)
            ->when($lastRead, fn($messages) => $messages->where('created_at', '>', $lastRead))
            ->count();
    }
}
