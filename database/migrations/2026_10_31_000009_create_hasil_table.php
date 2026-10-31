<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hasil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftar_id')->unique()->constrained('pendaftar')->cascadeOnDelete();
            $table->foreignId('jurusan_id')->nullable()->constrained('jurusan')->nullOnDelete();
            $table->unsignedInteger('perankingan')->nullable();
            $table->string('status_akhir', 20);
            $table->unsignedTinyInteger('via_pilihan')->nullable();
            $table->timestamp('diproses_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hasil');
    }
};
