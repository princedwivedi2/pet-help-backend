<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('comments');
        Schema::dropIfExists('community_posts');
        Schema::dropIfExists('communities');
        Schema::dropIfExists('blogs');
    }

    public function down(): void
    {
        // Old tables are not recreated — replaced by new module tables.
    }
};
