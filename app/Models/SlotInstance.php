<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlotInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'resource_id',
        'availability_block_id',
        'starts_at',
        'ends_at',
        'capacity',
        'booked_count',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    public function availabilityBlock()
    {
        return $this->belongsTo(AvailabilityBlock::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function remainingCapacity(): int
    {
        return max(0, $this->capacity - $this->booked_count);
    }
}
