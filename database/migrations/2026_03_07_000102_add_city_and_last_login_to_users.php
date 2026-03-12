<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'city')) {
                $table->string('city', 100)->nullable()->after('address');
                $table->index('city');
            }

            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('remember_token');
                $table->index('last_login_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_login_at')) {
                $table->dropIndex(['last_login_at']);
                $table->dropColumn('last_login_at');
            }

            if (Schema::hasColumn('users', 'city')) {
                $table->dropIndex(['city']);
                $table->dropColumn('city');
            }
        });
    }
};
