<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kwitansi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('rts')->cascadeOnDelete();
            $table->foreignId('iuran_pembayaran_id')->constrained('iuran_pembayaran')->cascadeOnDelete();
            $table->string('nomor');
            $table->integer('tahun');
            $table->date('tanggal');
            $table->string('pdf_path')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'nomor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kwitansi');
    }
};
