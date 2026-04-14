<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add appointment rescheduling, waitlist, and medical history features.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Appointment rescheduling support
        if (Schema::hasTable('appointments')) {
            if (! Schema::hasColumn('appointments', 'original_scheduled_at')) {
                Schema::table('appointments', function (Blueprint $table) {
                    $table->timestamp('original_scheduled_at')->nullable();
                });
            }

            if (! Schema::hasColumn('appointments', 'reschedule_count')) {
                Schema::table('appointments', function (Blueprint $table) {
                    $table->unsignedTinyInteger('reschedule_count')->default(0);
                });
            }

            if (! Schema::hasColumn('appointments', 'reschedule_reason')) {
                Schema::table('appointments', function (Blueprint $table) {
                    $table->text('reschedule_reason')->nullable();
                });
            }

            if (! Schema::hasColumn('appointments', 'reminder_sent_at')) {
                Schema::table('appointments', function (Blueprint $table) {
                    $table->timestamp('reminder_sent_at')->nullable();
                });
            }

            if (! Schema::hasColumn('appointments', 'timezone')) {
                Schema::table('appointments', function (Blueprint $table) {
                    $table->string('timezone', 50)->default('Asia/Kolkata');
                });
            }
        }

        // Waitlist for appointments
        if (! Schema::hasTable('appointment_waitlist')) {
            Schema::create('appointment_waitlist', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('vet_profile_id')->constrained()->onDelete('cascade');
                $table->foreignId('pet_id')->nullable()->constrained()->onDelete('set null');
                $table->date('preferred_date');
                $table->time('preferred_time_start')->nullable();
                $table->time('preferred_time_end')->nullable();
                $table->string('consultation_type', 50)->default('clinic');
                $table->boolean('is_notified')->default(false);
                $table->timestamp('notified_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->index(['vet_profile_id', 'preferred_date', 'is_notified'], 'waitlist_vet_date');
                $table->index(['user_id', 'created_at'], 'waitlist_user');
            });
        }

        // Pet medical history
        if (Schema::hasTable('pets')) {
            if (! Schema::hasColumn('pets', 'microchip_number')) {
                Schema::table('pets', function (Blueprint $table) {
                    $table->string('microchip_number', 50)->nullable();
                });
            }

            if (! Schema::hasColumn('pets', 'vaccinations')) {
                Schema::table('pets', function (Blueprint $table) {
                    $table->json('vaccinations')->nullable();
                });
            }

            if (! Schema::hasColumn('pets', 'allergies')) {
                Schema::table('pets', function (Blueprint $table) {
                    $table->json('allergies')->nullable();
                });
            }

            if (! Schema::hasColumn('pets', 'medications')) {
                Schema::table('pets', function (Blueprint $table) {
                    $table->json('medications')->nullable();
                });
            }

            if (! Schema::hasColumn('pets', 'medical_conditions')) {
                Schema::table('pets', function (Blueprint $table) {
                    $table->json('medical_conditions')->nullable();
                });
            }

            if (! Schema::hasColumn('pets', 'last_checkup_date')) {
                Schema::table('pets', function (Blueprint $table) {
                    $table->date('last_checkup_date')->nullable();
                });
            }

            if (! Schema::hasColumn('pets', 'medical_notes')) {
                Schema::table('pets', function (Blueprint $table) {
                    $table->text('medical_notes')->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('appointments')) {
            $appointmentColumns = [
                'original_scheduled_at',
                'reschedule_count',
                'reschedule_reason',
                'reminder_sent_at',
                'timezone',
            ];

            foreach ($appointmentColumns as $column) {
                if (Schema::hasColumn('appointments', $column)) {
                    Schema::table('appointments', function (Blueprint $table) use ($column) {
                        $table->dropColumn($column);
                    });
                }
            }
        }

        Schema::dropIfExists('appointment_waitlist');

        if (Schema::hasTable('pets')) {
            $petColumns = [
                'microchip_number',
                'vaccinations',
                'allergies',
                'medications',
                'medical_conditions',
                'last_checkup_date',
                'medical_notes',
            ];

            foreach ($petColumns as $column) {
                if (Schema::hasColumn('pets', $column)) {
                    Schema::table('pets', function (Blueprint $table) use ($column) {
                        $table->dropColumn($column);
                    });
                }
            }
        }
    }
};
