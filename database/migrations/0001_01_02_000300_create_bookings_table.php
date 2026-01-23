<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->foreignId('slot_instance_id')->constrained()->cascadeOnDelete();
            $table->string('status', 16)->default('confirmed');
            $table->string('idempotency_key', 64)->unique();
            $table->unsignedSmallInteger('total_guests')->default(1);
            $table->timestamp('booked_at');
            $table->timestamps();

            $table->index(['resource_id', 'booked_at']);
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE bookings ADD CONSTRAINT chk_booking_guests CHECK (total_guests > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
