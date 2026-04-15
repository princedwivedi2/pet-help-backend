<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pet_medications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pet_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Prescription information
            $table->foreignId('prescribed_by_vet_id')->nullable()->constrained('vet_profiles')->onDelete('set null');
            $table->foreignId('appointment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('sos_request_id')->nullable()->constrained()->onDelete('set null');
            
            // Medication details
            $table->string('medication_name');
            $table->string('generic_name')->nullable();
            $table->string('dosage');
            $table->string('dosage_unit', 50)->default('mg');
            
            // Frequency and duration
            $table->integer('frequency')->default(1);
            $table->enum('frequency_unit', ['daily', 'weekly', 'monthly', 'as_needed', 'hours'])->default('daily');
            $table->integer('duration_days')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            
            // Administration
            $table->enum('administration_method', [
                'oral', 'topical', 'injection', 'drops', 'spray', 
                'inhaler', 'patch', 'suppository', 'other'
            ])->default('oral');
            
            $table->text('instructions')->nullable();
            $table->text('side_effects')->nullable();
            $table->text('contraindications')->nullable();
            $table->text('food_instructions')->nullable();
            $table->text('storage_instructions')->nullable();
            
            // Pharmacy and refill information
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('pharmacy_name')->nullable();
            $table->string('prescription_number')->nullable();
            $table->integer('refills_remaining')->default(0);
            $table->integer('total_refills')->default(0);
            
            // Status tracking
            $table->boolean('is_active')->default(true);
            $table->text('discontinuation_reason')->nullable();
            $table->timestamp('discontinued_at')->nullable();
            $table->text('notes')->nullable();
            
            // Reminder settings
            $table->boolean('reminder_enabled')->default(true);
            
            // Media
            $table->json('photo_urls')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['pet_id', 'is_active']);
            $table->index(['user_id', 'is_active']);
            $table->index(['prescribed_by_vet_id', 'created_at']);
            $table->index(['start_date', 'end_date']);
            $table->index(['refills_remaining', 'is_active']);
        });

        if (Schema::hasTable('pet_reminders') && Schema::hasColumn('pet_reminders', 'related_medication_id') && ! $this->foreignKeyExists('pet_reminders', 'pet_reminders_related_medication_id_foreign')) {
            Schema::table('pet_reminders', function (Blueprint $table) {
                $table->foreign('related_medication_id', 'pet_reminders_related_medication_id_foreign')
                    ->references('id')
                    ->on('pet_medications')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pet_reminders') && $this->foreignKeyExists('pet_reminders', 'pet_reminders_related_medication_id_foreign')) {
            Schema::table('pet_reminders', function (Blueprint $table) {
                $table->dropForeign('pet_reminders_related_medication_id_foreign');
            });
        }

        Schema::dropIfExists('pet_medications');
    }

    private function foreignKeyExists(string $tableName, string $constraintName): bool
    {
        $result = DB::selectOne(
            'SELECT COUNT(*) AS count FROM information_schema.table_constraints WHERE constraint_schema = DATABASE() AND table_name = ? AND constraint_name = ? AND constraint_type = ? ',
            [$tableName, $constraintName, 'FOREIGN KEY']
        );

        return (int) ($result->count ?? 0) > 0;
    }
};