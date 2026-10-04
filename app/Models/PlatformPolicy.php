<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformPolicy extends Model
{
    protected $fillable = ['slug', 'title', 'body', 'version', 'updated_by'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
