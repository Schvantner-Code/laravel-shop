<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_product', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });

        Schema::table('order_product', function (Blueprint $table) {
            $table->index('order_id');
            $table->unique(['order_id', 'product_id']);
        });

        Schema::table('order_product', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_product', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });

        Schema::table('order_product', function (Blueprint $table) {
            $table->dropUnique(['order_id', 'product_id']);
            $table->dropIndex(['order_id']);
        });

        Schema::table('order_product', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders');
        });
    }
};
