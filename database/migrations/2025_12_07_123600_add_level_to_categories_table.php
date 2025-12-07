<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedTinyInteger('level')->default(0)->after('parent_id');
            $table->index('level');
        });

        // Update existing categories with correct levels
        // Level 0 = top-level (parent_id is null)
        // Level 1 = sub-category (parent has no parent)
        // Level 2 = sub-sub-category (parent's parent exists)
        DB::statement("
            UPDATE categories c
            SET level = CASE
                WHEN c.parent_id IS NULL THEN 0
                WHEN (SELECT parent_id FROM categories p WHERE p.id = c.parent_id) IS NULL THEN 1
                ELSE 2
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['level']);
            $table->dropColumn('level');
        });
    }
};
