<?php

use App\Enums\OrderStatusEnum;
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
            $table->foreignId('shop_id')->constrained();
            $table->string('external_order_id');
            $table->string('external_shop_id'); // denormalization for faster query with webhooks
            $table->string('shop_type');
            $table->string('external_order_status');
            $table->string('order_status')
                ->default(OrderStatusEnum::UNPROCESSED->value);
            $table->json('details')->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'external_order_id']);
            $table->index(['external_shop_id', 'external_order_id']);
            $table->index('order_status');
            $table->index('external_order_status');
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
