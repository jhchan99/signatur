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

        Schema::create('follows', function (Blueprint $table) {
            $table->bigInteger('followee_id');
            $table->foreign('followee_id')->references('id')->on('users');
            $table->bigInteger('follower_id');
            $table->foreign('follower_id')->references('id')->on('users');
            $table->timestampTz('created_at');
            $table->primary(['followee_id', 'follower_id']);
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
