<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iuran_tarif', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('rts')->cascadeOnDelete();
            $table->foreignId('iuran_jenis_id')->constrained('iuran_jenis')->cascadeOnDelete();
            $table->foreignId('keluarga_id')->nullable()->constrained('keluarga')->cascadeOnDelete();
            $table->decimal('nominal', 14, 2);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'iuran_jenis_id', 'keluarga_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iuran_tarif');
    }
};
