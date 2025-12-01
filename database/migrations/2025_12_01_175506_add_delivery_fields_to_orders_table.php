<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update the status enum to include 'cancelled'
        DB::statement("ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status IN ('placed', 'accepted', 'out_for_delivery', 'delivered', 'cancelled'))");

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('delivery_partner_id')->nullable()->after('user_id');
            $table->unsignedBigInteger('address_id')->nullable()->after('delivery_partner_id');
            $table->text('notes')->nullable()->after('delivery_address');

            $table->foreign('delivery_partner_id')
                ->references('id')
                ->on('delivery_partners')
                ->nullOnDelete();

            $table->foreign('address_id')
                ->references('id')
                ->on('user_addresses')
                ->nullOnDelete();

            $table->index('delivery_partner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['delivery_partner_id']);
            $table->dropForeign(['address_id']);
            $table->dropColumn(['delivery_partner_id', 'address_id', 'notes']);
        });

        DB::statement("ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status IN ('placed', 'accepted', 'out_for_delivery', 'delivered'))");
    }
};
