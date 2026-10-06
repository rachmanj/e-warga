<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warga', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('rts')->cascadeOnDelete();
            $table->foreignId('keluarga_id')->constrained('keluarga')->cascadeOnDelete();
            $table->text('nik')->nullable();
            $table->string('nik_hash')->nullable();
            $table->string('nik_4_terakhir', 4)->nullable();
            $table->string('nama');
            $table->enum('hubungan', ['kepala', 'istri', 'anak', 'famili', 'lain']);
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->date('tanggal_lahir')->nullable();
            $table->string('pekerjaan')->nullable();
            $table->string('agama')->nullable();
            $table->string('status_perkawinan')->nullable();
            $table->string('no_hp')->nullable();
            $table->enum('status', ['aktif', 'pindah', 'meninggal'])->default('aktif');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warga');
    }
};
