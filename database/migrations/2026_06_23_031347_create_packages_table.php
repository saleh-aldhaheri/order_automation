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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();

            $table->string('external_package_id');
            $table->string('external_order_id');  //denormalization for faster query with webhooks
            $table->foreignId('order_id')
                ->constrained();
            $table->string('shop_type');
            $table->string('external_package_status');
            $table->string('package_status')
                ->default('pending');
            $table->json('details')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'external_package_id']);
            $table->index(['external_order_id', 'external_package_id']);
            $table->index('package_status');
            $table->index('external_package_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
