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
        Schema::table('authors', function (Blueprint $table): void {
            $table->string('birth_date', 128)->nullable()->after('bio');
            $table->string('death_date', 128)->nullable()->after('birth_date');
            $table->string('wikipedia', 512)->nullable()->after('death_date');
            $table->json('alternate_names')->nullable()->after('wikipedia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('authors', function (Blueprint $table): void {
            $table->dropColumn([
                'birth_date',
                'death_date',
                'wikipedia',
                'alternate_names',
            ]);
        });
    }
};
