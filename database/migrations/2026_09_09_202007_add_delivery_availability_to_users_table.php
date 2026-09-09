<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_available_for_delivery')
                ->default(false)
                ->after('role');

            $table->decimal('latitude', 10, 7)
                ->nullable()
                ->after('is_available_for_delivery');

            $table->decimal('longitude', 10, 7)
                ->nullable()
                ->after('latitude');

            $table->index('is_available_for_delivery');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['is_available_for_delivery']);
            $table->dropColumn([
                'is_available_for_delivery',
                'latitude',
                'longitude',
            ]);
        });
    }
};
