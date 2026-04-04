<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('authors', function (Blueprint $table): void {
            $table->json('open_library_author_search_doc')->nullable()->after('alternate_names');
            $table->timestamp('open_library_author_enriched_at')->nullable()->after('open_library_author_search_doc');
        });
    }

    public function down(): void
    {
        Schema::table('authors', function (Blueprint $table): void {
            $table->dropColumn([
                'open_library_author_search_doc',
                'open_library_author_enriched_at',
            ]);
        });
    }
};
