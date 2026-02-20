<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_guides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('emergency_categories')->onDelete('cascade');
            $table->string('title', 200);
            $table->string('slug', 200)->unique();
            $table->text('summary');
            $table->longText('content');
            $table->json('applicable_species')->nullable();
            $table->enum('severity_level', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->integer('estimated_read_minutes')->default(5);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['category_id', 'is_published']);
            $table->index('severity_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_guides');
    }
};
