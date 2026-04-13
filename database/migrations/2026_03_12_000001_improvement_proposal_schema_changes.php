<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SOS_STATUS_BACKUP_COLUMN = 'legacy_status_before_standardization';

    private const SOS_STATUS_FORWARD_MAP = [
        'pending' => 'sos_pending',
        'acknowledged' => 'sos_accepted',
        'in_progress' => 'sos_in_progress',
        'treatment_in_progress' => 'sos_in_progress',
        'completed' => 'sos_completed',
        'cancelled' => 'sos_cancelled',
    ];

    private const SOS_STATUS_ROLLBACK_MAP = [
        'sos_pending' => 'pending',
        'sos_accepted' => 'acknowledged',
        'sos_in_progress' => 'in_progress',
        'sos_completed' => 'completed',
        'sos_cancelled' => 'cancelled',
        'vet_on_the_way' => 'vet_on_the_way',
        'arrived' => 'arrived',
        'expired' => 'expired',
    ];

    private const SOS_STATUS_ENUM_VALUES = [
        'sos_pending', 'sos_accepted', 'vet_on_the_way', 'arrived',
        'sos_in_progress', 'sos_completed', 'sos_cancelled', 'expired',
        'pending', 'acknowledged', 'in_progress', 'treatment_in_progress',
        'completed', 'cancelled',
    ];

    private const SOS_STATUS_LEGACY_ENUM_VALUES = [
        'pending', 'acknowledged', 'in_progress', 'treatment_in_progress', 'completed', 'cancelled',
        'vet_on_the_way', 'arrived', 'expired'
    ];

    public function up(): void
    {
        $this->setSosStatusEnum(self::SOS_STATUS_ENUM_VALUES, 'sos_pending');

        if (!Schema::hasColumn('sos_requests', self::SOS_STATUS_BACKUP_COLUMN)) {
            Schema::table('sos_requests', function (Blueprint $table) {
                $table->string(self::SOS_STATUS_BACKUP_COLUMN, 50)->nullable()->after('status');
            });
        }

        DB::statement(
            "UPDATE sos_requests SET `" . self::SOS_STATUS_BACKUP_COLUMN . "` = `status` WHERE `" . self::SOS_STATUS_BACKUP_COLUMN . "` IS NULL"
        );

        // ── VetProfile enhancements ──
        Schema::table('vet_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('vet_profiles', 'online_fee')) {
                $table->unsignedInteger('online_fee')->nullable()->after('home_visit_fee');
            }
            if (!Schema::hasColumn('vet_profiles', 'max_home_visit_km')) {
                $table->unsignedInteger('max_home_visit_km')->default(10)->after('online_fee');
            }
        });

        // ── SOS Request enhancements ──
        Schema::table('sos_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('sos_requests', 'response_type')) {
                $table->string('response_type', 50)->nullable()->after('status');
            }
            if (!Schema::hasColumn('sos_requests', 'estimated_arrival_at')) {
                $table->timestamp('estimated_arrival_at')->nullable()->after('response_type');
            }
        });

        // ── Appointment enhancements ──
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'payment_mode')) {
                $table->string('payment_mode', 20)->default('online')->after('payment_status');
            }
        });

        // ── Standardize SOS statuses (migrate old names to new) ──
        foreach (self::SOS_STATUS_FORWARD_MAP as $from => $to) {
            DB::table('sos_requests')->where('status', $from)->update(['status' => $to]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sos_requests', self::SOS_STATUS_BACKUP_COLUMN)) {
            DB::statement(
                "UPDATE sos_requests SET `status` = `" . self::SOS_STATUS_BACKUP_COLUMN . "` WHERE `" . self::SOS_STATUS_BACKUP_COLUMN . "` IS NOT NULL"
            );
        }

        foreach (self::SOS_STATUS_ROLLBACK_MAP as $from => $to) {
            DB::table('sos_requests')->where('status', $from)->update(['status' => $to]);
        }

        $this->setSosStatusEnum(self::SOS_STATUS_LEGACY_ENUM_VALUES, 'pending');

        $vetProfileDrops = array_filter([
            Schema::hasColumn('vet_profiles', 'online_fee') ? 'online_fee' : null,
            Schema::hasColumn('vet_profiles', 'max_home_visit_km') ? 'max_home_visit_km' : null,
        ]);
        if (!empty($vetProfileDrops)) {
            Schema::table('vet_profiles', function (Blueprint $table) use ($vetProfileDrops) {
                $table->dropColumn($vetProfileDrops);
            });
        }

        $sosDrops = array_filter([
            Schema::hasColumn('sos_requests', 'response_type') ? 'response_type' : null,
            Schema::hasColumn('sos_requests', 'estimated_arrival_at') ? 'estimated_arrival_at' : null,
        ]);
        if (!empty($sosDrops)) {
            Schema::table('sos_requests', function (Blueprint $table) use ($sosDrops) {
                $table->dropColumn($sosDrops);
            });
        }

        if (Schema::hasColumn('appointments', 'payment_mode')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropColumn('payment_mode');
            });
        }

        if (Schema::hasColumn('sos_requests', self::SOS_STATUS_BACKUP_COLUMN)) {
            Schema::table('sos_requests', function (Blueprint $table) {
                $table->dropColumn(self::SOS_STATUS_BACKUP_COLUMN);
            });
        }
    }

    private function setSosStatusEnum(array $values, ?string $default = null): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // Assert that input values are simple, non-empty strings to prevent SQL injection.
        assert(!empty($values), 'Values array cannot be empty.');
        foreach ($values as $value) {
            assert(is_string($value) && preg_match('/^[A-Z0-9_]+$/i', $value), "Invalid ENUM value: {$value}");
        }
        if ($default) {
            assert(in_array($default, $values), "Default value '{$default}' not in provided values.");
        }

        $enumList = implode("','", $values);
        $defaultClause = $default ? " DEFAULT '{$default}'" : '';
        DB::statement("ALTER TABLE sos_requests MODIFY COLUMN status ENUM('{$enumList}') NOT NULL{$defaultClause}");
    }
};
