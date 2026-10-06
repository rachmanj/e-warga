<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keluarga', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('rts')->cascadeOnDelete();
            $table->text('no_kk');
            $table->string('no_kk_hash')->index();
            $table->string('alamat');
            $table->string('blok_unit')->nullable();
            $table->string('rt_lingkungan')->nullable();
            $table->enum('status_hunian', ['milik', 'sewa', 'kontrak', 'kos']);
            $table->string('nama_pemilik')->nullable();
            $table->date('tanggal_masuk')->nullable();
            $table->date('tanggal_keluar')->nullable();
            $table->enum('status', ['aktif', 'pindah', 'nonaktif'])->default('aktif');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'no_kk_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keluarga');
    }
};
