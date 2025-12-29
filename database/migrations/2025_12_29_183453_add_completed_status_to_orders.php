<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // This is the best way to modify ENUM in MySQL
        $values = array_column(OrderStatus::cases(), 'value');
        $sqlList = "'".implode("','", $values)."'";
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM($sqlList) NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to revert ENUM changes
    }
};
