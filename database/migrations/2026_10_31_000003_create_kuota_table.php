<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kuota', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurusan_id')->unique()->constrained('jurusan')->cascadeOnDelete();
            $table->unsignedInteger('kuota')->default(0);
            $table->unsignedInteger('cadangan')->default(0);
            $table->unsignedInteger('terisi')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kuota');
    }
};
