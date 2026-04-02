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
        Schema::disableForeignKeyConstraints();

        Schema::table('reading_logs', function (Blueprint $table): void {
            $table->dropForeign(['book_id']);
        });

        Schema::table('book_featured_entries', function (Blueprint $table): void {
            $table->dropForeign(['book_id']);
        });

        Schema::table('book_author', function (Blueprint $table): void {
            $table->dropForeign(['book_id']);
            $table->dropForeign(['author_id']);
        });

        Schema::rename('books', 'works');

        Schema::table('works', function (Blueprint $table): void {
            $table->renameColumn('open_library_id', 'open_library_key');
            $table->renameColumn('publish_year', 'first_publish_year');
        });

        Schema::table('works', function (Blueprint $table): void {
            $table->string('subtitle', 512)->nullable()->after('title');
            $table->unsignedInteger('cover_id')->nullable()->after('cover_url');
        });

        foreach (DB::table('works')->whereNotNull('cover_url')->cursor() as $row) {
            $coverUrl = $row->cover_url;
            if (! is_string($coverUrl) || $coverUrl === '') {
                continue;
            }
            if (preg_match('#/b/id/(\d+)-#', $coverUrl, $matches) === 1) {
                DB::table('works')->where('id', $row->id)->update([
                    'cover_id' => (int) $matches[1],
                ]);
            }
        }

        Schema::table('works', function (Blueprint $table): void {
            $table->dropColumn('cover_url');
        });

        Schema::rename('book_author', 'author_works');

        Schema::table('author_works', function (Blueprint $table): void {
            $table->renameColumn('book_id', 'work_id');
        });

        Schema::table('author_works', function (Blueprint $table): void {
            $table->string('role', 255)->nullable()->after('author_id');
        });

        Schema::table('reading_logs', function (Blueprint $table): void {
            $table->renameColumn('book_id', 'work_id');
        });

        Schema::table('book_featured_entries', function (Blueprint $table): void {
            $table->renameColumn('book_id', 'work_id');
        });

        Schema::table('author_works', function (Blueprint $table): void {
            $table->foreign('work_id')->references('id')->on('works')->cascadeOnDelete();
            $table->foreign('author_id')->references('id')->on('authors')->cascadeOnDelete();
        });

        Schema::table('reading_logs', function (Blueprint $table): void {
            $table->foreign('work_id')->references('id')->on('works');
        });

        Schema::table('book_featured_entries', function (Blueprint $table): void {
            $table->foreign('work_id')->references('id')->on('works')->cascadeOnDelete();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('reading_logs', function (Blueprint $table): void {
            $table->dropForeign(['work_id']);
        });

        Schema::table('book_featured_entries', function (Blueprint $table): void {
            $table->dropForeign(['work_id']);
        });

        Schema::table('author_works', function (Blueprint $table): void {
            $table->dropForeign(['work_id']);
            $table->dropForeign(['author_id']);
        });

        Schema::table('reading_logs', function (Blueprint $table): void {
            $table->renameColumn('work_id', 'book_id');
        });

        Schema::table('book_featured_entries', function (Blueprint $table): void {
            $table->renameColumn('work_id', 'book_id');
        });

        Schema::table('author_works', function (Blueprint $table): void {
            $table->dropColumn('role');
        });

        Schema::table('author_works', function (Blueprint $table): void {
            $table->renameColumn('work_id', 'book_id');
        });

        Schema::rename('author_works', 'book_author');

        Schema::table('works', function (Blueprint $table): void {
            $table->string('cover_url', 255)->nullable()->after('subtitle');
        });

        foreach (DB::table('works')->whereNotNull('cover_id')->cursor() as $row) {
            DB::table('works')->where('id', $row->id)->update([
                'cover_url' => 'https://covers.openlibrary.org/b/id/'.$row->cover_id.'-M.jpg',
            ]);
        }

        Schema::table('works', function (Blueprint $table): void {
            $table->dropColumn(['cover_id', 'subtitle']);
        });

        Schema::table('works', function (Blueprint $table): void {
            $table->renameColumn('open_library_key', 'open_library_id');
            $table->renameColumn('first_publish_year', 'publish_year');
        });

        Schema::rename('works', 'books');

        Schema::table('book_author', function (Blueprint $table): void {
            $table->foreign('book_id')->references('id')->on('books')->cascadeOnDelete();
            $table->foreign('author_id')->references('id')->on('authors')->cascadeOnDelete();
        });

        Schema::table('reading_logs', function (Blueprint $table): void {
            $table->foreign('book_id')->references('id')->on('books');
        });

        Schema::table('book_featured_entries', function (Blueprint $table): void {
            $table->foreign('book_id')->references('id')->on('books')->cascadeOnDelete();
        });

        Schema::enableForeignKeyConstraints();
    }
};
