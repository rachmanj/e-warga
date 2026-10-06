<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen_warga', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('rts')->cascadeOnDelete();
            $table->foreignId('keluarga_id')->nullable()->constrained('keluarga')->nullOnDelete();
            $table->foreignId('warga_id')->nullable()->constrained('warga')->nullOnDelete();
            $table->enum('jenis', ['ktp', 'kk', 'lainnya']);
            $table->string('nama_asli');
            $table->string('file_path');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_warga');
    }
};
