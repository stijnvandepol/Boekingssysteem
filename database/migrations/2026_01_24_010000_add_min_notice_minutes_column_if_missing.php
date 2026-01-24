<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('resources', 'min_notice_minutes')) {
            Schema::table('resources', function (Blueprint $table) {
                $table->unsignedSmallInteger('min_notice_minutes')->default(0)->after('default_capacity');
            });
        }
    }

    public function down(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            if (Schema::hasColumn('resources', 'min_notice_minutes')) {
                $table->dropColumn('min_notice_minutes');
            }
        });
    }
};
