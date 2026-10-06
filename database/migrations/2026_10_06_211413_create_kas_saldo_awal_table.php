<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kas_saldo_awal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('rts')->cascadeOnDelete();
            $table->integer('tahun');
            $table->enum('pos', ['tunai', 'bank']);
            $table->decimal('jumlah', 14, 2);
            $table->timestamps();

            $table->unique(['tenant_id', 'tahun', 'pos']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_saldo_awal');
    }
};
