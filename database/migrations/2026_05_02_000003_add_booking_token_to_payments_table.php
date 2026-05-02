<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Booking-token split payment model (per spec):
 *   user pays a small online prepayment (e.g. ₹49 / ₹99) at booking time, with
 *   the balance collected at the clinic visit.
 *
 *   token_amount       — paise paid online to confirm the booking
 *   balance_due        — paise remaining to collect at clinic
 *   balance_collected_at — vet records when the balance was collected
 *
 * For full-online and pure-offline payments these stay null.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedInteger('token_amount')->nullable()->after('amount');
            $table->unsignedInteger('balance_due')->nullable()->after('token_amount');
            $table->timestamp('balance_collected_at')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['token_amount', 'balance_due', 'balance_collected_at']);
        });
    }
};
