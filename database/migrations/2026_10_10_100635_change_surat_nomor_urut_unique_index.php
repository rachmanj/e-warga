<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'surat_jenis_id', 'nomor_urut', 'tahun']);
            $table->unique(['tenant_id', 'nomor_urut', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::table('surat', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'nomor_urut', 'tahun']);
            $table->unique(['tenant_id', 'surat_jenis_id', 'nomor_urut', 'tahun']);
        });
    }
};
