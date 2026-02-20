<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vet_profiles', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')
                ->constrained()->onDelete('cascade');
            $table->text('qualifications')->nullable()->after('postal_code');
            $table->string('license_number', 100)->nullable()->unique()->after('qualifications');
            $table->unsignedSmallInteger('years_of_experience')->nullable()->after('license_number');
            $table->string('license_document_url')->nullable()->after('years_of_experience');
            $table->text('rejection_reason')->nullable()->after('is_verified');
            $table->timestamp('verified_at')->nullable()->after('rejection_reason');
            $table->foreignId('verified_by')->nullable()->after('verified_at')
                ->constrained('users')->onDelete('set null');

            $table->index('user_id');
            $table->index('license_number');
            $table->index('is_verified');
        });
    }

    public function down(): void
    {
        Schema::table('vet_profiles', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['verified_by']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['license_number']);
            $table->dropIndex(['is_verified']);
            $table->dropUnique(['license_number']);
            $table->dropColumn([
                'user_id',
                'qualifications',
                'license_number',
                'years_of_experience',
                'license_document_url',
                'rejection_reason',
                'verified_at',
                'verified_by',
            ]);
        });
    }
};
