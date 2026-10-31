<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nilai_seleksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftar_id')->unique()->constrained('pendaftar')->cascadeOnDelete();
            $table->decimal('nilai_rapor', 5, 2)->default(0);
            $table->decimal('nilai_prestasi', 5, 2)->default(0);
            $table->decimal('nilai_kunjungan', 5, 2)->default(0);
            $table->decimal('total', 5, 2)->default(0);
            $table->timestamp('diinput_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nilai_seleksi');
    }
};
