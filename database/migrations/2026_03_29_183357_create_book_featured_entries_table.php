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
        Schema::create('book_featured_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('import_batch');
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('source', 64);
            $table->string('list_name', 128)->nullable();
            $table->json('payload')->nullable();
            $table->timestampTz('imported_at');
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

            $table->index(['import_batch', 'position']);
            $table->index('imported_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_featured_entries');
    }
};
