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
        Schema::disableForeignKeyConstraints();

        Schema::create('reading_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->bigInteger('book_id');
            $table->foreign('book_id')->references('id')->on('books');
            $table->string('status', 255);
            $table->decimal('rating', 2, 1)->nullable();
            $table->text('review_text')->nullable();
            $table->boolean('is_spoiler');
            $table->boolean('is_private');
            $table->date('date_started')->nullable();
            $table->date('date_finished')->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');
            $table->unique(['user_id', 'book_id']);
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reading_logs');
    }
};
