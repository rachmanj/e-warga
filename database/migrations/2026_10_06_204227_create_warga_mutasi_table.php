<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warga_mutasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('rts')->cascadeOnDelete();
            $table->foreignId('keluarga_id')->constrained('keluarga')->cascadeOnDelete();
            $table->foreignId('warga_id')->nullable()->constrained('warga')->nullOnDelete();
            $table->enum('jenis', ['masuk', 'keluar', 'lahir', 'meninggal', 'ubah_kk']);
            $table->date('tanggal');
            $table->text('keterangan')->nullable();
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warga_mutasi');
    }
};
