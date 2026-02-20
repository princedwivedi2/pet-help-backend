<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name', 100);
            $table->enum('species', ['dog', 'cat', 'bird', 'rabbit', 'hamster', 'fish', 'reptile', 'other']);
            $table->string('breed', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->string('photo_url')->nullable();
            $table->text('medical_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pets');
    }
};
