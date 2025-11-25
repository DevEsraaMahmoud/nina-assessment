<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds FULLTEXT index for optimized text search on first_name, last_name, and email.
     * This enables MATCH ... AGAINST queries which are significantly faster than LIKE queries
     * on large datasets (1M+ records).
     */
    public function up(): void
    {
        // Use raw SQL since Laravel Schema doesn't support FULLTEXT directly
        // MySQL 8 compatible index name
        DB::statement('ALTER TABLE users ADD FULLTEXT INDEX users_fulltext_search_idx (first_name, last_name, email)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP INDEX users_fulltext_search_idx');
    }
};
