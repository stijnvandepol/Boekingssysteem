<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'resource_id',
        'slot_instance_id',
        'status',
        'idempotency_key',
        'total_guests',
        'booked_at',
        'duration_minutes',
    ];

    protected function casts(): array
    {
        return [
            'booked_at' => 'datetime',
            'duration_minutes' => 'integer',
        ];
    }

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    public function slotInstance()
    {
        return $this->belongsTo(SlotInstance::class);
    }

    public function guests()
    {
        return $this->hasMany(BookingGuest::class);
    }
}
