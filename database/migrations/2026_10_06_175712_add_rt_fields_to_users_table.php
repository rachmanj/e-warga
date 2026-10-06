<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('rts')->nullOnDelete();
            $table->string('username')->nullable()->unique()->after('tenant_id');
            $table->string('nama')->nullable()->after('username');
            $table->boolean('aktif')->default(true)->after('nama');
        });

        foreach (DB::table('users')->orderBy('id')->get() as $user) {
            $username = strstr($user->email, '@', true) ?: $user->email;
            $base = $username;
            $suffix = 0;

            while (
                DB::table('users')
                    ->where('username', $username)
                    ->where('id', '!=', $user->id)
                    ->exists()
            ) {
                $suffix++;
                $username = $base.$suffix;
            }

            DB::table('users')->where('id', $user->id)->update([
                'username' => $username,
                'nama' => $user->name,
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable(false)->change();
            $table->string('nama')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn(['username', 'nama', 'aktif']);
        });
    }
};
