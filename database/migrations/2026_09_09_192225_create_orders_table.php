<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->foreignId('address_id')->nullable()->constrained()->nullOnDelete();

            $table->json('delivery_address');
            $table->string('status', 30)->default('pending')->index();
            $table->string('payment_method', 30);

            $table->decimal('subtotal', 12, 2);
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['store_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
