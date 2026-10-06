<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iuran_pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('rts')->cascadeOnDelete();
            $table->foreignId('iuran_tagihan_id')->constrained('iuran_tagihan')->cascadeOnDelete();
            $table->date('tanggal');
            $table->decimal('jumlah', 14, 2);
            $table->enum('metode', ['tunai', 'transfer', 'lainnya']);
            $table->string('no_referensi')->nullable();
            $table->string('bukti_path')->nullable();
            $table->foreignId('dicatat_oleh')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iuran_pembayaran');
    }
};
