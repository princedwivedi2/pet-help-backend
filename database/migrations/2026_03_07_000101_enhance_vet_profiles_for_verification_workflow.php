<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vet_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('vet_profiles', 'city')) {
                $table->string('city', 100)->nullable()->after('address');
                $table->index('city');
            }

            if (!Schema::hasColumn('vet_profiles', 'specialization')) {
                $table->string('specialization', 150)->nullable()->after('qualifications');
                $table->index('specialization');
            }

            if (!Schema::hasColumn('vet_profiles', 'profile_photo')) {
                $table->string('profile_photo')->nullable()->after('email');
            }

            if (!Schema::hasColumn('vet_profiles', 'degree_certificate_url')) {
                $table->string('degree_certificate_url')->nullable()->after('license_document_url');
            }

            if (!Schema::hasColumn('vet_profiles', 'government_id_url')) {
                $table->string('government_id_url')->nullable()->after('degree_certificate_url');
            }

            if (!Schema::hasColumn('vet_profiles', 'verification_documents')) {
                $table->json('verification_documents')->nullable()->after('government_id_url');
            }

            if (!Schema::hasColumn('vet_profiles', 'working_hours')) {
                $table->json('working_hours')->nullable()->after('accepted_species');
            }

            if (!Schema::hasColumn('vet_profiles', 'home_visit_fee')) {
                $table->unsignedInteger('home_visit_fee')->nullable()->after('consultation_fee');
            }

            if (!Schema::hasColumn('vet_profiles', 'verification_status')) {
                $table->enum('verification_status', ['pending', 'approved', 'rejected', 'suspended', 'needs_information'])
                    ->default('pending')
                    ->after('vet_status');
                $table->index('verification_status');
            }
        });

        if (Schema::hasColumn('vet_profiles', 'verification_status')) {
            DB::statement("UPDATE vet_profiles SET verification_status = vet_status WHERE verification_status IS NULL OR verification_status = ''");
        }
    }

    public function down(): void
    {
        Schema::table('vet_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('vet_profiles', 'verification_status')) {
                $table->dropIndex(['verification_status']);
                $table->dropColumn('verification_status');
            }

            if (Schema::hasColumn('vet_profiles', 'home_visit_fee')) {
                $table->dropColumn('home_visit_fee');
            }

            if (Schema::hasColumn('vet_profiles', 'working_hours')) {
                $table->dropColumn('working_hours');
            }

            if (Schema::hasColumn('vet_profiles', 'verification_documents')) {
                $table->dropColumn('verification_documents');
            }

            if (Schema::hasColumn('vet_profiles', 'government_id_url')) {
                $table->dropColumn('government_id_url');
            }

            if (Schema::hasColumn('vet_profiles', 'degree_certificate_url')) {
                $table->dropColumn('degree_certificate_url');
            }

            if (Schema::hasColumn('vet_profiles', 'profile_photo')) {
                $table->dropColumn('profile_photo');
            }

            if (Schema::hasColumn('vet_profiles', 'specialization')) {
                $table->dropIndex(['specialization']);
                $table->dropColumn('specialization');
            }

            if (Schema::hasColumn('vet_profiles', 'city')) {
                $table->dropIndex(['city']);
                $table->dropColumn('city');
            }
        });
    }
};
