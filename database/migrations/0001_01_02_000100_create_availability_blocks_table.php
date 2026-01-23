<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availability_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedSmallInteger('slot_length_minutes');
            $table->unsignedSmallInteger('capacity');
            $table->timestamps();

            $table->index(['resource_id', 'starts_at']);
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE availability_blocks ADD CONSTRAINT chk_availability_ends CHECK (ends_at > starts_at)');
            DB::statement('ALTER TABLE availability_blocks ADD CONSTRAINT chk_availability_slot_length CHECK (slot_length_minutes > 0)');
            DB::statement('ALTER TABLE availability_blocks ADD CONSTRAINT chk_availability_capacity CHECK (capacity > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_blocks');
    }
};
