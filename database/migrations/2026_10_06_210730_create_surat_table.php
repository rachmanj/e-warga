<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('rts')->cascadeOnDelete();
            $table->foreignId('surat_jenis_id')->constrained('surat_jenis')->cascadeOnDelete();
            $table->foreignId('keluarga_id')->nullable()->constrained('keluarga')->nullOnDelete();
            $table->foreignId('warga_id')->nullable()->constrained('warga')->nullOnDelete();
            $table->integer('nomor_urut')->nullable();
            $table->integer('tahun');
            $table->string('nomor_lengkap')->nullable();
            $table->text('keperluan');
            $table->json('data_tambahan')->nullable();
            $table->date('tanggal_ajuan');
            $table->date('tanggal_terbit')->nullable();
            $table->enum('status', ['draft', 'diajukan', 'disetujui', 'ditolak', 'terbit', 'batal'])->default('diajukan');
            $table->text('alasan_tolak')->nullable();
            $table->foreignId('dibuat_oleh')->constrained('users')->cascadeOnDelete();
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('disetujui_at')->nullable();
            $table->string('file_path')->nullable();
            $table->string('kode_verifikasi')->unique();
            $table->timestamps();

            $table->unique(['tenant_id', 'surat_jenis_id', 'nomor_urut', 'tahun']);
            $table->unique(['tenant_id', 'nomor_lengkap']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat');
    }
};
