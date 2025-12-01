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
        Schema::table('delivery_partners', function (Blueprint $table) {
            // Drop the old bigint column
            $table->dropColumn('current_order_id');
        });

        Schema::table('delivery_partners', function (Blueprint $table) {
            // Add as UUID to match orders table
            $table->uuid('current_order_id')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_partners', function (Blueprint $table) {
            $table->dropColumn('current_order_id');
        });

        Schema::table('delivery_partners', function (Blueprint $table) {
            $table->unsignedBigInteger('current_order_id')->nullable()->after('status');
        });
    }
};
