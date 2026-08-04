<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CURRENT_STATUSES = ['pending', 'paid', 'shipped', 'completed', 'cancelled'];

    private const PREVIOUS_STATUSES = ['pending', 'paid', 'shipped', 'cancelled'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->setAllowedStatuses(self::CURRENT_STATUSES);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('orders')->where('status', 'completed')->exists()) {
            throw new RuntimeException(
                'Cannot remove the completed order status while completed orders exist.'
            );
        }

        $this->setAllowedStatuses(self::PREVIOUS_STATUSES);
    }

    /**
     * @param  list<string>  $statuses
     */
    private function setAllowedStatuses(array $statuses): void
    {
        $sqlList = "'".implode("','", $statuses)."'";

        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM($sqlList) NOT NULL DEFAULT 'pending'");
    }
};
