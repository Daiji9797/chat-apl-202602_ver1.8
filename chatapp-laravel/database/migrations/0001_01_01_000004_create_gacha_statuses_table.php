<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gacha_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('gacha_id');
            $table->integer('stage')->default(1);
            $table->timestamps();
            $table->unique(['user_id', 'gacha_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gacha_statuses');
    }
};
