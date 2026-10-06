<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rts', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('rw');
            $table->string('kelurahan');
            $table->string('kecamatan');
            $table->string('kota');
            $table->string('kode_pos')->nullable();
            $table->string('nama_ketua')->nullable();
            $table->string('nama_sekretaris')->nullable();
            $table->string('nama_bendahara')->nullable();
            $table->text('alamat_sekretariat')->nullable();
            $table->string('no_hp')->nullable();
            $table->string('slug')->unique();
            $table->boolean('publik_aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rts');
    }
};
