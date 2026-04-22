<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->decimal('latitude', 9, 6)->nullable()->after('briefing_timezone');
            $table->decimal('longitude', 9, 6)->nullable()->after('latitude');
            $table->string('location_name')->nullable()->after('longitude');
            $table->string('location_source', 20)->nullable()->after('location_name');
            $table->timestamp('location_updated_at')->nullable()->after('location_source');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'latitude',
                'longitude',
                'location_name',
                'location_source',
                'location_updated_at',
            ]);
        });
    }
};
