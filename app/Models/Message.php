<?php

namespace App\Models;

use App\Services\RiderRealtime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Message extends Model
{
    protected $fillable = ['conversation_id', 'sender_id', 'body', 'forwarded_from_message_id'];

    protected static function booted(): void
    {
        static::created(function (Message $message): void {
            $conversationId = $message->conversation_id;

            DB::afterCommit(function () use ($conversationId): void {
                $userIds = DB::table('conversation_participants')->where('conversation_id', $conversationId)->pluck('user_id');
                RiderRealtime::notifyMany(CourierProfile::whereIn('user_id', $userIds)->pluck('id'));
            });
        });
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function originalMessage()
    {
        return $this->belongsTo(Message::class, 'forwarded_from_message_id');
    }
}
