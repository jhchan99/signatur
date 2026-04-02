<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('editions', function (Blueprint $table): void {
            $table->id();
            $table->string('open_library_key', 255)->unique();
            $table->foreignId('work_id')->nullable()->constrained('works')->nullOnDelete();
            $table->string('title', 512)->nullable();
            $table->string('subtitle', 512)->nullable();
            $table->string('by_statement', 512)->nullable();
            $table->string('edition_name', 255)->nullable();
            $table->string('physical_format', 255)->nullable();
            $table->json('publishers')->nullable();
            $table->string('publish_date', 255)->nullable();
            $table->unsignedInteger('number_of_pages')->nullable();
            $table->unsignedInteger('cover_id')->nullable();
            $table->json('languages')->nullable();
            $table->json('subjects')->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

            $table->index('work_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('editions');
    }
};
