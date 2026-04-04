<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('works', function (Blueprint $table): void {
            $table->json('open_library_search_doc')->nullable()->after('subjects');
            $table->string('open_library_match_source', 64)->nullable()->after('open_library_search_doc');
            $table->timestamp('open_library_enriched_at')->nullable()->after('open_library_match_source');
        });
    }

    public function down(): void
    {
        Schema::table('works', function (Blueprint $table): void {
            $table->dropColumn([
                'open_library_search_doc',
                'open_library_match_source',
                'open_library_enriched_at',
            ]);
        });
    }
};
