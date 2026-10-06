<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iuran_tagihan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('rts')->cascadeOnDelete();
            $table->foreignId('keluarga_id')->constrained('keluarga')->cascadeOnDelete();
            $table->foreignId('iuran_jenis_id')->constrained('iuran_jenis')->cascadeOnDelete();
            $table->string('periode', 7);
            $table->decimal('nominal', 14, 2);
            $table->date('jatuh_tempo')->nullable();
            $table->enum('status', ['belum', 'sebagian', 'lunas', 'bebas'])->default('belum');
            $table->text('alasan_bebas')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'keluarga_id', 'iuran_jenis_id', 'periode'], 'iuran_tagihan_tenant_keluarga_jenis_periode_uniq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iuran_tagihan');
    }
};
