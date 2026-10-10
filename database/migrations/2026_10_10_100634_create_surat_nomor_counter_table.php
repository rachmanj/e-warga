<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_nomor_counter', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('rts')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedInteger('nomor_terakhir')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_nomor_counter');
    }
};
