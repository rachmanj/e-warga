<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warga', function (Blueprint $table) {
            $table->string('tempat_lahir')->nullable()->after('tanggal_lahir');
            $table->string('pendidikan')->nullable()->after('tempat_lahir');
            $table->string('kewarganegaraan')->nullable()->default('WNI')->after('pendidikan');
            $table->string('golongan_darah')->nullable()->after('kewarganegaraan');
        });
    }

    public function down(): void
    {
        Schema::table('warga', function (Blueprint $table) {
            $table->dropColumn([
                'tempat_lahir',
                'pendidikan',
                'kewarganegaraan',
                'golongan_darah',
            ]);
        });
    }
};
