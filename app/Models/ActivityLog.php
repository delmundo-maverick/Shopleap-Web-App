<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'actor_id',
        'actor_name',
        'actor_role',
        'action',
        'category',
        'description',
        'subject_type',
        'subject_id',
        'properties',
    ];

    protected function casts(): array
    {
        return ['properties' => 'array'];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject()
    {
        return $this->morphTo();
    }
}
