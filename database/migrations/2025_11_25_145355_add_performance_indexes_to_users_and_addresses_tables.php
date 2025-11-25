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
        Schema::table('users', function (Blueprint $table) {
            // Index for ordering by created_at (improves ORDER BY performance)
            try {
                $table->index('created_at', 'users_created_at_index');
            } catch (\Exception $e) {
                // Index might already exist, skip
            }
            
            // Composite index for common search patterns (first_name + last_name)
            // This helps with searches that match both first and last name
            try {
                $table->index(['first_name', 'last_name'], 'users_name_search_index');
            } catch (\Exception $e) {
                // Index might already exist, skip
            }
        });

        Schema::table('addresses', function (Blueprint $table) {
            // Index for post_code search
            try {
                $table->index('post_code', 'addresses_post_code_index');
            } catch (\Exception $e) {
                // Index might already exist, skip
            }
            
            // Composite index for city + country searches
            try {
                $table->index(['city', 'country'], 'addresses_location_index');
            } catch (\Exception $e) {
                // Index might already exist, skip
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            try {
                $table->dropIndex('users_created_at_index');
            } catch (\Exception $e) {
                // Index might not exist, skip
            }
            
            try {
                $table->dropIndex('users_name_search_index');
            } catch (\Exception $e) {
                // Index might not exist, skip
            }
        });

        Schema::table('addresses', function (Blueprint $table) {
            try {
                $table->dropIndex('addresses_post_code_index');
            } catch (\Exception $e) {
                // Index might not exist, skip
            }
            
            try {
                $table->dropIndex('addresses_location_index');
            } catch (\Exception $e) {
                // Index might not exist, skip
            }
        });
    }
};
