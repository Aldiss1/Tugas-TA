<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pendaftar', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_pendaftaran', 20)->nullable()->unique();
            $table->foreignId('pengguna_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sumber', 10)->default('online');
            $table->string('nisn', 10)->nullable()->index();
            $table->string('nama_lengkap');
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir');
            $table->string('jenis_kelamin', 1);
            $table->string('agama', 20)->nullable();
            $table->string('asal_sekolah')->nullable();
            $table->text('alamat')->nullable();
            $table->string('nik', 20)->nullable();
            $table->string('nama_orang_tua')->nullable();
            $table->string('pekerjaan_orang_tua')->nullable();
            $table->string('telepon_orang_tua', 20)->nullable();
            $table->string('status', 30)->default('draf')->index();
            $table->text('alasan_gugur')->nullable();
            $table->timestamp('dikirim_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pendaftar');
    }
};
