<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slot_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->foreignId('availability_block_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedSmallInteger('capacity');
            $table->unsignedSmallInteger('booked_count')->default(0);
            $table->string('status', 16)->default('open');
            $table->timestamps();

            $table->unique(['resource_id', 'starts_at']);
            $table->index(['resource_id', 'starts_at', 'status']);
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE slot_instances ADD CONSTRAINT chk_slot_ends CHECK (ends_at > starts_at)');
            DB::statement('ALTER TABLE slot_instances ADD CONSTRAINT chk_slot_capacity CHECK (capacity > 0)');
            DB::statement('ALTER TABLE slot_instances ADD CONSTRAINT chk_slot_booked_nonneg CHECK (booked_count >= 0)');
            DB::statement('ALTER TABLE slot_instances ADD CONSTRAINT chk_slot_booked_le_capacity CHECK (booked_count <= capacity)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('slot_instances');
    }
};
