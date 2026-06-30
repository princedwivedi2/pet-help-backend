<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-device FCM tokens.
 *
 * Replaces the single `users.fcm_token` column with a per-device row, so a user
 * who installs the app on phone + tablet + web doesn't lose previous tokens
 * every time they register a new one.
 *
 * UNIQUE(token) lets us upsert: when the same token is presented again
 * (e.g. user reinstalls), we update last_seen_at + reactivate rather than
 * inserting a duplicate. If the token previously belonged to a different user,
 * we re-assign — FCM tokens are device-scoped, not user-scoped, so multiple
 * users on the same device must not retain overlapping tokens.
 *
 * `users.fcm_token` stays for now — backfilled into device_tokens — and gets
 * dropped in a follow-up migration once all callers are switched.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('device_tokens')) {
            return;
        }

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token', 255)->unique();
            $table->enum('platform', ['ios', 'android', 'web', 'unknown'])->default('unknown');
            $table->timestamp('last_seen_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });

        // Backfill existing users.fcm_token values so notifications keep working
        // through the deploy. Only carry over non-empty values.
        if (Schema::hasColumn('users', 'fcm_token')) {
            DB::table('users')
                ->whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->select('id', 'fcm_token', 'updated_at')
                ->orderBy('id')
                ->chunk(500, function ($users) {
                    $rows = [];
                    foreach ($users as $u) {
                        $rows[] = [
                            'user_id' => $u->id,
                            'token' => $u->fcm_token,
                            'platform' => 'unknown',
                            'last_seen_at' => $u->updated_at,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    if (!empty($rows)) {
                        // insertOrIgnore in case duplicates exist across users.
                        DB::table('device_tokens')->insertOrIgnore($rows);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
