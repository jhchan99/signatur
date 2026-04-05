<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Re-applies authors.open_library_id when the migration ledger and schema diverge
     * (e.g. column dropped out-of-band while migrations rows remain).
     */
    public function up(): void
    {
        if (Schema::hasColumn('authors', 'open_library_id')) {
            return;
        }

        Schema::table('authors', function (Blueprint $table): void {
            $table->string('open_library_id', 255)->nullable()->unique()->after('id');
        });
    }

    /**
     * Intentional no-op: up() may skip when the column was created by an earlier migration;
     * dropping here would break those databases on partial rollback.
     */
    public function down(): void {}
};
