<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kas_kategori', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('rts')->cascadeOnDelete();
            $table->string('nama');
            $table->enum('jenis', ['masuk', 'keluar']);
            $table->timestamps();

            $table->unique(['tenant_id', 'nama', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_kategori');
    }
};
