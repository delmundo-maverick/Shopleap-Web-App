<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'last_name',
        'first_name',
        'middle_initial',
        'sex',
        'contact_no',
        'birthday',
        'age',
        'province',
        'municipality',
        'barangay',
        'street_address',
        'business_name',
        'line_of_business',
        'shop_description',
        'id_upload_path',
        'business_permit_path',
        'status',
        'rejection_reason',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getShopNameAttribute(): string
    {
        return $this->business_name ?: trim("{$this->first_name} {$this->last_name}");
    }

    public function getLocationAttribute(): string
    {
        return trim("{$this->municipality}, {$this->province}", ', ');
    }
}
