<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('client_uuid');
            $table->text('body');
            $table->dateTime('recorded_at');
            $table->unsignedInteger('duration_ms');
            $table->timestamps();

            $table->unique(['user_id', 'client_uuid']);
            $table->index(['user_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
