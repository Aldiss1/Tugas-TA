<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftar_id')->constrained('pendaftar')->cascadeOnDelete();
            $table->string('jenis', 20);
            $table->string('path', 500);
            $table->string('tipe_file', 60)->nullable();
            $table->unsignedInteger('ukuran_bytes')->default(0);
            $table->timestamp('diunggah_at')->nullable();
            $table->timestamps();
            $table->unique(['pendaftar_id', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen');
    }
};
