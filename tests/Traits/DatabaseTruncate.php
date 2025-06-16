<?php

declare(strict_types=1);

namespace Tests\Traits;

use Illuminate\Support\Facades\DB;

trait DatabaseTruncate
{
    /**
     * Truncate specified tables
     */
    public function truncateTables(array $tables): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Clean up specific tables after each test
     */
    public function cleanupTables(array $tables): void
    {
        foreach ($tables as $table) {
            DB::table($table)->delete();
        }
    }

    /**
     * Reset auto increment for tables
     */
    public function resetAutoIncrement(array $tables): void
    {
        foreach ($tables as $table) {
            DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = 1;");
        }
    }

    /**
     * Get count of records in table
     */
    public function getTableCount(string $table): int
    {
        return DB::table($table)->count();
    }

    /**
     * Ensure tables are empty
     */
    public function ensureTablesEmpty(array $tables): void
    {
        foreach ($tables as $table) {
            expect($this->getTableCount($table))->toBe(0, "Table {$table} should be empty");
        }
    }
}
