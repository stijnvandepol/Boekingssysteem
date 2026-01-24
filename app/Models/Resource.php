<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'timezone',
        'default_slot_length_minutes',
        'default_capacity',
        'min_notice_minutes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function availabilityBlocks()
    {
        return $this->hasMany(AvailabilityBlock::class);
    }

    public function slotInstances()
    {
        return $this->hasMany(SlotInstance::class);
    }
}
