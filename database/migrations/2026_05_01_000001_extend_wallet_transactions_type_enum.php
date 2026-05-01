<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Extend the wallet_transactions.type enum to include payout_request and
 * payout_completed. These two types are required for the vet payout workflow:
 *   payout_request  — vet submits a withdrawal request (already used in code)
 *   payout_completed — admin approves and finalises the payout
 *
 * SQLite (used in tests) does not enforce ENUM, so this migration is a no-op
 * on SQLite and only alters the column on MySQL / MariaDB.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE wallet_transactions
                MODIFY COLUMN type ENUM(
                    'credit',
                    'debit',
                    'payout',
                    'refund_debit',
                    'payout_request',
                    'payout_completed'
                ) NOT NULL
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE wallet_transactions
                MODIFY COLUMN type ENUM(
                    'credit',
                    'debit',
                    'payout',
                    'refund_debit'
                ) NOT NULL
            ");
        }
    }
};
