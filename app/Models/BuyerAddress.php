<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BuyerAddress extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['label', 'recipient_name', 'phone', 'province', 'city_municipality', 'barangay', 'street_address', 'postal_code', 'notes', 'is_default'];

    protected $attributes = ['is_default' => false];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string|null> */
    public function snapshot(): array
    {
        return ['name' => $this->recipient_name, 'contact_no' => $this->phone,
            'province' => $this->province, 'municipality' => $this->city_municipality,
            'barangay' => $this->barangay, 'street' => $this->street_address,
            'postal_code' => $this->postal_code, 'notes' => $this->notes, 'label' => $this->label];
    }
}
