<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nilai_seleksi_riwayat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nilai_seleksi_id')->constrained('nilai_seleksi')->cascadeOnDelete();
            $table->json('snapshot');
            $table->foreignId('diubah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nilai_seleksi_riwayat');
    }
};
