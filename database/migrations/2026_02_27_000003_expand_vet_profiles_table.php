<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vet_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('vet_profiles', 'consultation_fee')) {
                $table->unsignedInteger('consultation_fee')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('vet_profiles', 'consultation_types')) {
                $table->json('consultation_types')->nullable()->after('consultation_fee');
            }
            if (!Schema::hasColumn('vet_profiles', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('consultation_types');
            }
            if (!Schema::hasColumn('vet_profiles', 'featured_until')) {
                $table->timestamp('featured_until')->nullable()->after('is_featured');
            }
            if (!Schema::hasColumn('vet_profiles', 'avg_rating')) {
                $table->decimal('avg_rating', 3, 2)->nullable()->after('featured_until');
            }
            if (!Schema::hasColumn('vet_profiles', 'total_reviews')) {
                $table->unsignedInteger('total_reviews')->default(0)->after('avg_rating');
            }
            if (!Schema::hasColumn('vet_profiles', 'total_appointments')) {
                $table->unsignedInteger('total_appointments')->default(0)->after('total_reviews');
            }
            if (!Schema::hasColumn('vet_profiles', 'completed_appointments')) {
                $table->unsignedInteger('completed_appointments')->default(0)->after('total_appointments');
            }
            if (!Schema::hasColumn('vet_profiles', 'acceptance_rate')) {
                $table->decimal('acceptance_rate', 5, 2)->nullable()->after('completed_appointments');
            }
            if (!Schema::hasColumn('vet_profiles', 'avg_response_minutes')) {
                $table->decimal('avg_response_minutes', 8, 2)->nullable()->after('acceptance_rate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vet_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'consultation_fee', 'consultation_types', 'is_featured',
                'featured_until', 'total_appointments', 'completed_appointments',
                'acceptance_rate', 'avg_response_minutes',
            ]);
        });
    }
};
