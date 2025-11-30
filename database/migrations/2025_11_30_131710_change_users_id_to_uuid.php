<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Note: This migration converts users.id from bigInteger to UUID.
     * If you have existing data, you'll need to handle the conversion carefully.
     * This is a complex operation and should be tested in a development environment first.
     */
    public function up(): void
    {
        // Check if users table exists
        if (!Schema::hasTable('users')) {
            return;
        }

        // Drop foreign key constraints on sessions table first
        try {
            Schema::table('sessions', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        } catch (\Exception $e) {
            // Foreign key might not exist, continue
        }

        // Add temporary uuid column
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid_temp')->nullable()->after('id');
        });

        // Generate UUIDs for existing records
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update(['uuid_temp' => (string) Str::uuid()]);
        }

        // Update sessions table user_id to match new UUIDs
        $userMappings = DB::table('users')->pluck('uuid_temp', 'id');
        foreach ($userMappings as $oldId => $newUuid) {
            DB::table('sessions')
                ->where('user_id', $oldId)
                ->update(['user_id' => $newUuid]);
        }

        // Drop old id column and rename uuid_temp to id
        // This is complex, so we use raw SQL
        DB::statement('ALTER TABLE users DROP PRIMARY KEY');
        DB::statement('ALTER TABLE users DROP COLUMN id');
        DB::statement('ALTER TABLE users CHANGE uuid_temp id CHAR(36) NOT NULL');
        DB::statement('ALTER TABLE users ADD PRIMARY KEY (id)');

        // Update sessions table to use UUID (change column type)
        DB::statement('ALTER TABLE sessions MODIFY user_id CHAR(36)');

        // Re-add foreign key constraint
        Schema::table('sessions', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is complex to reverse - consider backing up data first
        // For now, we'll leave it as a note that manual intervention may be needed
        throw new \Exception('Reversing UUID migration requires manual intervention. Please backup your data first.');
    }
};
