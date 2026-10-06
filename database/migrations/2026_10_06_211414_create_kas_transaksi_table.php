<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kas_transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('rts')->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('jenis', ['masuk', 'keluar']);
            $table->enum('pos', ['tunai', 'bank']);
            $table->foreignId('kas_kategori_id')->nullable()->constrained('kas_kategori')->nullOnDelete();
            $table->string('uraian');
            $table->decimal('jumlah', 14, 2);
            $table->string('no_bukti')->nullable();
            $table->foreignId('iuran_pembayaran_id')->nullable()->constrained('iuran_pembayaran')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_transaksi');
    }
};
