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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('external_order_id');
            $table->foreignId('shop_id')
                ->constrained();
            $table->string('shop_type');
            $table->string('shop_status');
            $table->boolean('is_processed')
                ->default(false);
            $table->json('details')->nullable();
            $table->timestamps();
            $table->unique([
                'shop_id',
                'external_order_id',
            ]);
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
