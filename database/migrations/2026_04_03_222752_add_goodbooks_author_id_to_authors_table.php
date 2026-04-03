<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SHA-1 hex (40 chars) of the normalized display name from Goodbooks import — stable key when no Open Library id exists.
     */
    public function up(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->string('goodbooks_author_id', 40)->nullable()->unique()->after('open_library_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->dropUnique(['goodbooks_author_id']);
            $table->dropColumn('goodbooks_author_id');
        });
    }
};
