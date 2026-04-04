<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('works', function (Blueprint $table): void {
            // Used for ORDER BY title on the books listing page
            $table->index('title');
            // Used for WHERE first_publish_year = ? filter and the DISTINCT pluck for year options
            $table->index('first_publish_year');
        });
    }

    public function down(): void
    {
        Schema::table('works', function (Blueprint $table): void {
            $table->dropIndex(['title']);
            $table->dropIndex(['first_publish_year']);
        });
    }
};
