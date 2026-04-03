<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('works', function (Blueprint $table): void {
            $table->unsignedBigInteger('goodbooks_book_id')->nullable()->unique();
        });

        Schema::table('works', function (Blueprint $table): void {
            $table->string('open_library_key', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (DB::table('works')->whereNull('open_library_key')->cursor() as $row) {
            DB::table('works')->where('id', $row->id)->update([
                'open_library_key' => '/works/pending/'.$row->id,
            ]);
        }

        Schema::table('works', function (Blueprint $table): void {
            $table->dropUnique(['goodbooks_book_id']);
        });

        Schema::table('works', function (Blueprint $table): void {
            $table->dropColumn('goodbooks_book_id');
        });

        Schema::table('works', function (Blueprint $table): void {
            $table->string('open_library_key', 255)->nullable(false)->change();
        });
    }
};
