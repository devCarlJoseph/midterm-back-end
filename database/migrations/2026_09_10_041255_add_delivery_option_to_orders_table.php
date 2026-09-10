<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('delivery_option_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('delivery_option_name', 30)->nullable();
            $table->unsignedSmallInteger('estimated_delivery_minutes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('delivery_option_id');
            $table->dropColumn([
                'delivery_option_name',
                'estimated_delivery_minutes',
            ]);
        });
    }
};