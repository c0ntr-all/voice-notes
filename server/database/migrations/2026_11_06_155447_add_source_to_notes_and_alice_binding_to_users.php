<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->string('source', 16)->default('app')->after('duration_ms');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('alice_user_id', 128)->nullable()->unique();
            $table->string('alice_link_code', 6)->nullable()->unique();
            $table->timestamp('alice_link_code_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropColumn('source');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['alice_user_id']);
            $table->dropUnique(['alice_link_code']);
            $table->dropColumn(['alice_user_id', 'alice_link_code', 'alice_link_code_expires_at']);
        });
    }
};
